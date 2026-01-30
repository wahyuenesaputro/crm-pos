<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAccountingTables extends Migration
{
    public function up()
    {
        // Chart of Accounts
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'type' => ['type' => 'ENUM', 'constraint' => ['asset', 'liability', 'equity', 'revenue', 'expense']],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'is_active' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('accounts');

        // Journal Entries
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'journal_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'transaction_date' => ['type' => 'DATE'],
            'reference_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'total_debit' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'total_credit' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'posted', 'void'], 'default' => 'posted'],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('journal_number');
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('journals');

        // Journal Lines
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'journal_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'account_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'debit' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'credit' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('journal_id', 'journals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('journal_lines');
    }

    public function down()
    {
        $this->forge->dropTable('journal_lines');
        $this->forge->dropTable('journals');
        $this->forge->dropTable('accounts');
    }
}
