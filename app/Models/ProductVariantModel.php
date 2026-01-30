<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table = 'product_variants';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'stock_qty',
        'min_stock',
        'weight',
        'is_active'
    ];

    /**
     * Find variant by SKU or barcode
     */
    public function findBySku(string $sku): ?object
    {
        return $this->where('sku', $sku)
            ->orWhere('barcode', $sku)
            ->first();
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(int $variantId, int $quantity, string $type = 'adjustment'): bool
    {
        $variant = $this->find($variantId);
        if (!$variant) {
            return false;
        }

        $newStock = $type === 'out' ? $variant->stock_qty - $quantity : $variant->stock_qty + $quantity;

        return $this->update($variantId, ['stock_qty' => $newStock]);
    }
}
