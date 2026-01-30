<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create('id_ID');

        // 1. Create Tenant
        $tenantId = $this->db->table('tenants')->insert([
            'name' => 'Kopi Kuy Coffee Shop',
            'subdomain' => 'kopikuy',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $tenantId = $this->db->insertID();

        // 2. Create Roles
        $roles = [
            ['tenant_id' => null, 'name' => 'Super Admin', 'slug' => 'superadmin', 'is_system' => true],
            ['tenant_id' => $tenantId, 'name' => 'Owner', 'slug' => 'owner', 'is_system' => true],
            ['tenant_id' => $tenantId, 'name' => 'Manager', 'slug' => 'manager', 'is_system' => true],
            ['tenant_id' => $tenantId, 'name' => 'Cashier', 'slug' => 'cashier', 'is_system' => true],
        ];
        foreach ($roles as $role) {
            $role['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('roles')->insert($role);
        }

        // 3. Create Store
        $this->db->table('stores')->insert([
            'tenant_id' => $tenantId,
            'name' => 'Kopi Kuy HQ',
            'address' => 'Jl. Sudirman No. 123, Jakarta',
            'phone' => '021-5551234',
            'email' => 'hq@kopikuy.com',
            'currency' => 'IDR',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $storeId = $this->db->insertID();

        // 4. Create 2 Branches
        $branches = [
            ['name' => 'Cabang Sudirman', 'code' => 'SDR', 'address' => 'Jl. Sudirman No. 123'],
            ['name' => 'Cabang Kemang', 'code' => 'KMG', 'address' => 'Jl. Kemang Raya No. 45'],
        ];
        $branchIds = [];
        foreach ($branches as $branch) {
            $branch['store_id'] = $storeId;
            $branch['is_active'] = true;
            $branch['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('branches')->insert($branch);
            $branchIds[] = $this->db->insertID();
        }

        // Create warehouses for each branch
        foreach ($branchIds as $branchId) {
            $this->db->table('warehouses')->insert([
                'branch_id' => $branchId,
                'name' => 'Main Warehouse',
                'type' => 'main',
                'is_default' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 5. Create Users (1 Owner + 5 Cashiers)
        $ownerRole = $this->db->table('roles')->where('slug', 'owner')->get()->getRow();
        $cashierRole = $this->db->table('roles')->where('slug', 'cashier')->get()->getRow();

        // Owner
        $this->db->table('users')->insert([
            'tenant_id' => $tenantId,
            'branch_id' => null,
            'username' => 'owner',
            'email' => 'owner@kopikuy.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'pin' => '1234',
            'full_name' => 'Budi Santoso',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $ownerId = $this->db->insertID();
        $this->db->table('user_roles')->insert([
            'user_id' => $ownerId,
            'role_id' => $ownerRole->id,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 5 Cashiers
        for ($i = 1; $i <= 5; $i++) {
            $branchId = $branchIds[($i - 1) % count($branchIds)];
            $this->db->table('users')->insert([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'username' => "kasir{$i}",
                'email' => "kasir{$i}@kopikuy.com",
                'password_hash' => password_hash('kasir123', PASSWORD_DEFAULT),
                'pin' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'full_name' => $faker->name(),
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $userId = $this->db->insertID();
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $cashierRole->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 6. Create Categories
        $categories = ['Kopi', 'Non-Kopi', 'Snack', 'Makanan'];
        $categoryIds = [];
        foreach ($categories as $cat) {
            $this->db->table('categories')->insert([
                'tenant_id' => $tenantId,
                'name' => $cat,
                'slug' => strtolower($cat),
                'sort_order' => count($categoryIds),
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $categoryIds[$cat] = $this->db->insertID();
        }

        // 7. Create 50 Products with Variants
        $coffeeProducts = [
            'Americano',
            'Cappuccino',
            'Latte',
            'Espresso',
            'Mocha',
            'Macchiato',
            'Affogato',
            'Cold Brew',
            'Kopi Susu',
            'Es Kopi'
        ];
        $nonCoffeeProducts = ['Matcha Latte', 'Teh Tarik', 'Chocolate', 'Milk Tea', 'Lemon Tea'];
        $snacks = ['Croissant', 'Roti Bakar', 'French Fries', 'Kentang Goreng', 'Cookies'];
        $foods = ['Nasi Goreng', 'Mie Goreng', 'Sandwich', 'Roti Panggang', 'Salad'];

        $allProducts = [];
        foreach ($coffeeProducts as $p)
            $allProducts[] = ['name' => $p, 'cat' => 'Kopi', 'price' => rand(18, 35) * 1000];
        foreach ($nonCoffeeProducts as $p)
            $allProducts[] = ['name' => $p, 'cat' => 'Non-Kopi', 'price' => rand(15, 28) * 1000];
        foreach ($snacks as $p)
            $allProducts[] = ['name' => $p, 'cat' => 'Snack', 'price' => rand(10, 20) * 1000];
        foreach ($foods as $p)
            $allProducts[] = ['name' => $p, 'cat' => 'Makanan', 'price' => rand(20, 45) * 1000];

        // Add more random products to reach 50
        for ($i = count($allProducts); $i < 50; $i++) {
            $allProducts[] = [
                'name' => 'Product ' . ($i + 1),
                'cat' => array_keys($categoryIds)[array_rand(array_keys($categoryIds))],
                'price' => rand(10, 50) * 1000
            ];
        }

        $variantIds = [];
        foreach ($allProducts as $idx => $prod) {
            $sku = 'SKU-' . str_pad($idx + 1, 5, '0', STR_PAD_LEFT);
            $this->db->table('products')->insert([
                'tenant_id' => $tenantId,
                'category_id' => $categoryIds[$prod['cat']],
                'name' => $prod['name'],
                'slug' => url_title($prod['name'], '-', true),
                'sku' => $sku,
                'unit' => 'pcs',
                'track_stock' => true,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $productId = $this->db->insertID();

            $this->db->table('product_variants')->insert([
                'product_id' => $productId,
                'sku' => $sku,
                'cost_price' => $prod['price'] * 0.4,
                'selling_price' => $prod['price'],
                'stock_qty' => rand(50, 200),
                'min_stock' => 10,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $variantIds[] = $this->db->insertID();
        }

        // 8. Create Membership Tiers
        $tiers = [
            ['name' => 'Bronze', 'slug' => 'bronze', 'min_points' => 0, 'discount_percent' => 0, 'point_multiplier' => 1],
            ['name' => 'Silver', 'slug' => 'silver', 'min_points' => 500, 'discount_percent' => 5, 'point_multiplier' => 1.25],
            ['name' => 'Gold', 'slug' => 'gold', 'min_points' => 2000, 'discount_percent' => 10, 'point_multiplier' => 1.5],
        ];
        $tierIds = [];
        foreach ($tiers as $tier) {
            $tier['tenant_id'] = $tenantId;
            $tier['is_active'] = true;
            $tier['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('membership_tiers')->insert($tier);
            $tierIds[] = $this->db->insertID();
        }

        // 9. Create 200 Customers
        $customerIds = [];
        for ($i = 1; $i <= 200; $i++) {
            $tierId = $tierIds[array_rand($tierIds)];
            $this->db->table('customers')->insert([
                'tenant_id' => $tenantId,
                'tier_id' => $tierId,
                'code' => 'CUST-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'gender' => $faker->randomElement(['male', 'female']),
                'total_points' => rand(0, 1000),
                'total_spent' => rand(100000, 5000000),
                'visit_count' => rand(1, 50),
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $customerIds[] = $this->db->insertID();
        }

        // 10. Create Chart of Accounts (Accounting)
        $accounts = [
            ['code' => '1100', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Bank', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense'],
        ];
        foreach ($accounts as $acc) {
            $acc['tenant_id'] = $tenantId;
            $acc['is_active'] = true;
            $acc['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('accounts')->insert($acc);
        }

        echo "Demo data seeded successfully!\n";
        echo "- 1 Tenant, 1 Store, 2 Branches\n";
        echo "- 1 Owner + 5 Cashiers\n";
        echo "- 4 Categories, 50 Products\n";
        echo "- 3 Membership Tiers, 200 Customers\n";
        echo "- 7 Chart of Accounts\n";
        echo "\nLogin credentials:\n";
        echo "  Owner: owner / password123\n";
        echo "  Cashier: kasir1 / kasir123 (kasir1-5 available)\n";
    }
}
