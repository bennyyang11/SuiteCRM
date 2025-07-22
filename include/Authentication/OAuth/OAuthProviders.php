<?php
/**
 * SuiteCRM Modern Authentication - OAuth 2.0 Providers
 * 
 * Handles OAuth integration for Google, Microsoft, and GitHub
 */

namespace SuiteCRM\Authentication\OAuth;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Microsoft;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;
use Exception;

class OAuthProviders
{
    private array $config;
    private string $redirectUri;
    
    public function __construct()
    {
        global $sugar_config;
        
        $this->config = $sugar_config['oauth_providers'] ?? [];
        $this->redirectUri = $this->getBaseUrl() . '/modules/Users/oauth_callback.php';
    }
    
    /**
     * Get OAuth provider instance
     */
    public function getProvider(string $provider): ?object
    {
        if (!isset($this->config[$provider])) {
            return null;
        }
        
        $config = $this->config[$provider];
        
        switch ($provider) {
            case 'google':
                return new Google([
                    'clientId' => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri' => $this->redirectUri . '?provider=google',
                    'hostedDomain' => $config['hosted_domain'] ?? null,
                ]);
                
            case 'microsoft':
                return new Microsoft([
                    'clientId' => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri' => $this->redirectUri . '?provider=microsoft',
                    'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                    'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                    'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
                    'scopes' => ['openid', 'profile', 'email'],
                ]);
                
            case 'github':
                return new Github([
                    'clientId' => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri' => $this->redirectUri . '?provider=github',
                ]);
                
            default:
                return null;
        }
    }
    
    /**
     * Get authorization URL for provider
     */
    public function getAuthorizationUrl(string $provider): ?string
    {
        $providerInstance = $this->getProvider($provider);
        
        if (!$providerInstance) {
            return null;
        }
        
        $authUrl = $providerInstance->getAuthorizationUrl([
            'scope' => $this->getProviderScopes($provider)
        ]);
        
        // Store state for CSRF protection
        $_SESSION['oauth2state_' . $provider] = $providerInstance->getState();
        
        return $authUrl;
    }
    
    /**
     * Handle OAuth callback and get user info
     */
    public function handleCallback(string $provider, string $code, string $state): ?array
    {
        $providerInstance = $this->getProvider($provider);
        
        if (!$providerInstance) {
            throw new Exception('Invalid OAuth provider');
        }
        
        // Verify state for CSRF protection
        if (!isset($_SESSION['oauth2state_' . $provider]) || 
            $_SESSION['oauth2state_' . $provider] !== $state) {
            throw new Exception('Invalid state parameter');
        }
        
        unset($_SESSION['oauth2state_' . $provider]);
        
        try {
            // Get access token
            $token = $providerInstance->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            // Get user details
            $user = $providerInstance->getResourceOwner($token);
            $userArray = $user->toArray();
            
            // Normalize user data across providers
            $normalizedUser = $this->normalizeUserData($provider, $userArray);
            
            // Store OAuth tokens securely
            $this->storeOAuthTokens($normalizedUser['id'], $provider, $token);
            
            return $normalizedUser;
            
        } catch (Exception $e) {
            throw new Exception('OAuth authentication failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Normalize user data across different providers
     */
    private function normalizeUserData(string $provider, array $userData): array
    {
        switch ($provider) {
            case 'google':
                return [
                    'id' => $userData['sub'] ?? $userData['id'],
                    'email' => $userData['email'],
                    'name' => $userData['name'],
                    'first_name' => $userData['given_name'] ?? '',
                    'last_name' => $userData['family_name'] ?? '',
                    'picture' => $userData['picture'] ?? '',
                    'provider' => 'google'
                ];
                
            case 'microsoft':
                return [
                    'id' => $userData['id'],
                    'email' => $userData['mail'] ?? $userData['userPrincipalName'],
                    'name' => $userData['displayName'],
                    'first_name' => $userData['givenName'] ?? '',
                    'last_name' => $userData['surname'] ?? '',
                    'picture' => '',
                    'provider' => 'microsoft'
                ];
                
            case 'github':
                return [
                    'id' => (string) $userData['id'],
                    'email' => $userData['email'],
                    'name' => $userData['name'] ?? $userData['login'],
                    'first_name' => '',
                    'last_name' => '',
                    'picture' => $userData['avatar_url'] ?? '',
                    'provider' => 'github'
                ];
                
            default:
                return $userData;
        }
    }
    
    /**
     * Get provider-specific scopes
     */
    private function getProviderScopes(string $provider): array
    {
        switch ($provider) {
            case 'google':
                return ['openid', 'email', 'profile'];
            case 'microsoft':
                return ['openid', 'profile', 'email'];
            case 'github':
                return ['user:email'];
            default:
                return [];
        }
    }
    
    /**
     * Store OAuth tokens securely
     */
    private function storeOAuthTokens(string $userId, string $provider, AccessToken $token): void
    {
        global $db;
        
        $query = "INSERT INTO user_oauth_tokens 
                  (user_id, provider, access_token, refresh_token, expires_at, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  access_token = VALUES(access_token),
                  refresh_token = VALUES(refresh_token),
                  expires_at = VALUES(expires_at),
                  updated_at = NOW()";
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([
            $userId,
            $provider,
            $this->encryptToken($token->getToken()),
            $this->encryptToken($token->getRefreshToken()),
            $token->getExpires() ? date('Y-m-d H:i:s', $token->getExpires()) : null,
            date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Encrypt OAuth tokens for secure storage
     */
    private function encryptToken(?string $token): ?string
    {
        if (!$token) {
            return null;
        }
        
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt OAuth tokens
     */
    private function decryptToken(?string $encryptedToken): ?string
    {
        if (!$encryptedToken) {
            return null;
        }
        
        $data = base64_decode($encryptedToken);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $key = $this->getEncryptionKey();
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key for OAuth tokens
     */
    private function getEncryptionKey(): string
    {
        global $sugar_config;
        
        if (empty($sugar_config['oauth_encryption_key'])) {
            $key = base64_encode(random_bytes(32));
            $sugar_config['oauth_encryption_key'] = $key;
            
            // Save to config
            $configFile = 'config_override.php';
            $configContent = file_get_contents($configFile) ?? "<?php\n";
            $configContent .= "\n\$sugar_config['oauth_encryption_key'] = '$key';\n";
            file_put_contents($configFile, $configContent);
            
            return $key;
        }
        
        return base64_decode($sugar_config['oauth_encryption_key']);
    }
    
    /**
     * Get base URL for redirects
     */
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        return $protocol . '://' . $host . rtrim($path, '/');
    }
    
    /**
     * Check if provider is configured
     */
    public function isProviderConfigured(string $provider): bool
    {
        return isset($this->config[$provider]) && 
               !empty($this->config[$provider]['client_id']) && 
               !empty($this->config[$provider]['client_secret']);
    }
    
    /**
     * Get list of configured providers
     */
    public function getConfiguredProviders(): array
    {
        $providers = [];
        
        foreach (['google', 'microsoft', 'github'] as $provider) {
            if ($this->isProviderConfigured($provider)) {
                $providers[] = $provider;
            }
        }
        
        return $providers;
    }
}
