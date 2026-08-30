<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'allow_export_f16_eclaim')) {
                $table->string('allow_export_f16_eclaim', 1)->default('N')->after('allow_hosfin');
            }
            if (!Schema::hasColumn('users', 'allow_export_f16_fdh')) {
                $table->string('allow_export_f16_fdh', 1)->default('N')->after('allow_export_f16_eclaim');
            }
            if (!Schema::hasColumn('users', 'allow_export_ssop')) {
                $table->string('allow_export_ssop', 1)->default('N')->after('allow_export_f16_fdh');
            }
            if (!Schema::hasColumn('users', 'allow_export_aipn')) {
                $table->string('allow_export_aipn', 1)->default('N')->after('allow_export_ssop');
            }
            if (!Schema::hasColumn('users', 'allow_export_csop')) {
                $table->string('allow_export_csop', 1)->default('N')->after('allow_export_aipn');
            }
            if (!Schema::hasColumn('users', 'allow_export_cipn')) {
                $table->string('allow_export_cipn', 1)->default('N')->after('allow_export_csop');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'allow_export_f16_eclaim',
                'allow_export_f16_fdh',
                'allow_export_ssop',
                'allow_export_aipn',
                'allow_export_csop',
                'allow_export_cipn',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
