<?php

namespace App\Controllers\Api;

use App\Models\ProductVariantModel;

class InventoryController extends BaseApiController
{
    protected ProductVariantModel $variantModel;

    public function __construct()
    {
        parent::__construct();
        $this->variantModel = new ProductVariantModel();
    }

    /**
     * GET /api/v1/inventory
     */
    public function index()
    {
        $db = \Config\Database::connect();

        $inventory = $db->table('product_variants pv')
            ->select('pv.id as variant_id, p.id as product_id, p.name as product_name, pv.sku, pv.stock_qty, pv.min_stock, pv.cost_price, pv.selling_price')
            ->select('(pv.stock_qty * pv.cost_price) as stock_value')
            ->join('products p', 'p.id = pv.product_id')
            ->where('p.tenant_id', $this->tenantId)
            ->where('pv.is_active', true)
            ->where('pv.deleted_at IS NULL')
            ->orderBy('pv.stock_qty', 'ASC')
            ->get()
            ->getResult();

        return $this->success($inventory);
    }

    /**
     * POST /api/v1/inventory/adjust
     */
    public function adjust()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['variant_id'])) {
            return $this->error('variant_id is required', 422);
        }
        if (!isset($data['quantity']) || !is_numeric($data['quantity'])) {
            return $this->error('quantity is required', 422);
        }
        if (empty($data['type']) || !in_array($data['type'], ['in', 'out', 'adjustment'])) {
            return $this->error('type must be in, out, or adjustment', 422);
        }

        $variant = $this->variantModel->find($data['variant_id']);
        if (!$variant) {
            return $this->error('Product variant not found', 404);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $quantity = (int) $data['quantity'];
            $type = $data['type'];

            if ($type === 'adjustment') {
                // Set to exact value
                $newQty = $quantity;
            } else {
                // in = add, out = subtract
                $newQty = $type === 'in'
                    ? $variant->stock_qty + $quantity
                    : $variant->stock_qty - $quantity;
            }

            if ($newQty < 0) {
                throw new \Exception('Resulting stock cannot be negative');
            }

            $this->variantModel->update($variant->id, ['stock_qty' => $newQty]);

            // Log adjustment
            $db->table('inventory_logs')->insert([
                'branch_id' => $this->branchId,
                'variant_id' => $variant->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_qty' => $variant->stock_qty,
                'new_qty' => $newQty,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $this->currentUser->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $db->transCommit();

            $updated = $this->variantModel->find($variant->id);
            return $this->success([
                'variant_id' => $updated->id,
                'previous_qty' => $variant->stock_qty,
                'new_qty' => $updated->stock_qty,
                'adjustment' => $quantity,
                'type' => $type
            ], 'Stock adjusted');

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/inventory/transfer
     */
    public function transfer()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['variant_id']) || empty($data['from_branch_id']) || empty($data['to_branch_id']) || !isset($data['quantity'])) {
            return $this->error('variant_id, from_branch_id, to_branch_id, and quantity are required', 422);
        }

        // For now, just reduce stock (simplified - full implementation would track per-branch stock)
        return $this->success([
            'message' => 'Transfer functionality requires multi-warehouse implementation',
            'variant_id' => $data['variant_id'],
            'quantity' => $data['quantity']
        ]);
    }
}
