<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVoucherTables extends Migration
{
    public function up()
    {
        // Campaigns table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['discount', 'cashback', 'free_item', 'bundle'],
                'default' => 'discount',
            ],
            'start_date' => [
                'type' => 'DATETIME',
            ],
            'end_date' => [
                'type' => 'DATETIME',
            ],
            'rules' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'target_customers' => [
                'type' => 'ENUM',
                'constraint' => ['all', 'new', 'returning', 'tier', 'specific'],
                'default' => 'all',
            ],
            'target_tiers' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'target_branches' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'max_redemptions' => [
                'type' => 'INT',
                'null' => true,
            ],
            'current_redemptions' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey(['start_date', 'end_date']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('campaigns');

        // Vouchers table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'campaign_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'discount_type' => [
                'type' => 'ENUM',
                'constraint' => ['percentage', 'fixed', 'free_item'],
                'default' => 'percentage',
            ],
            'discount_value' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'min_purchase' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
            ],
            'max_discount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
            ],
            'usage_limit' => [
                'type' => 'INT',
                'null' => true,
            ],
            'usage_limit_per_customer' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'used_count' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'valid_from' => [
                'type' => 'DATETIME',
            ],
            'valid_to' => [
                'type' => 'DATETIME',
            ],
            'is_single_use' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('campaign_id');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['valid_from', 'valid_to']);
        $this->forge->addForeignKey('campaign_id', 'campaigns', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vouchers');

        // Voucher Redemptions table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'voucher_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'sale_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'customer_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'discount_applied' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('voucher_id');
        $this->forge->addKey('sale_id');
        $this->forge->addKey('customer_id');
        $this->forge->addForeignKey('voucher_id', 'vouchers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('voucher_redemptions');
    }

    public function down()
    {
        $this->forge->dropTable('voucher_redemptions');
        $this->forge->dropTable('vouchers');
        $this->forge->dropTable('campaigns');
    }
}
