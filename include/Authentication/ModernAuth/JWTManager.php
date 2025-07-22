<?php
/**
 * SuiteCRM Modern Authentication - JWT Token Manager
 * 
 * Handles JWT token creation, validation, and refresh
 */

namespace SuiteCRM\Authentication\ModernAuth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class JWTManager
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $accessTokenExpiry = 3600; // 1 hour
    private int $refreshTokenExpiry = 86400 * 30; // 30 days
    
    public function __construct()
    {
        global $sugar_config;
        
        // Use or generate JWT secret key
        if (empty($sugar_config['jwt_secret_key'])) {
            $this->secretKey = $this->generateSecretKey();
            $this->updateConfigWithSecretKey($this->secretKey);
        } else {
            $this->secretKey = $sugar_config['jwt_secret_key'];
        }
    }
    
    /**
     * Generate JWT token for authenticated user
     */
    public function generateTokens(string $userId, array $userInfo = []): array
    {
        $issuedAt = time();
        $accessExpiry = $issuedAt + $this->accessTokenExpiry;
        $refreshExpiry = $issuedAt + $this->refreshTokenExpiry;
        
        // Access token payload
        $accessPayload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'suitecrm',
            'sub' => $userId,
            'iat' => $issuedAt,
            'exp' => $accessExpiry,
            'type' => 'access',
            'user_info' => $userInfo
        ];
        
        // Refresh token payload
        $refreshPayload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'suitecrm',
            'sub' => $userId,
            'iat' => $issuedAt,
            'exp' => $refreshExpiry,
            'type' => 'refresh'
        ];
        
        $accessToken = JWT::encode($accessPayload, $this->secretKey, $this->algorithm);
        $refreshToken = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);
        
        // Store refresh token in database
        $this->storeRefreshToken($userId, $refreshToken, $refreshExpiry);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenExpiry,
            'token_type' => 'Bearer'
        ];
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null; // Token expired
        } catch (Exception $e) {
            return null; // Invalid token
        }
    }
    
    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $payload = $this->validateToken($refreshToken);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }
        
        // Verify refresh token exists in database
        if (!$this->verifyRefreshToken($payload['sub'], $refreshToken)) {
            return null;
        }
        
        // Generate new tokens
        return $this->generateTokens($payload['sub']);
    }
    
    /**
     * Revoke refresh token
     */
    public function revokeRefreshToken(string $userId, string $refreshToken = null): bool
    {
        global $db;
        
        if ($refreshToken) {
            $tokenHash = hash('sha256', $refreshToken);
            $query = "DELETE FROM user_refresh_tokens 
                     WHERE user_id = '{$userId}' 
                     AND token = '{$tokenHash}'";
            return $db->query($query);
        } else {
            // Revoke all refresh tokens for user
            $query = "DELETE FROM user_refresh_tokens 
                     WHERE user_id = '{$userId}'";
            return $db->query($query);
        }
    }
    
    /**
     * Set token as HTTP-only cookie
     */
    public function setTokenCookie(string $accessToken, string $refreshToken): void
    {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        
        // Set access token cookie (shorter expiry)
        setcookie(
            'suitecrm_access_token',
            $accessToken,
            [
                'expires' => time() + $this->accessTokenExpiry,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        // Set refresh token cookie (longer expiry)
        setcookie(
            'suitecrm_refresh_token',
            $refreshToken,
            [
                'expires' => time() + $this->refreshTokenExpiry,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Clear token cookies
     */
    public function clearTokenCookies(): void
    {
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        
        setcookie('suitecrm_access_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain
        ]);
        
        setcookie('suitecrm_refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $domain
        ]);
    }
    
    /**
     * Generate secure secret key
     */
    private function generateSecretKey(): string
    {
        return base64_encode(random_bytes(64));
    }
    
    /**
     * Update config with secret key
     */
    private function updateConfigWithSecretKey(string $secretKey): void
    {
        global $sugar_config;
        $sugar_config['jwt_secret_key'] = $secretKey;
        
        // Write to config_override.php
        $configFile = 'config_override.php';
        $configContent = "<?php\n\$sugar_config['jwt_secret_key'] = '$secretKey';\n";
        
        if (file_exists($configFile)) {
            $existingContent = file_get_contents($configFile);
            if (strpos($existingContent, 'jwt_secret_key') === false) {
                $configContent = $existingContent . "\n\$sugar_config['jwt_secret_key'] = '$secretKey';\n";
            }
        }
        
        file_put_contents($configFile, $configContent);
    }
    
    /**
     * Store refresh token in database
     */
    private function storeRefreshToken(string $userId, string $refreshToken, int $expiry): void
    {
        global $db;
        
        $tokenHash = hash('sha256', $refreshToken);
        $expiryDate = date('Y-m-d H:i:s', $expiry);
        $createdDate = date('Y-m-d H:i:s');
        
        // Use SuiteCRM's database methods
        $query = "INSERT INTO user_refresh_tokens (user_id, token, expires_at, created_at) 
                  VALUES ('{$userId}', '{$tokenHash}', '{$expiryDate}', '{$createdDate}')
                  ON DUPLICATE KEY UPDATE token = '{$tokenHash}', expires_at = '{$expiryDate}'";
        
        $db->query($query);
    }
    
    /**
     * Verify refresh token exists in database
     */
    private function verifyRefreshToken(string $userId, string $refreshToken): bool
    {
        global $db;
        
        $tokenHash = hash('sha256', $refreshToken);
        $query = "SELECT id FROM user_refresh_tokens 
                  WHERE user_id = '{$userId}' 
                  AND token = '{$tokenHash}' 
                  AND expires_at > NOW()";
        
        $result = $db->query($query);
        
        return $db->getRowCount($result) > 0;
    }
}
