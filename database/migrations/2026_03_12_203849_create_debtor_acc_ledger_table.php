<?php
/*
 * (c) 2026 - Created by Antigravity AI
 * Project: h-rims
 * File: 2026_03_12_203849_create_debtor_acc_ledger_table.php
 */

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
        if (!Schema::hasTable('debtor_acc_ledger')) {
            Schema::create('debtor_acc_ledger', function (Blueprint $table) {
                $table->integer('budget_year')->index()->comment('ปีงบประมาณ');
                $table->integer('month_no')->comment('ใช้เรียง Tab เดือน (1-12 ตามปีงบประมาณ)');
                $table->string('acc_code', 50)->index()->comment('รหัสผังบัญชี');
                
                $table->primary(['budget_year', 'month_no', 'acc_code']);

                $table->char('vst_month', 7)->comment('เดือน/ปีใช้ดึงข้อมูล (YYYY-MM)');
                $table->string('acc_name', 255)->comment('ชื่อผังบัญชี');
                
                // ยอดเงิน
                $table->decimal('balance_old', 15, 2)->default(0)->comment('ยอดยกมา');
                $table->decimal('debt_new', 15, 2)->default(0)->comment('ยอดตั้งหนี้ในเดือน');
                $table->decimal('debt_receive', 15, 2)->default(0)->comment('ยอดรับชำระในเดือน');
                $table->decimal('debt_adj_dec', 15, 2)->default(0)->comment('ยอดปรับลดในเดือน');
                $table->decimal('debt_adj_inc', 15, 2)->default(0)->comment('ยอดปรับเพิ่มในเดือน');
                $table->decimal('balance_total', 15, 2)->default(0)->comment('ยอดคงเหลือยกไป');
                $table->text('adj_note')->nullable()->comment('หมายเหตุการปรับปรุง');
                
                // Aging (อายุหนี้)
                $table->decimal('aging_90', 15, 2)->default(0)->comment('<= 90 วัน');
                $table->decimal('aging_365', 15, 2)->default(0)->comment('91 - 365 วัน');
                $table->decimal('aging_over', 15, 2)->default(0)->comment('> 365 วัน');
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debtor_acc_ledger');
    }
};
