<?php

namespace App\Libraries;

use Config\JWT as JWTConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JWTHandler
{
    protected JWTConfig $config;

    public function __construct()
    {
        $this->config = config('JWT');
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(array $userData): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->config->accessTokenExpire;

        $payload = [
            'iss' => $this->config->issuer,
            'aud' => $this->config->audience,
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $userData,
        ];

        return JWT::encode($payload, $this->config->secretKey, $this->config->algorithm);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(int $userId): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->config->refreshTokenExpire;

        $payload = [
            'iss' => $this->config->issuer,
            'aud' => $this->config->audience,
            'iat' => $issuedAt,
            'exp' => $expire,
            'type' => 'refresh',
            'user_id' => $userId,
        ];

        return JWT::encode($payload, $this->config->secretKey, $this->config->algorithm);
    }

    /**
     * Validate and decode token
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->config->secretKey, $this->config->algorithm));
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get token from Authorization header
     */
    public function getTokenFromHeader(): ?string
    {
        $request = service('request');
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get user data from token
     */
    public function getUserFromToken(): ?object
    {
        $token = $this->getTokenFromHeader();
        if (!$token) {
            return null;
        }

        $decoded = $this->validateToken($token);
        if (!$decoded || !isset($decoded->data)) {
            return null;
        }

        return $decoded->data;
    }
}
