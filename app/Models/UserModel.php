<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'username',
        'email',
        'password_hash',
        'pin',
        'full_name',
        'phone',
        'avatar',
        'is_active',
        'last_login_at'
    ];

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email',
        'password_hash' => 'required',
        'full_name' => 'required|max_length[255]',
    ];

    /**
     * Find user by username or email for login
     */
    public function findByCredential(string $credential): ?object
    {
        return $this->where('username', $credential)
            ->orWhere('email', $credential)
            ->first();
    }

    /**
     * Get user with roles
     */
    public function getUserWithRoles(int $userId): ?object
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $db = \Config\Database::connect();
        $roles = $db->table('user_roles')
            ->select('roles.slug, roles.name')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->get()
            ->getResultArray();

        $user->roles = array_column($roles, 'slug');
        $user->role_names = array_column($roles, 'name');

        return $user;
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, int $roleId): bool
    {
        $db = \Config\Database::connect();
        return $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
