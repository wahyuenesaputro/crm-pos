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
}
