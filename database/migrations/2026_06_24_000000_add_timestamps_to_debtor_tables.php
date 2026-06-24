<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dbName = DB::getDatabaseName();
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . $dbName;
        
        foreach ($tables as $tableObj) {
            if (!isset($tableObj->$key)) {
                // Fallback in case key is named differently in some PHP environments
                $vars = get_object_vars($tableObj);
                $table = reset($vars);
            } else {
                $table = $tableObj->$key;
            }

            // Target all debtor tables except tracking tables
            if (str_starts_with($table, 'debtor_') && !str_contains($table, '_tracking')) {
                Schema::table($table, function (Blueprint $tableCol) use ($table) {
                    if (!Schema::hasColumn($table, 'created_at')) {
                        $tableCol->timestamp('created_at')->nullable();
                    }
                    if (!Schema::hasColumn($table, 'updated_at')) {
                        $tableCol->timestamp('updated_at')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . $dbName;
        
        foreach ($tables as $tableObj) {
            if (!isset($tableObj->$key)) {
                $vars = get_object_vars($tableObj);
                $table = reset($vars);
            } else {
                $table = $tableObj->$key;
            }

            if (str_starts_with($table, 'debtor_') && !str_contains($table, '_tracking')) {
                Schema::table($table, function (Blueprint $tableCol) use ($table) {
                    if (Schema::hasColumn($table, 'created_at')) {
                        $tableCol->dropColumn('created_at');
                    }
                    if (Schema::hasColumn($table, 'updated_at')) {
                        $tableCol->dropColumn('updated_at');
                    }
                });
            }
        }
    }
};
