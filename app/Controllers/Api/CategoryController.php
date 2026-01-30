<?php

namespace App\Controllers\Api;

class CategoryController extends BaseApiController
{
    /**
     * GET /api/v1/categories
     */
    public function index()
    {
        $db = \Config\Database::connect();

        $categories = $db->table('categories')
            ->where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        return $this->success($categories);
    }

    /**
     * POST /api/v1/categories
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['name'])) {
            return $this->error('Category name is required', 422);
        }

        $db = \Config\Database::connect();

        // Get max sort order
        $maxSort = $db->table('categories')
            ->where('tenant_id', $this->tenantId)
            ->selectMax('sort_order')
            ->get()
            ->getRow();

        $categoryData = [
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'slug' => url_title($data['name'], '-', true),
            'description' => $data['description'] ?? null,
            'sort_order' => ($maxSort->sort_order ?? 0) + 1,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->table('categories')->insert($categoryData);
        $id = $db->insertID();

        $category = $db->table('categories')->where('id', $id)->get()->getRow();
        return $this->success($category, 'Category created', 201);
    }
}
