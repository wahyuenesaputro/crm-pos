<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use App\Libraries\JWTHandler;

class AuthController extends BaseApiController
{
    protected UserModel $userModel;
    protected JWTHandler $jwt;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->jwt = new JWTHandler();
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login()
    {
        // Support JSON input
        $json = $this->request->getJSON(true);
        $username = $json['username'] ?? $this->request->getPost('username');
        $password = $json['password'] ?? $this->request->getPost('password');

        // Manual validation after parsing
        if (empty($username) || empty($password)) {
            return $this->error('Username and password are required', 422);
        }

        $user = $this->userModel->findByCredential($username);
        if (!$user || !password_verify($password, $user->password_hash)) {
            return $this->error('Invalid credentials', 401);
        }

        if (!$user->is_active) {
            return $this->error('Account is disabled', 403);
        }

        // Get user with roles
        $userWithRoles = $this->userModel->getUserWithRoles($user->id);

        // Generate tokens
        $tokenData = [
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'branch_id' => $user->branch_id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'roles' => $userWithRoles->roles ?? []
        ];

        $accessToken = $this->jwt->generateAccessToken($tokenData);
        $refreshToken = $this->jwt->generateRefreshToken($user->id);

        // Store refresh token
        $db = \Config\Database::connect();
        $db->table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + config('JWT')->refreshTokenExpire),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Update last login
        $this->userModel->update($user->id, ['last_login_at' => date('Y-m-d H:i:s')]);

        return $this->success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => config('JWT')->accessTokenExpire,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'roles' => $userWithRoles->roles ?? [],
                'branch_id' => $user->branch_id
            ]
        ], 'Login successful');
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh()
    {
        $refreshToken = $this->request->getPost('refresh_token');
        if (!$refreshToken) {
            return $this->error('Refresh token required', 400);
        }

        $decoded = $this->jwt->validateToken($refreshToken);
        if (!$decoded || ($decoded->type ?? '') !== 'refresh') {
            return $this->error('Invalid refresh token', 401);
        }

        // Verify token exists and not revoked
        $db = \Config\Database::connect();
        $storedToken = $db->table('refresh_tokens')
            ->where('user_id', $decoded->user_id)
            ->where('token', hash('sha256', $refreshToken))
            ->where('revoked', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();

        if (!$storedToken) {
            return $this->error('Token revoked or expired', 401);
        }

        $user = $this->userModel->getUserWithRoles($decoded->user_id);
        if (!$user || !$user->is_active) {
            return $this->error('User not found or disabled', 401);
        }

        // Generate new tokens
        $tokenData = [
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'branch_id' => $user->branch_id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'roles' => $user->roles ?? []
        ];

        $newAccessToken = $this->jwt->generateAccessToken($tokenData);
        $newRefreshToken = $this->jwt->generateRefreshToken($user->id);

        // Revoke old and store new refresh token
        $db->table('refresh_tokens')
            ->where('id', $storedToken->id)
            ->update(['revoked' => true]);

        $db->table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $newRefreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + config('JWT')->refreshTokenExpire),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => config('JWT')->accessTokenExpire
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        $token = $this->jwt->getTokenFromHeader();
        if ($token) {
            $decoded = $this->jwt->validateToken($token);
            if ($decoded && isset($decoded->data->id)) {
                $db = \Config\Database::connect();
                $db->table('refresh_tokens')
                    ->where('user_id', $decoded->data->id)
                    ->update(['revoked' => true]);
            }
        }

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me()
    {
        if (!$this->currentUser) {
            return $this->error('Not authenticated', 401);
        }

        $user = $this->userModel->getUserWithRoles($this->currentUser->id);

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'roles' => $user->roles ?? [],
            'role_names' => $user->role_names ?? [],
            'branch_id' => $user->branch_id,
            'tenant_id' => $user->tenant_id
        ]);
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Request password reset token
     */
    public function forgotPassword()
    {
        $json = $this->request->getJSON(true);
        $email = $json['email'] ?? $this->request->getPost('email');

        if (empty($email)) {
            return $this->error('Email is required', 422);
        }

        $user = $this->userModel->where('email', $email)->first();

        // Always return success to prevent email enumeration
        if (!$user) {
            return $this->success(null, 'If an account with that email exists, a reset link has been sent');
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $db = \Config\Database::connect();

        // Invalidate any existing tokens
        $db->table('password_resets')
            ->where('user_id', $user->id)
            ->update(['used' => true]);

        // Store new token
        $db->table('password_resets')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'used' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // In production, send email here
        // For now, return token for testing (REMOVE IN PRODUCTION)
        return $this->success([
            'message' => 'Password reset token generated',
            'token' => $token, // REMOVE IN PRODUCTION - send via email instead
            'expires_at' => $expiresAt
        ], 'If an account with that email exists, a reset link has been sent');
    }

    /**
     * POST /api/v1/auth/reset-password
     * Reset password with token
     */
    public function resetPassword()
    {
        $json = $this->request->getJSON(true);
        $token = $json['token'] ?? $this->request->getPost('token');
        $password = $json['password'] ?? $this->request->getPost('password');
        $passwordConfirm = $json['password_confirmation'] ?? $this->request->getPost('password_confirmation');

        if (empty($token)) {
            return $this->error('Reset token is required', 422);
        }
        if (empty($password)) {
            return $this->error('New password is required', 422);
        }
        if (strlen($password) < 8) {
            return $this->error('Password must be at least 8 characters', 422);
        }
        if ($password !== $passwordConfirm) {
            return $this->error('Passwords do not match', 422);
        }

        $db = \Config\Database::connect();

        // Find valid token
        $resetRecord = $db->table('password_resets')
            ->where('token', hash('sha256', $token))
            ->where('used', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();

        if (!$resetRecord) {
            return $this->error('Invalid or expired reset token', 400);
        }

        // Update password
        $this->userModel->update($resetRecord->user_id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Mark token as used
        $db->table('password_resets')
            ->where('id', $resetRecord->id)
            ->update(['used' => true]);

        // Revoke all refresh tokens for security
        $db->table('refresh_tokens')
            ->where('user_id', $resetRecord->user_id)
            ->update(['revoked' => true]);

        return $this->success(null, 'Password reset successfully');
    }

    /**
     * POST /api/v1/auth/change-password
     * Change password for authenticated user
     */
    public function changePassword()
    {
        if (!$this->currentUser) {
            return $this->error('Not authenticated', 401);
        }

        $json = $this->request->getJSON(true);
        $currentPassword = $json['current_password'] ?? '';
        $newPassword = $json['new_password'] ?? '';
        $confirmPassword = $json['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            return $this->error('Current and new passwords are required', 422);
        }
        if (strlen($newPassword) < 8) {
            return $this->error('New password must be at least 8 characters', 422);
        }
        if ($newPassword !== $confirmPassword) {
            return $this->error('Passwords do not match', 422);
        }

        $user = $this->userModel->find($this->currentUser->id);
        if (!password_verify($currentPassword, $user->password_hash)) {
            return $this->error('Current password is incorrect', 400);
        }

        $this->userModel->update($this->currentUser->id, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success(null, 'Password changed successfully');
    }
}
