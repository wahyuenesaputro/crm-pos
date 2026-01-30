<?php

namespace App\Controllers\Api;

use App\Models\SaleModel;

class ReportController extends BaseApiController
{
    protected SaleModel $saleModel;

    public function __construct()
    {
        parent::__construct();
        $this->saleModel = new SaleModel();
    }

    /**
     * GET /api/v1/reports/daily-sales
     */
    public function dailySales()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $branchId = $this->request->getGet('branch_id') ?? $this->branchId;

        // For owners without branch_id, aggregate all branches in tenant
        $summary = $this->saleModel->getDailySummary($branchId, $date, $this->tenantId);
        $summary->date = $date;
        $summary->branch_id = $branchId;

        return $this->success($summary);
    }

    /**
     * GET /api/v1/reports/inventory
     */
    public function inventory()
    {
        $db = \Config\Database::connect();

        $inventory = $db->table('product_variants pv')
            ->select('p.name as product_name, pv.sku, pv.stock_qty, pv.cost_price, pv.selling_price')
            ->select('(pv.stock_qty * pv.cost_price) as stock_value')
            ->join('products p', 'p.id = pv.product_id')
            ->where('p.tenant_id', $this->tenantId)
            ->where('pv.is_active', true)
            ->where('pv.deleted_at IS NULL')
            ->orderBy('pv.stock_qty', 'ASC')
            ->get()
            ->getResult();

        $totalValue = array_sum(array_column($inventory, 'stock_value'));

        return $this->success([
            'items' => $inventory,
            'total_stock_value' => $totalValue,
            'low_stock_count' => count(array_filter($inventory, fn($i) => $i->stock_qty <= 10))
        ]);
    }

    /**
     * GET /api/v1/reports/best-sellers
     */
    public function bestSellers()
    {
        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?? date('Y-m-d');
        $limit = $this->request->getGet('limit') ?? 10;

        $db = \Config\Database::connect();

        $builder = $db->table('sale_items si')
            ->select('si.product_name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue')
            ->join('sales s', 's.id = si.sale_id')
            ->where('s.status', 'completed')
            ->where('DATE(s.transaction_date) >=', $dateFrom)
            ->where('DATE(s.transaction_date) <=', $dateTo);

        // Scope by branch or tenant
        if ($this->branchId) {
            $builder->where('s.branch_id', $this->branchId);
        } else {
            $builder->join('branches b', 'b.id = s.branch_id')
                ->join('stores st', 'st.id = b.store_id')
                ->where('st.tenant_id', $this->tenantId);
        }

        $bestSellers = $builder->groupBy('si.variant_id, si.product_name')
            ->orderBy('total_qty', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();

        return $this->success([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'items' => $bestSellers
        ]);
    }

    /**
     * GET /api/v1/reports/customer-rfm
     */
    public function customerRfm()
    {
        $db = \Config\Database::connect();

        $customers = $db->table('customers c')
            ->select('c.id, c.name, c.total_spent, c.visit_count, c.last_visit_at')
            ->select('DATEDIFF(NOW(), c.last_visit_at) as days_since_visit')
            ->where('c.tenant_id', $this->tenantId)
            ->where('c.is_active', true)
            ->orderBy('c.total_spent', 'DESC')
            ->limit(100)
            ->get()
            ->getResult();

        // Simple RFM scoring
        foreach ($customers as &$customer) {
            $r = $customer->days_since_visit <= 30 ? 5 : ($customer->days_since_visit <= 90 ? 3 : 1);
            $f = $customer->visit_count >= 10 ? 5 : ($customer->visit_count >= 5 ? 3 : 1);
            $m = $customer->total_spent >= 1000000 ? 5 : ($customer->total_spent >= 500000 ? 3 : 1);

            $customer->rfm_score = $r . $f . $m;
            $customer->segment = $this->getRfmSegment($r, $f, $m);
        }

        return $this->success($customers);
    }

    private function getRfmSegment(int $r, int $f, int $m): string
    {
        $score = $r + $f + $m;
        if ($score >= 13)
            return 'Champion';
        if ($score >= 10)
            return 'Loyal';
        if ($r >= 4 && $f <= 2)
            return 'New Customer';
        if ($r <= 2 && $f >= 3)
            return 'At Risk';
        if ($r <= 2 && $f <= 2)
            return 'Lost';
        return 'Potential Loyalist';
    }
}
