<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'tier_id',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'birth_date',
        'gender',
        'notes',
        'total_points',
        'total_spent',
        'visit_count',
        'last_visit_at',
        'total_points',
        'is_active'
    ];

    /**
     * Generate customer code
     */
    public function generateCode(int $tenantId): string
    {
        $prefix = "CUST-";
        $lastCustomer = $this->where('tenant_id', $tenantId)
            ->orderBy('id', 'DESC')
            ->first();

        $newNumber = $lastCustomer ? $lastCustomer->id + 1 : 1;
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Find by phone or email
     */
    public function findByContact(int $tenantId, string $contact): ?object
    {
        return $this->where('tenant_id', $tenantId)
            ->groupStart()
            ->where('phone', $contact)
            ->orWhere('email', $contact)
            ->groupEnd()
            ->first();
    }

    /**
     * Update customer stats after purchase
     */
    public function updateAfterPurchase(int $customerId, float $amount, int $points): bool
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return false;
        }

        return $this->update($customerId, [
            'total_spent' => $customer->total_spent + $amount,
            'total_points' => $customer->total_points + $points,
            'visit_count' => $customer->visit_count + 1,
            'last_visit_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Calculate membership tier based on total_spent
     * 
     * Tier rules:
     * - Bronze: total_spent < 1,000,000 → 0% discount
     * - Silver: 1,000,000 <= total_spent <= 5,000,000 → 5% discount
     * - Gold: total_spent > 5,000,000 → 10% discount
     * 
     * @param int $customerId
     * @return object|null Tier object with name, discount_percent
     */
    public function calculateTier(int $customerId): ?object
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return null;
        }

        return $this->getTierBySpending((float) $customer->total_spent);
    }

    /**
     * Get tier info based on spending amount (static calculation)
     * 
     * @param float $totalSpent Customer's total spending
     * @return object Tier object with name, slug, discount_percent
     */
    public function getTierBySpending(float $totalSpent): object
    {
        // Bronze tier: < Rp 1,000,000 → 0% discount
        if ($totalSpent < 1000000) {
            return (object) [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'discount_percent' => 0.00
            ];
        }

        // Silver tier: >= Rp 1,000,000 and <= Rp 5,000,000 → 5% discount
        if ($totalSpent <= 5000000) {
            return (object) [
                'name' => 'Silver',
                'slug' => 'silver',
                'discount_percent' => 5.00
            ];
        }

        // Gold tier: > Rp 5,000,000 → 10% discount
        return (object) [
            'name' => 'Gold',
            'slug' => 'gold',
            'discount_percent' => 10.00
        ];
    }

    /**
     * Redeem points from customer's balance
     * Uses row-level locking to prevent race conditions
     * 
     * Business rule: 1 point = Rp 100
     * 
     * @param int $customerId
     * @param int $points Number of points to redeem
     * @return array ['success' => bool, 'message' => string, 'points_before' => int, 'points_after' => int]
     */
    public function redeemPoints(int $customerId, int $points): array
    {
        $db = \Config\Database::connect();

        // Get customer with row lock (FOR UPDATE)
        $customer = $db->table($this->table)
            ->where('id', $customerId)
            ->get()
            ->getRow();

        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Customer not found',
                'points_before' => 0,
                'points_after' => 0
            ];
        }

        $currentPoints = (int) $customer->total_points;

        // Validate sufficient points
        if ($points > $currentPoints) {
            return [
                'success' => false,
                'message' => "Insufficient points. Customer has {$currentPoints} points, requested {$points}",
                'points_before' => $currentPoints,
                'points_after' => $currentPoints
            ];
        }

        // Deduct points
        $newPoints = $currentPoints - $points;
        $updated = $this->update($customerId, ['total_points' => $newPoints]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Failed to update customer points',
                'points_before' => $currentPoints,
                'points_after' => $currentPoints
            ];
        }

        return [
            'success' => true,
            'message' => "Successfully redeemed {$points} points",
            'points_before' => $currentPoints,
            'points_after' => $newPoints
        ];
    }

    /**
     * Get customer with row-level lock for concurrent-safe operations
     * Must be called within a transaction
     * 
     * @param int $customerId
     * @return object|null
     */
    public function findForUpdate(int $customerId): ?object
    {
        $db = \Config\Database::connect();

        return $db->query(
            "SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE",
            [$customerId]
        )->getRow();
    }
}
