<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
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
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'image',
        'unit',
        'track_stock',
        'is_active'
    ];

    protected $validationRules = [
        'name' => 'required|max_length[255]',
    ];

    /**
     * Get products with variants for tenant
     */
    public function getProductsWithVariants(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('products.tenant_id', $tenantId)
            ->where('products.is_active', true);

        if (!empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('products.name', $filters['search'])
                ->orLike('products.sku', $filters['search'])
                ->orLike('products.barcode', $filters['search'])
                ->groupEnd();
        }

        $builder->select('products.*, c.name as category_name')
            ->join('categories c', 'c.id = products.category_id', 'left');

        $products = $builder->findAll();

        // Load variants for each product
        $variantModel = new ProductVariantModel();
        foreach ($products as &$product) {
            $product->variants = $variantModel->where('product_id', $product->id)
               // ->where('is_active', true)
                ->findAll();
            if (!empty($variants)) {
                // Ambil harga dari varian pertama sebagai harga tampilan
                $product->selling_price = $variants[0]->selling_price;

                // Hitung total stok dari semua varian
                $totalStock = 0;
                foreach ($variants as $v) {
                    $totalStock += $v->stock_qty;
                }
                $product->stock_qty = $totalStock;
            } else {
                // Default jika tidak punya varian
                $product->selling_price = 0;
                $product->stock_qty = 0;
            }    
        }

        return $products;
    }

    /**
     * Search products by name, SKU, or barcode
     */
    public function search(int $tenantId, string $query, int $limit = 20): array
    {
        return $this->select('products.*, product_variants.selling_price, product_variants.stock_qty, product_variants.id as variant_id')
            ->join('product_variants', 'product_variants.product_id = products.id', 'left')
            ->where('products.tenant_id', $tenantId)
            ->where('products.is_active', true)
            ->groupStart()
            ->like('products.name', $query)
            ->orLike('products.sku', $query)
            ->orLike('products.barcode', $query)
            ->orLike('product_variants.sku', $query)
            ->orLike('product_variants.barcode', $query)
            ->groupEnd()
            ->limit($limit)
            ->findAll();
    }
}
