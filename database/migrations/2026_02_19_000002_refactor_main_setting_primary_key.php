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
            // Check if 'id' column exists before trying to drop it
            if (Schema::hasColumn('main_setting', 'id')) {
                Schema::table('main_setting', function (Blueprint $table) {
                    // In some DBs we need to drop primary key first
                    // But 'id' is often the primary key.
                    // To be safe and compatible with SQLite/MySQL/PostgreSQL:
                    $table->dropColumn('id');
                });
            }

            // Setting 'name' as primary key. 
            // We use raw SQL to ensure compatibility if Blueprint's primary() fails on existing columns
            // Actually, in Laravel we can do:
            Schema::table('main_setting', function (Blueprint $table) {
                $table->string('name', 100)->change()->primary();
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
                $table->dropPrimary(['name']);
                $table->id()->first();
            });
        }
    }
};
