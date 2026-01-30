<?php

namespace App\Controllers\Api;

class VoucherController extends BaseApiController
{
    /**
     * GET /api/v1/vouchers
     */
    public function index()
    {
        $db = \Config\Database::connect();

        $vouchers = $db->table('vouchers')
            ->where('tenant_id', $this->tenantId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->success($vouchers);
    }

    /**
     * POST /api/v1/vouchers/validate
     */
    public function validateCode()
    {
        $data = $this->request->getJSON(true);
        $code = $data['code'] ?? null;
        $subtotal = $data['subtotal'] ?? 0;

        if (empty($code)) {
            return $this->error('Voucher code is required', 422);
        }

        $db = \Config\Database::connect();

        $voucher = $db->table('vouchers')
            ->where('tenant_id', $this->tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->get()
            ->getRow();

        if (!$voucher) {
            return $this->error('Invalid voucher code', 404);
        }

        // Check validity dates
        $now = date('Y-m-d H:i:s');
        if ($voucher->valid_from && $now < $voucher->valid_from) {
            return $this->error('Voucher is not yet valid', 400);
        }
        if ($voucher->valid_until && $now > $voucher->valid_until) {
            return $this->error('Voucher has expired', 400);
        }

        // Check usage limit
        if ($voucher->usage_limit !== null && $voucher->usage_count >= $voucher->usage_limit) {
            return $this->error('Voucher usage limit reached', 400);
        }

        // Check minimum order
        if ($voucher->min_order && $subtotal < $voucher->min_order) {
            return $this->error('Minimum order of Rp ' . number_format($voucher->min_order, 0, ',', '.') . ' required', 400);
        }

        // Calculate discount
        $discount = 0;
        if ($voucher->type === 'percentage') {
            $discount = $subtotal * ($voucher->value / 100);
            if ($voucher->max_discount && $discount > $voucher->max_discount) {
                $discount = $voucher->max_discount;
            }
        } else {
            $discount = $voucher->value;
        }

        return $this->success([
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'type' => $voucher->type,
            'value' => $voucher->value,
            'discount_amount' => $discount,
            'description' => $voucher->description
        ], 'Voucher is valid');
    }

    /**
     * POST /api/v1/vouchers
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['code'])) {
            return $this->error('Voucher code is required', 422);
        }
        if (empty($data['type']) || !in_array($data['type'], ['percentage', 'fixed'])) {
            return $this->error('Type must be percentage or fixed', 422);
        }
        if (!isset($data['value']) || $data['value'] <= 0) {
            return $this->error('Value must be greater than 0', 422);
        }

        $db = \Config\Database::connect();

        // Check for duplicate code
        $existing = $db->table('vouchers')
            ->where('tenant_id', $this->tenantId)
            ->where('code', $data['code'])
            ->get()
            ->getRow();

        if ($existing) {
            return $this->error('Voucher code already exists', 409);
        }

        $voucherData = [
            'tenant_id' => $this->tenantId,
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order' => $data['min_order'] ?? null,
            'max_discount' => $data['max_discount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_count' => 0,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->table('vouchers')->insert($voucherData);
        $id = $db->insertID();

        $voucher = $db->table('vouchers')->where('id', $id)->get()->getRowArray();
        return $this->success($voucher, 'Voucher created', 201);
    }

    /**
     * PUT /api/v1/vouchers/:id
     */
    public function update($id = null)
    {
        $db = \Config\Database::connect();

        $voucher = $db->table('vouchers')
            ->where('id', $id)
            ->where('tenant_id', $this->tenantId)
            ->get()
            ->getRow();

        if (!$voucher) {
            return $this->error('Voucher not found', 404);
        }

        $data = $this->request->getJSON(true);

        $updateData = [];
        if (isset($data['description']))
            $updateData['description'] = $data['description'];
        if (isset($data['type']))
            $updateData['type'] = $data['type'];
        if (isset($data['value']))
            $updateData['value'] = $data['value'];
        if (isset($data['min_order']))
            $updateData['min_order'] = $data['min_order'];
        if (isset($data['max_discount']))
            $updateData['max_discount'] = $data['max_discount'];
        if (isset($data['usage_limit']))
            $updateData['usage_limit'] = $data['usage_limit'];
        if (isset($data['valid_from']))
            $updateData['valid_from'] = $data['valid_from'];
        if (isset($data['valid_until']))
            $updateData['valid_until'] = $data['valid_until'];
        if (isset($data['is_active']))
            $updateData['is_active'] = $data['is_active'];
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $db->table('vouchers')->where('id', $id)->update($updateData);

        $updated = $db->table('vouchers')->where('id', $id)->get()->getRowArray();
        return $this->success($updated, 'Voucher updated');
    }

    /**
     * DELETE /api/v1/vouchers/:id
     */
    public function delete($id = null)
    {
        $db = \Config\Database::connect();

        $voucher = $db->table('vouchers')
            ->where('id', $id)
            ->where('tenant_id', $this->tenantId)
            ->get()
            ->getRow();

        if (!$voucher) {
            return $this->error('Voucher not found', 404);
        }

        $db->table('vouchers')->where('id', $id)->update([
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success(null, 'Voucher deleted');
    }
}
