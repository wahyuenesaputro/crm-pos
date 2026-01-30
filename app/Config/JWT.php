<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class JWT extends BaseConfig
{
    /**
     * JWT Secret Key - MUST be changed in production
     */
    public string $secretKey = 'your-secret-key-change-in-production';

    /**
     * JWT Algorithm
     */
    public string $algorithm = 'HS256';

    /**
     * Access token expiration time in seconds (default: 1 hour)
     */
    public int $accessTokenExpire = 3600;

    /**
     * Refresh token expiration time in seconds (default: 7 days)
     */
    public int $refreshTokenExpire = 604800;

    /**
     * Token issuer
     */
    public string $issuer = 'crm-pos-api';

    /**
     * Token audience
     */
    public string $audience = 'crm-pos-client';
}
