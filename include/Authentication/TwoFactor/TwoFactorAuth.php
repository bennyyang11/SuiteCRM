<?php
/**
 * SuiteCRM Modern Authentication - Two-Factor Authentication
 * 
 * Handles TOTP-based 2FA with Google Authenticator compatibility
 */

namespace SuiteCRM\Authentication\TwoFactor;

use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;

class TwoFactorAuth
{
    private Google2FA $google2fa;
    private string $issuer = 'SuiteCRM';
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    
    /**
     * Generate secret key for 2FA setup
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }
    
    /**
     * Generate QR code for 2FA setup
     */
    public function generateQRCode(string $email, string $secretKey): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $this->issuer,
            $email,
            $secretKey
        );
        
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
            'scale'      => 5,
            'imageBase64' => true,
        ]);
        
        $qrcode = new QRCode($options);
        return $qrcode->render($qrCodeUrl);
    }
    
    /**
     * Verify TOTP code
     */
    public function verifyCode(string $secretKey, string $code, int $tolerance = 1): bool
    {
        return $this->google2fa->verifyKey($secretKey, $code, $tolerance);
    }
    
    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor(string $userId, string $secretKey): bool
    {
        global $db;
        
        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
        $hashedBackupCodes = array_map('password_hash', $backupCodes, array_fill(0, count($backupCodes), PASSWORD_DEFAULT));
        
        $query = "INSERT INTO user_2fa_settings 
                  (user_id, secret_key, backup_codes, enabled, created_at) 
                  VALUES (?, ?, ?, 1, ?) 
                  ON DUPLICATE KEY UPDATE 
                  secret_key = VALUES(secret_key),
                  backup_codes = VALUES(backup_codes),
                  enabled = 1,
                  updated_at = NOW()";
        
        $stmt = $db->getConnection()->prepare($query);
        $result = $stmt->execute([
            $userId,
            $this->encryptSecret($secretKey),
            json_encode($hashedBackupCodes),
            date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Store unhashed backup codes in session for one-time display
            $_SESSION['new_backup_codes'] = $backupCodes;
        }
        
        return $result;
    }
    
    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor(string $userId): bool
    {
        global $db;
        
        $query = "UPDATE user_2fa_settings SET enabled = 0, updated_at = NOW() WHERE user_id = ?";
        $stmt = $db->getConnection()->prepare($query);
        return $stmt->execute([$userId]);
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public function isTwoFactorEnabled(string $userId): bool
    {
        global $db;
        
        $query = "SELECT enabled FROM user_2fa_settings WHERE user_id = ? AND enabled = 1";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get user's 2FA secret key
     */
    public function getUserSecretKey(string $userId): ?string
    {
        global $db;
        
        $query = "SELECT secret_key FROM user_2fa_settings WHERE user_id = ? AND enabled = 1";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        if ($result) {
            return $this->decryptSecret($result['secret_key']);
        }
        
        return null;
    }
    
    /**
     * Verify 2FA code for user
     */
    public function verifyUserCode(string $userId, string $code): bool
    {
        $secretKey = $this->getUserSecretKey($userId);
        
        if (!$secretKey) {
            return false;
        }
        
        // Try TOTP verification first
        if ($this->verifyCode($secretKey, $code)) {
            return true;
        }
        
        // Try backup code verification
        return $this->verifyBackupCode($userId, $code);
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode(string $userId, string $code): bool
    {
        global $db;
        
        $query = "SELECT backup_codes FROM user_2fa_settings WHERE user_id = ? AND enabled = 1";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        if (!$result) {
            return false;
        }
        
        $backupCodes = json_decode($result['backup_codes'], true);
        if (!$backupCodes) {
            return false;
        }
        
        // Check if code matches any backup code
        foreach ($backupCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used backup code
                unset($backupCodes[$index]);
                $backupCodes = array_values($backupCodes); // Reindex array
                
                // Update database
                $updateQuery = "UPDATE user_2fa_settings SET backup_codes = ?, updated_at = NOW() WHERE user_id = ?";
                $updateStmt = $db->getConnection()->prepare($updateQuery);
                $updateStmt->execute([json_encode($backupCodes), $userId]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate new backup codes
     */
    public function generateNewBackupCodes(string $userId): ?array
    {
        global $db;
        
        $backupCodes = $this->generateBackupCodes();
        $hashedBackupCodes = array_map('password_hash', $backupCodes, array_fill(0, count($backupCodes), PASSWORD_DEFAULT));
        
        $query = "UPDATE user_2fa_settings SET backup_codes = ?, updated_at = NOW() WHERE user_id = ? AND enabled = 1";
        $stmt = $db->getConnection()->prepare($query);
        
        if ($stmt->execute([json_encode($hashedBackupCodes), $userId])) {
            return $backupCodes;
        }
        
        return null;
    }
    
    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodesCount(string $userId): int
    {
        global $db;
        
        $query = "SELECT backup_codes FROM user_2fa_settings WHERE user_id = ? AND enabled = 1";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        if ($result) {
            $backupCodes = json_decode($result['backup_codes'], true);
            return count($backupCodes ?? []);
        }
        
        return 0;
    }
    
    /**
     * Generate backup codes
     */
    private function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateBackupCode();
        }
        
        return $codes;
    }
    
    /**
     * Generate single backup code
     */
    private function generateBackupCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Encrypt secret key for storage
     */
    private function encryptSecret(string $secret): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secret, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt secret key
     */
    private function decryptSecret(string $encryptedSecret): string
    {
        $data = base64_decode($encryptedSecret);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $key = $this->getEncryptionKey();
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key for 2FA secrets
     */
    private function getEncryptionKey(): string
    {
        global $sugar_config;
        
        if (empty($sugar_config['2fa_encryption_key'])) {
            $key = base64_encode(random_bytes(32));
            $sugar_config['2fa_encryption_key'] = $key;
            
            // Save to config
            $configFile = 'config_override.php';
            $configContent = file_get_contents($configFile) ?? "<?php\n";
            $configContent .= "\n\$sugar_config['2fa_encryption_key'] = '$key';\n";
            file_put_contents($configFile, $configContent);
            
            return $key;
        }
        
        return base64_decode($sugar_config['2fa_encryption_key']);
    }
    
    /**
     * Get current TOTP code for testing (development only)
     */
    public function getCurrentCode(string $secretKey): string
    {
        return $this->google2fa->getCurrentOtp($secretKey);
    }
}
