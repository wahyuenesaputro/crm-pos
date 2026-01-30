<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventoryTable extends Migration
{
    public function up()
    {
        // Branch Stock table (current stock per branch/warehouse)
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'quantity' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'reserved_qty' => [
                'type' => 'INT',
                'default' => 0,
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
        $this->forge->addUniqueKey(['warehouse_id', 'variant_id']);
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branch_stock');

        // Inventory Movements table (stock ledger)
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'reference_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'reference_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'movement_type' => [
                'type' => 'ENUM',
                'constraint' => ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out'],
            ],
            'quantity' => [
                'type' => 'INT',
            ],
            'stock_before' => [
                'type' => 'INT',
            ],
            'stock_after' => [
                'type' => 'INT',
            ],
            'unit_cost' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('warehouse_id');
        $this->forge->addKey('variant_id');
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('inventory_movements');

        // Stock Transfers table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'transfer_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'from_warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'to_warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'in_transit', 'completed', 'cancelled'],
                'default' => 'pending',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'approved_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('transfer_number');
        $this->forge->addForeignKey('from_warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('to_warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stock_transfers');

        // Stock Transfer Items table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'transfer_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'quantity' => [
                'type' => 'INT',
            ],
            'received_qty' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('transfer_id');
        $this->forge->addForeignKey('transfer_id', 'stock_transfers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stock_transfer_items');

        // Stock Takes table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'warehouse_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'take_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'in_progress', 'completed', 'cancelled'],
                'default' => 'draft',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('take_number');
        $this->forge->addForeignKey('warehouse_id', 'warehouses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('stock_takes');

        // Stock Take Items table
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'stock_take_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'variant_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'system_qty' => [
                'type' => 'INT',
            ],
            'counted_qty' => [
                'type' => 'INT',
                'null' => true,
            ],
            'variance' => [
                'type' => 'INT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('stock_take_id');
        $this->forge->addForeignKey('stock_take_id', 'stock_takes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('stock_take_items');
    }

    public function down()
    {
        $this->forge->dropTable('stock_take_items');
        $this->forge->dropTable('stock_takes');
        $this->forge->dropTable('stock_transfer_items');
        $this->forge->dropTable('stock_transfers');
        $this->forge->dropTable('inventory_movements');
        $this->forge->dropTable('branch_stock');
    }
}
