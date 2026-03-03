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
        Schema::create('eclaim_status', function (Blueprint $table) {
            $table->id();
            $table->string('hospcode', 5)->nullable()->index('idx_hospcode')->comment('รหัสสถานพยาบาล 5 หลัก');
            $table->string('eclaim_no', 100)->nullable()->comment('เลขที่เคลม (E-Claim)');
            $table->string('patient_type', 10)->nullable()->comment('ประเภทผู้ป่วย (OPD/IPD)');
            $table->string('hipdata', 255)->nullable()->comment('สิทธิการรักษา');
            $table->string('cid', 13)->nullable()->index('idx_cid')->comment('เลขบัตรประชาชน');
            $table->string('ptname', 255)->nullable()->comment('ชื่อ-สกุล');
            $table->string('hn', 50)->nullable()->index('idx_hn')->comment('HN');
            $table->string('an', 50)->nullable()->index('idx_an')->comment('AN');
            $table->date('vstdate')->nullable()->index('idx_vstdate')->comment('วันที่เข้ารับบริการ/Admit');
            $table->time('vsttime')->nullable()->index('idx_vsttime')->comment('เวลาเข้ารับบริการ/Admit');
            $table->date('dchdate')->nullable()->index('idx_dchdate')->comment('วันที่จำหน่าย');
            $table->time('dchtime')->nullable()->index('idx_dchtime')->comment('เวลาจำหน่าย');
            $table->string('status', 100)->nullable()->comment('สถานะการส่งข้อมูล');
            $table->string('recorder', 255)->nullable()->comment('ผู้บันทึก/ส่ง');
            $table->string('tran_id', 100)->nullable()->comment('รหัส Tran_id สทป.');
            $table->double('net_charge', 15, 2)->nullable()->comment('ค่าใช้จ่ายสุทธิ');
            $table->double('claim_amount', 15, 2)->nullable()->comment('ยอดที่ขอเก็บ');
            $table->string('rep', 100)->nullable()->comment('REP');
            $table->string('stm', 100)->nullable()->comment('STM');
            $table->string('seq', 100)->nullable()->comment('SEQ');
            $table->text('check_detail')->nullable()->comment('รายละเอียดการตรวจสอบ');
            $table->text('deny_warning')->nullable()->comment('คำเตือน/ปฏิเสธจ่าย');
            $table->string('channel', 50)->nullable()->comment('ช่องทางที่นำเข้า (API/Excel)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eclaim_status');
    }
};
