<?php

namespace App\Controllers\Api;

use App\Models\CustomerModel;

class CustomerController extends BaseApiController
{
    protected CustomerModel $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new CustomerModel();
    }

    /**
     * GET /api/v1/customers
     */
    public function index()
    {
        $search = $this->request->getGet('search');
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $offset = (int) ($this->request->getGet('offset') ?? 0);

        $builder = $this->customerModel->where('tenant_id', $this->tenantId);

        if ($search) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->orLike('code', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $customers = $builder->orderBy('name', 'ASC')
            ->limit($limit, $offset)
            ->findAll();

        // Enrich with tier name
        $db = \Config\Database::connect();
        foreach ($customers as &$customer) {
            if ($customer->tier_id) {
                $tier = $db->table('membership_tiers')->where('id', $customer->tier_id)->get()->getRow();
                $customer->tier_name = $tier->name ?? null;
            }
        }

        return $this->paginate($customers, $total, ($offset / $limit) + 1, $limit);
    }

    /**
     * GET /api/v1/customers/:id
     */
    public function show($id = null)
    {
        $customer = $this->customerModel->where('tenant_id', $this->tenantId)->find($id);
        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($customer);
    }

    /**
     * POST /api/v1/customers
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'name' => 'required|min_length[2]'
        ];

        if (!$this->validate($rules)) {
            return $this->error('Validation failed', 422, $this->validator->getErrors());
        }

        // Generate customer code
        $code = $this->customerModel->generateCode($this->tenantId);

        // Get default tier
        $db = \Config\Database::connect();
        $defaultTier = $db->table('membership_tiers')
            ->where('tenant_id', $this->tenantId)
            ->where('min_points', 0)
            ->get()
            ->getRow();

        $customerData = [
            'tenant_id' => $this->tenantId,
            'tier_id' => $defaultTier->id ?? null,
            'code' => $code,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'gender' => $data['gender'] ?? null,
            'is_active' => true
        ];

        $id = $this->customerModel->insert($customerData);
        $customer = $this->customerModel->find($id);

        return $this->success($customer, 'Customer created', 201);
    }

    /**
     * PUT /api/v1/customers/:id
     */
    public function update($id = null)
    {
        $customer = $this->customerModel->where('tenant_id', $this->tenantId)->find($id);
        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $data = $this->request->getJSON(true);

        $updateData = [];
        if (isset($data['name']))
            $updateData['name'] = $data['name'];
        if (isset($data['email']))
            $updateData['email'] = $data['email'];
        if (isset($data['phone']))
            $updateData['phone'] = $data['phone'];
        if (isset($data['address']))
            $updateData['address'] = $data['address'];
        if (isset($data['gender']))
            $updateData['gender'] = $data['gender'];

        if (!empty($updateData)) {
            $this->customerModel->update($id, $updateData);
        }

        $customer = $this->customerModel->find($id);
        return $this->success($customer, 'Customer updated');
    }

    /**
     * DELETE /api/v1/customers/:id
     */
    public function delete($id = null)
    {
        $customer = $this->customerModel->where('tenant_id', $this->tenantId)->find($id);
        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $this->customerModel->update($id, ['is_active' => false]);
        return $this->success(null, 'Customer deleted');
    }

    /**
     * GET /api/v1/customers/:id/favorites
     * 
     * Get top 3 most frequently purchased products by customer
     * 
     * Query logic:
     * - Joins sale_items -> sales -> product_variants -> products
     * - Groups by product_id
     * - Orders by: purchase frequency DESC, total quantity DESC, latest purchase DESC
     * 
     * @param int $id Customer ID
     * @return Response JSON array of favorite products with product_id, product_name, price, image
     */
    public function favorites($id = null)
    {
        // Security: Validate id is numeric
        if (!is_numeric($id) || (int) $id <= 0) {
            return $this->error('Invalid customer ID', 400);
        }

        $customerId = (int) $id;

        // Check if customer exists in this tenant
        $customer = $this->customerModel
            ->where('tenant_id', $this->tenantId)
            ->find($customerId);

        if (!$customer) {
            // Return empty array for non-existent customer (as per requirements)
            return $this->success([], 'No favorites found');
        }

        $db = \Config\Database::connect();

        /**
         * Query to find top 3 favorite products:
         * 
         * SELECT 
         *   p.id as product_id,
         *   p.name as product_name,
         *   pv.selling_price as price,
         *   p.image,
         *   COUNT(DISTINCT s.id) as purchase_count,
         *   SUM(si.quantity) as total_quantity,
         *   MAX(s.transaction_date) as last_purchase
         * FROM sale_items si
         * INNER JOIN sales s ON s.id = si.sale_id
         * INNER JOIN product_variants pv ON pv.id = si.variant_id
         * INNER JOIN products p ON p.id = pv.product_id
         * WHERE s.customer_id = ?
         *   AND s.status = 'completed'
         * GROUP BY p.id, p.name, pv.selling_price, p.image
         * ORDER BY purchase_count DESC, total_quantity DESC, last_purchase DESC
         * LIMIT 3
         */
        $favorites = $db->table('sale_items si')
            ->select('
                p.id as product_id,
                p.name as product_name,
                pv.selling_price as price,
                p.image,
                COUNT(DISTINCT s.id) as purchase_count,
                SUM(si.quantity) as total_quantity,
                MAX(s.transaction_date) as last_purchase
            ')
            ->join('sales s', 's.id = si.sale_id', 'inner')
            ->join('product_variants pv', 'pv.id = si.variant_id', 'inner')
            ->join('products p', 'p.id = pv.product_id', 'inner')
            ->where('s.customer_id', $customerId)
            ->where('s.status', 'completed')
            ->groupBy('p.id, p.name, pv.selling_price, p.image')
            ->orderBy('purchase_count', 'DESC')
            ->orderBy('total_quantity', 'DESC')
            ->orderBy('last_purchase', 'DESC')
            ->limit(3)
            ->get()
            ->getResult();

        // Format response: only include required fields
        $result = [];
        foreach ($favorites as $fav) {
            $result[] = [
                'product_id' => (int) $fav->product_id,
                'product_name' => $fav->product_name,
                'price' => (float) $fav->price,
                'image' => $fav->image
            ];
        }

        return $this->success($result);
    }
}
