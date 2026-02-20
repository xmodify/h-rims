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
        if (Schema::hasTable('main_setting')) {
            // 1. Handle the 'id' column and its potential primary key
            if (Schema::hasColumn('main_setting', 'id')) {
                // MySQL doesn't allow dropping a primary key on an AUTO_INCREMENT column.
                // We must remove the auto_increment attribute first.
                DB::statement('ALTER TABLE main_setting MODIFY COLUMN id INT');

                Schema::table('main_setting', function (Blueprint $table) {
                    $indexes = Schema::getIndexes('main_setting');
                    $idIsPrimary = collect($indexes)->contains(fn($i) => $i['primary'] && in_array('id', $i['columns']));

                    if ($idIsPrimary) {
                        $table->dropPrimary();
                    }
                    $table->dropColumn('id');
                });
            }

            // 2. Set 'name' as primary key only if no primary key exists
            Schema::table('main_setting', function (Blueprint $table) {
                $indexes = Schema::getIndexes('main_setting');
                $hasPrimary = collect($indexes)->contains('primary', true);
                $nameIsPrimary = collect($indexes)->contains(fn($i) => $i['primary'] && in_array('name', $i['columns']));

                if (!$hasPrimary) {
                    $table->string('name', 100)->change()->primary();
                } elseif (!$nameIsPrimary) {
                    // There is a primary key but not on 'name'. 
                    // This scenario is unlikely if 'id' was the only other PK and we dropped it,
                    // but we handle it by dropping the current PK and adding it to 'name'.
                    $table->dropPrimary();
                    $table->string('name', 100)->change()->primary();
                } else {
                    // 'name' is already the primary key, just ensure the length is correct
                    $table->string('name', 100)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('main_setting')) {
            Schema::table('main_setting', function (Blueprint $table) {
                $indexes = Schema::getIndexes('main_setting');
                $nameIsPrimary = collect($indexes)->contains(fn($i) => $i['primary'] && in_array('name', $i['columns']));

                if ($nameIsPrimary) {
                    $table->dropPrimary();
                }

                if (!Schema::hasColumn('main_setting', 'id')) {
                    $table->id()->first();
                }
            });
        }
    }
};
