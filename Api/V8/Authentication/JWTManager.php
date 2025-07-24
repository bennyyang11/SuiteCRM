<?php

namespace Api\V8\Authentication;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use InvalidArgumentException;
use DateTime;

class JWTManager
{
    private string $secretKey;
    private string $algorithm;
    private int $accessTokenExpiry;  // 15 minutes
    private int $refreshTokenExpiry; // 7 days

    public function __construct()
    {
        $this->secretKey = $this->getSecretKey();
        $this->algorithm = 'HS256';
        $this->accessTokenExpiry = 900;  // 15 minutes
        $this->refreshTokenExpiry = 604800; // 7 days
    }

    /**
     * Generate JWT access token for authenticated user
     */
    public function generateAccessToken(array $userInfo): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + $this->accessTokenExpiry;

        $payload = [
            'iss' => 'SuiteCRM-Manufacturing',
            'iat' => $issuedAt,
            'exp' => $expiry,
            'user_id' => $userInfo['id'],
            'username' => $userInfo['username'],
            'role' => $userInfo['role'],
            'territories' => $userInfo['territories'] ?? [],
            'permissions' => $userInfo['permissions'] ?? [],
            'token_type' => 'access'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate JWT refresh token
     */
    public function generateRefreshToken(string $userId): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + $this->refreshTokenExpiry;

        $payload = [
            'iss' => 'SuiteCRM-Manufacturing',
            'iat' => $issuedAt,
            'exp' => $expiry,
            'user_id' => $userId,
            'token_type' => 'refresh',
            'jti' => $this->generateTokenId()
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate and decode JWT token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new InvalidArgumentException('Token has expired', 401);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid token', 401);
        }
    }

    /**
     * Extract token from Authorization header
     */
    public function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }

    /**
     * Check if user has required permission
     */
    public function hasPermission(array $tokenData, string $module, string $action): bool
    {
        $permissions = $tokenData['permissions'] ?? [];
        
        if (empty($permissions)) {
            return false;
        }

        // Admin role has all permissions
        if ($tokenData['role'] === 'admin') {
            return true;
        }

        // Check module-specific permissions
        if (isset($permissions[$module])) {
            return in_array(strtoupper($action), $permissions[$module]);
        }

        return false;
    }

    /**
     * Check if user has access to territory data
     */
    public function hasTerritoryAccess(array $tokenData, string $territoryId): bool
    {
        $territories = $tokenData['territories'] ?? [];
        
        // Admin and Manager roles have access to all territories
        if (in_array($tokenData['role'], ['admin', 'manager'])) {
            return true;
        }

        // Check user's assigned territories
        foreach ($territories as $territory) {
            if ($territory['id'] === $territoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate response with both access and refresh tokens
     */
    public function generateTokenResponse(array $userInfo): array
    {
        $accessToken = $this->generateAccessToken($userInfo);
        $refreshToken = $this->generateRefreshToken($userInfo['id']);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry,
            'user' => [
                'id' => $userInfo['id'],
                'username' => $userInfo['username'],
                'role' => $userInfo['role'],
                'territories' => $userInfo['territories'] ?? []
            ]
        ];
    }

    /**
     * Get secret key from environment or generate default
     */
    private function getSecretKey(): string
    {
        // In production, this should come from environment variable
        $envKey = getenv('JWT_SECRET_KEY');
        if ($envKey) {
            return $envKey;
        }

        // Default key for development (should be changed in production)
        return 'suitecrm_manufacturing_jwt_secret_2024_' . hash('sha256', 'manufacturing_distribution_system');
    }

    /**
     * Generate unique token ID for refresh tokens
     */
    private function generateTokenId(): string
    {
        return uniqid('jwt_', true) . '_' . time();
    }

    /**
     * Blacklist token (for logout functionality)
     */
    public function blacklistToken(string $tokenId): bool
    {
        // In production, store blacklisted tokens in Redis or database
        // For now, we'll use a simple file-based approach
        $blacklistFile = sys_get_temp_dir() . '/jwt_blacklist.json';
        
        $blacklist = [];
        if (file_exists($blacklistFile)) {
            $blacklist = json_decode(file_get_contents($blacklistFile), true) ?? [];
        }
        
        $blacklist[$tokenId] = time();
        
        // Clean up expired entries (older than 7 days)
        $cutoff = time() - $this->refreshTokenExpiry;
        $blacklist = array_filter($blacklist, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        return file_put_contents($blacklistFile, json_encode($blacklist)) !== false;
    }

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted(string $tokenId): bool
    {
        $blacklistFile = sys_get_temp_dir() . '/jwt_blacklist.json';
        
        if (!file_exists($blacklistFile)) {
            return false;
        }
        
        $blacklist = json_decode(file_get_contents($blacklistFile), true) ?? [];
        return isset($blacklist[$tokenId]);
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $tokenData = $this->validateToken($refreshToken);
        
        if ($tokenData['token_type'] !== 'refresh') {
            throw new InvalidArgumentException('Invalid refresh token', 400);
        }
        
        if ($this->isTokenBlacklisted($tokenData['jti'] ?? '')) {
            throw new InvalidArgumentException('Token has been invalidated', 401);
        }
        
        // Get fresh user data from database
        $userInfo = $this->getUserInfoById($tokenData['user_id']);
        
        if (!$userInfo) {
            throw new InvalidArgumentException('User not found', 404);
        }
        
        return $this->generateTokenResponse($userInfo);
    }

    /**
     * Get user information by ID (to be implemented with actual database call)
     */
    private function getUserInfoById(string $userId): ?array
    {
        // This should connect to the actual user database
        // For now, return mock data structure
        return [
            'id' => $userId,
            'username' => 'user_' . $userId,
            'role' => 'sales_rep',
            'territories' => [],
            'permissions' => []
        ];
    }
}
