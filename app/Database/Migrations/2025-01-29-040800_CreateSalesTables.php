<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalesTables extends Migration
{
    public function up()
    {
        // Sales table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'cashier_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'transaction_date' => ['type' => 'DATETIME'],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'change_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'points_earned' => ['type' => 'INT', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'completed', 'voided', 'refunded'], 'default' => 'draft'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_number');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('cashier_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sales');

        // Sale Items
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sale_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'quantity' => ['type' => 'INT'],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sale_items');

        // Payments
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sale_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'payment_method' => ['type' => 'ENUM', 'constraint' => ['cash', 'card', 'ewallet', 'qris', 'transfer', 'deposit']],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'reference_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gateway_response' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'completed', 'failed'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payments');

        // Receipts
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sale_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'receipt_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'format' => ['type' => 'ENUM', 'constraint' => ['thermal', 'pdf'], 'default' => 'thermal'],
            'content' => ['type' => 'LONGTEXT', 'null' => true],
            'printed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('receipt_number');
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('receipts');

        // Refunds
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sale_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'refund_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'processed_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'completed'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('refund_number');
        $this->forge->addForeignKey('sale_id', 'sales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('processed_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('refunds');

        // FK for voucher_redemptions
        $this->db->query('ALTER TABLE voucher_redemptions ADD CONSTRAINT fk_vr_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE voucher_redemptions DROP FOREIGN KEY fk_vr_sale');
        $this->forge->dropTable('refunds');
        $this->forge->dropTable('receipts');
        $this->forge->dropTable('payments');
        $this->forge->dropTable('sale_items');
        $this->forge->dropTable('sales');
    }
}
