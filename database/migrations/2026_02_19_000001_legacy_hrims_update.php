<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create fdh_claim_status table
        if (!Schema::hasTable('fdh_claim_status')) {
            Schema::create('fdh_claim_status', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('hn', 50);
                $table->string('seq', 50)->nullable();
                $table->string('an', 50)->nullable();
                $table->string('hcode', 10);
                $table->string('status', 50);
                $table->string('process_status', 10)->nullable();
                $table->string('status_message_th', 255)->nullable();
                $table->string('stm_period', 50)->nullable();
                $table->timestamps();
                $table->index('hn', 'idx_hn');
                $table->index('an', 'idx_an');
            });
        }

        // 2. Structural updates for existing tables
        $tables = [
            'lookup_icode' => [
                ['name' => 'ems', 'type' => 'string', 'length' => 1, 'after' => 'kidney'],
            ],
            'lookup_ward' => [
                ['name' => 'ward_normal', 'type' => 'string', 'length' => 1, 'after' => 'ward_name'],
                ['name' => 'bed_qty', 'type' => 'integer', 'unsigned' => true, 'after' => 'ward_homeward'],
            ],
            'lookup_hospcode' => [
                ['name' => 'created_at', 'type' => 'timestamp', 'after' => 'in_province'],
                ['name' => 'updated_at', 'type' => 'timestamp', 'after' => 'created_at'],
            ],
            // STM Tables (standard pattern)
            'stm_lgo' => $this->getStdStmCols('stm_filename'),
            'stm_lgo_kidney' => $this->getStdStmCols('stm_filename'),
            'stm_ofc' => $this->getStdStmCols('stm_filename'),
            'stm_ofc_kidney' => $this->getStdStmCols('hdflag'),
            'stm_ucs' => $this->getStdStmCols('stm_filename'),
            'stm_ucs_kidney' => $this->getStdStmCols('stm_filename'),
            'stm_sss_kidney' => [
                ['name' => 'stm_filename', 'type' => 'string', 'length' => 100, 'after' => 'id'],
                ['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'stm_filename'],
                ['name' => 'pt_name', 'type' => 'string', 'length' => 100, 'after' => 'hn'],
                ['name' => 'receive_no', 'type' => 'string', 'length' => 20, 'after' => 'hdflag'],
                ['name' => 'receipt_date', 'type' => 'date', 'after' => 'receive_no', 'nullable' => true],
                ['name' => 'receipt_by', 'type' => 'string', 'length' => 100, 'after' => 'receipt_date', 'nullable' => true],
            ],
            // Excel staging tables
            'stm_lgo_kidneyexcel' => [['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id']],
            'stm_lgoexcel' => [['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id']],
            'stm_ofcexcel' => [['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id']],
            'stm_ucs_kidneyexcel' => [['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id']],
            'stm_ucsexcel' => [['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id']],
            // Debtor tables
            'debtor_1102050101_209' => [
                ['name' => 'receive', 'type' => 'double', 'length' => [15, 2], 'after' => 'status'],
                ['name' => 'repno', 'type' => 'string', 'length' => 15, 'after' => 'receive'],
            ],
            'debtor_1102050101_216' => [
                ['name' => 'ppfs', 'type' => 'double', 'length' => [15, 2], 'after' => 'anywhere'],
            ],
            'debtor_1102050101_309' => [
                ['name' => 'other', 'type' => 'double', 'length' => [15, 2], 'after' => 'rcpt_money'],
                ['name' => 'ppfs', 'type' => 'double', 'length' => [15, 2], 'after' => 'kidney'],
            ],
        ];

        foreach ($tables as $tableName => $columns) {
            if (!Schema::hasTable($tableName))
                continue;

            Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName) {
                foreach ($columns as $col) {
                    $columnName = $col['name'];
                    $type = $col['type'];
                    $after = $col['after'] ?? null;
                    $length = $col['length'] ?? null;
                    $unsigned = $col['unsigned'] ?? false;
                    $nullable = $col['nullable'] ?? true;

                    // Skip if modification not needed or use raw SQL for complex modifications if necessary
                    // For simplicity, we add if missing, or use change() if present
                    $field = null;
                    if ($type == 'string')
                        $field = $table->string($columnName, $length);
                    elseif ($type == 'integer')
                        $field = $table->integer($columnName, false, $unsigned);
                    elseif ($type == 'timestamp')
                        $field = $table->timestamp($columnName);
                    elseif ($type == 'date')
                        $field = $table->date($columnName);
                    elseif ($type == 'double') {
                        $field = $table->double($columnName);
                    }

                    if ($field) {
                        if ($nullable)
                            $field->nullable();
                        if ($after && Schema::hasColumn($tableName, $after))
                            $field->after($after);

                        if (Schema::hasColumn($tableName, $columnName)) {
                            $field->change();
                        }
                    }
                }
            });
        }
    }

    private function getStdStmCols($afterStart)
    {
        return [
            ['name' => 'round_no', 'type' => 'string', 'length' => 30, 'after' => 'id'],
            ['name' => 'receive_no', 'type' => 'string', 'length' => 20, 'after' => $afterStart],
            ['name' => 'receipt_date', 'type' => 'date', 'after' => 'receive_no', 'nullable' => true],
            ['name' => 'receipt_by', 'type' => 'string', 'length' => 100, 'after' => 'receipt_date', 'nullable' => true],
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fdh_claim_status');
    }
};
