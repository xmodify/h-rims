<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('budget_year')) {
            Schema::create('budget_year', function (Blueprint $table) {
                $table->string('LEAVE_YEAR_ID', 10)->default('');
                $table->string('LEAVE_YEAR_NAME', 255)->nullable()->default('');
                $table->date('DATE_BEGIN')->nullable();
                $table->date('DATE_END')->nullable();
                $table->string('ACTIVE')->nullable()->default('False');
                $table->integer('DAY_PER_YEAR')->nullable()->default(10);
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_103')) {
            Schema::create('debtor_1102050101_103', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_109')) {
            Schema::create('debtor_1102050101_109', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_201')) {
            Schema::create('debtor_1102050101_201', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 15)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_202')) {
            Schema::create('debtor_1102050101_202', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_203')) {
            Schema::create('debtor_1102050101_203', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 15)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_209')) {
            Schema::create('debtor_1102050101_209', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('pp')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 15)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_216')) {
            Schema::create('debtor_1102050101_216', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('cr')->nullable();
                $table->double('anywhere')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_217')) {
            Schema::create('debtor_1102050101_217', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('cr')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_301')) {
            Schema::create('debtor_1102050101_301', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_302')) {
            Schema::create('debtor_1102050101_302', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_303')) {
            Schema::create('debtor_1102050101_303', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_304')) {
            Schema::create('debtor_1102050101_304', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('income_pttype')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_307')) {
            Schema::create('debtor_1102050101_307', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_308')) {
            Schema::create('debtor_1102050101_308', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('income_pttype')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_309')) {
            Schema::create('debtor_1102050101_309', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('kidney')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_310')) {
            Schema::create('debtor_1102050101_310', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_401')) {
            Schema::create('debtor_1102050101_401', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('ofc')->nullable();
                $table->double('kidney')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_402')) {
            Schema::create('debtor_1102050101_402', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_501')) {
            Schema::create('debtor_1102050101_501', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_502')) {
            Schema::create('debtor_1102050101_502', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_503')) {
            Schema::create('debtor_1102050101_503', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_504')) {
            Schema::create('debtor_1102050101_504', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_701')) {
            Schema::create('debtor_1102050101_701', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_702')) {
            Schema::create('debtor_1102050101_702', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_703')) {
            Schema::create('debtor_1102050101_703', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050101_704')) {
            Schema::create('debtor_1102050101_704', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_106')) {
            Schema::create('debtor_1102050102_106', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->string('mobile_phone_number', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('paid_money')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_106_tracking')) {
            Schema::create('debtor_1102050102_106_tracking', function (Blueprint $table) {
                $table->integer('tracking_id');
                $table->string('vn', 100)->nullable()->index();
                $table->date('tracking_date')->nullable();
                $table->string('tracking_type', 100)->nullable();
                $table->string('tracking_no', 100)->nullable();
                $table->string('tracking_officer', 100)->nullable();
                $table->string('tracking_note', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_107')) {
            Schema::create('debtor_1102050102_107', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->string('mobile_phone_number', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('paid_money')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_107_tracking')) {
            Schema::create('debtor_1102050102_107_tracking', function (Blueprint $table) {
                $table->integer('tracking_id');
                $table->string('vn', 100)->nullable();
                $table->string('an', 100)->nullable()->index();
                $table->date('tracking_date')->nullable();
                $table->string('tracking_type', 100)->nullable();
                $table->string('tracking_no', 100)->nullable();
                $table->string('tracking_officer', 100)->nullable();
                $table->string('tracking_note', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_108')) {
            Schema::create('debtor_1102050102_108', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_109')) {
            Schema::create('debtor_1102050102_109', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_110')) {
            Schema::create('debtor_1102050102_110', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('ofc')->nullable();
                $table->double('kidney')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_111')) {
            Schema::create('debtor_1102050102_111', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_602')) {
            Schema::create('debtor_1102050102_602', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->string('charge', 100)->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_603')) {
            Schema::create('debtor_1102050102_603', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->string('status', 100)->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_801')) {
            Schema::create('debtor_1102050102_801', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('lgo')->nullable();
                $table->double('kidney')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_802')) {
            Schema::create('debtor_1102050102_802', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_803')) {
            Schema::create('debtor_1102050102_803', function (Blueprint $table) {
                $table->string('vn', 100);
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('ptname', 100)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('ofc')->nullable();
                $table->double('kidney')->nullable();
                $table->double('ppfs')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->date('charge_date')->nullable();
                $table->string('charge_no', 100)->nullable();
                $table->double('charge')->nullable();
                $table->date('receive_date')->nullable();
                $table->string('receive_no', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('status', 100)->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('debtor_1102050102_804')) {
            Schema::create('debtor_1102050102_804', function (Blueprint $table) {
                $table->string('an', 100);
                $table->string('vn', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('ptname', 100)->nullable();
                $table->date('regdate')->nullable();
                $table->time('regtime')->nullable();
                $table->date('dchdate')->nullable();
                $table->time('dchtime')->nullable();
                $table->string('pttype', 100)->nullable();
                $table->string('hospmain', 100)->nullable();
                $table->string('hipdata_code', 100)->nullable();
                $table->string('pdx', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('income')->nullable();
                $table->double('rcpt_money')->nullable();
                $table->double('kidney')->nullable();
                $table->double('other')->nullable();
                $table->double('debtor')->nullable();
                $table->double('debtor_change')->nullable();
                $table->string('status', 100)->nullable();
                $table->double('receive')->nullable();
                $table->string('repno')->nullable();
                $table->string('debtor_lock', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('drugcat_aipn')) {
            Schema::create('drugcat_aipn', function (Blueprint $table) {
                $table->integer('id')->nullable();
                $table->string('Hospdcode', 255)->nullable();
                $table->string('Prodcat', 255)->nullable();
                $table->string('Tmtid', 255)->nullable();
                $table->string('Specprep', 255)->nullable();
                $table->string('Genname', 255)->nullable();
                $table->string('Tradename', 255)->nullable();
                $table->string('Dsfcode', 255)->nullable();
                $table->string('Dosefm', 255)->nullable();
                $table->string('Strength', 255)->nullable();
                $table->string('Content', 255)->nullable();
                $table->double('UnitPrice')->nullable();
                $table->string('Distrb', 255)->nullable();
                $table->string('Manuf', 255)->nullable();
                $table->string('Ised', 255)->nullable();
                $table->string('Ndc24', 255)->nullable();
                $table->string('Packsize', 255)->nullable();
                $table->string('Packprice', 255)->nullable();
                $table->string('Updateflag', 255)->nullable();
                $table->dateTime('DateChange')->nullable();
                $table->dateTime('DateUpdate')->nullable();
                $table->dateTime('DateEffect')->nullable();
                $table->dateTime('DateChk')->nullable();
                $table->string('Rp', 255)->nullable();
            });
        }

        if (!Schema::hasTable('drugcat_nhso')) {
            Schema::create('drugcat_nhso', function (Blueprint $table) {
                $table->string('hospdrugcode', 255)->nullable();
                $table->string('productcat', 255)->nullable();
                $table->string('tmtid', 255)->nullable();
                $table->string('specprep', 255)->nullable();
                $table->string('genericname', 255)->nullable();
                $table->string('tradename', 255)->nullable();
                $table->string('dfscode', 255)->nullable();
                $table->string('dosageform', 255)->nullable();
                $table->string('strength', 255)->nullable();
                $table->string('content', 255)->nullable();
                $table->double('unitprice')->nullable();
                $table->string('distributor', 255)->nullable();
                $table->string('manufacturer', 255)->nullable();
                $table->string('ised', 255)->nullable();
                $table->string('ndc24', 255)->nullable();
                $table->string('packsize', 255)->nullable();
                $table->string('packprice', 255)->nullable();
                $table->string('updateflag', 255)->nullable();
                $table->date('datechange')->nullable();
                $table->date('dateupdate')->nullable();
                $table->date('dateeffective')->nullable();
                $table->string('ised_approved', 255)->nullable();
                $table->string('ndc24_approved', 255)->nullable();
                $table->date('date_approved')->nullable();
                $table->string('ised_status', 255)->nullable();
                $table->string('stm_filename', 255)->nullable();
            });
        }

        if (!Schema::hasTable('fdh_claim_status')) {
            Schema::create('fdh_claim_status', function (Blueprint $table) {
                $table->id();
                $table->string('hn', 50)->index();
                $table->string('seq', 50)->nullable();
                $table->string('an', 50)->nullable()->index();
                $table->string('hcode', 10);
                $table->string('status', 50);
                $table->string('process_status', 10)->nullable();
                $table->string('status_message_th', 255)->nullable();
                $table->string('stm_period', 50)->nullable();
                $table->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP');
            });
        }

        if (!Schema::hasTable('labcat_ss')) {
            Schema::create('labcat_ss', function (Blueprint $table) {
                $table->string('lccode', 255)->nullable();
                $table->string('billgroup', 255)->nullable();
                $table->string('cscode', 255)->nullable();
                $table->string('tmlt', 255)->nullable();
                $table->string('loinc', 255)->nullable();
                $table->string('panel', 255)->nullable();
                $table->string('name', 255)->nullable();
                $table->string('sflag', 255)->nullable();
                $table->string('chargecat', 255)->nullable();
                $table->string('unitprice', 255)->nullable();
                $table->string('benefitplan', 255)->nullable();
                $table->string('reimbprice', 255)->nullable();
                $table->string('updateflag', 255)->nullable();
                $table->string('updatebeg', 255)->nullable();
                $table->string('updateend', 255)->nullable();
                $table->string('rpdatebeg', 255)->nullable();
                $table->string('rpdateend', 255)->nullable();
                $table->string('dateupd', 255)->nullable();
            });
        }

        if (!Schema::hasTable('labcat_tmt')) {
            Schema::create('labcat_tmt', function (Blueprint $table) {
                $table->string('lab_code', 255)->nullable();
                $table->string('lab_name', 255)->nullable();
                $table->string('lab_type', 255)->nullable();
                $table->string('location', 255)->nullable();
                $table->string('lab_price', 255)->nullable();
                $table->string('component', 255)->nullable();
                $table->string('scale', 255)->nullable();
                $table->string('specimen', 255)->nullable();
                $table->string('unit', 255)->nullable();
                $table->string('method', 255)->nullable();
                $table->string('cscode', 255)->nullable();
                $table->string('tmlt', 255)->nullable();
                $table->string('loinc_num', 255)->nullable();
            });
        }

        if (!Schema::hasTable('lookup_adp_sss')) {
            Schema::create('lookup_adp_sss', function (Blueprint $table) {
                $table->string('billgrcs', 255)->nullable();
                $table->string('code', 255)->index();
                $table->string('unit', 255)->nullable();
                $table->double('rate')->nullable();
                $table->double('rate2')->nullable();
                $table->string('desc', 255)->nullable();
                $table->date('daterev')->nullable();
                $table->date('dateeff')->nullable();
                $table->date('dateexp')->nullable();
                $table->date('lastupd')->nullable();
                $table->string('dxcond', 255)->nullable();
                $table->string('note', 255)->nullable();
            });
        }

        if (!Schema::hasTable('lookup_hospcode')) {
            Schema::create('lookup_hospcode', function (Blueprint $table) {
                $table->string('hospcode', 9);
                $table->string('hospcode_name', 100)->nullable();
                $table->string('hmain_ucs', 1)->nullable();
                $table->string('hmain_sss', 1)->nullable();
                $table->string('in_province', 1)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('lookup_icd10')) {
            Schema::create('lookup_icd10', function (Blueprint $table) {
                $table->string('icd10', 100);
                $table->string('pp', 1)->nullable();
                $table->string('ods', 1)->nullable();
                $table->string('ods_p', 1)->nullable();
                $table->string('kidney', 1)->nullable();
                $table->string('hiv', 1)->nullable();
                $table->string('tb', 1)->nullable();
            });
        }

        if (!Schema::hasTable('lookup_icd9_sss')) {
            Schema::create('lookup_icd9_sss', function (Blueprint $table) {
                $table->string('code', 255);
                $table->string('desc', 255)->nullable();
                $table->string('ortime', 255)->nullable();
            });
        }

        if (!Schema::hasTable('lookup_icode')) {
            Schema::create('lookup_icode', function (Blueprint $table) {
                $table->string('icode', 10);
                $table->string('name', 200)->nullable();
                $table->string('nhso_adp_code', 100)->nullable();
                $table->string('uc_cr', 1)->nullable()->index();
                $table->string('ppfs', 1)->nullable()->index();
                $table->string('herb32', 1)->nullable()->index();
                $table->string('kidney', 1)->nullable()->index();
                $table->string('ems', 1)->nullable()->index();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('lookup_ward')) {
            Schema::create('lookup_ward', function (Blueprint $table) {
                $table->string('ward', 2);
                $table->string('ward_name', 100)->nullable();
                $table->string('ward_normal', 1)->nullable();
                $table->string('ward_m', 1)->nullable();
                $table->string('ward_f', 1)->nullable();
                $table->string('ward_vip', 1)->nullable();
                $table->string('ward_lr', 1)->nullable();
                $table->string('ward_homeward', 1)->nullable();
                $table->integer('bed_qty')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('main_setting')) {
            Schema::create('main_setting', function (Blueprint $table) {
                $table->id();
                $table->string('name_th', 100)->nullable();
                $table->string('name', 100)->nullable();
                $table->string('value', 100)->nullable();
            });
        }

        if (!Schema::hasTable('nhso_endpoint')) {
            Schema::create('nhso_endpoint', function (Blueprint $table) {
                $table->id();
                $table->string('cid', 13)->nullable()->default(0)->index();
                $table->string('firstName', 255)->nullable();
                $table->string('lastName', 255)->nullable();
                $table->string('mainInscl', 255)->nullable();
                $table->string('mainInsclName', 255)->nullable();
                $table->string('subInscl', 255)->nullable();
                $table->string('subInsclName', 255)->nullable();
                $table->dateTime('serviceDateTime')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->string('sourceChannel', 255)->nullable();
                $table->string('claimCode', 255)->nullable()->index();
                $table->string('claimType', 255)->nullable();
            });
        }

        if (!Schema::hasTable('stm_lgo')) {
            Schema::create('stm_lgo', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('tran_id', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->string('dep', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->double('compensate_treatment')->nullable();
                $table->double('compensate_nhso')->nullable();
                $table->string('error_code', 100)->nullable();
                $table->string('fund', 100)->nullable();
                $table->string('service_type', 100)->nullable();
                $table->string('refer', 100)->nullable();
                $table->string('have_rights', 100)->nullable();
                $table->string('use_rights', 100)->nullable();
                $table->string('main_rights', 100)->nullable();
                $table->string('secondary_rights', 100)->nullable();
                $table->string('href', 100)->nullable();
                $table->string('hcode', 100)->nullable();
                $table->string('prov1', 100)->nullable();
                $table->string('hospcode', 100)->nullable();
                $table->string('hospname', 100)->nullable();
                $table->string('proj', 100)->nullable();
                $table->string('pa', 100)->nullable();
                $table->string('drg', 100)->nullable();
                $table->string('rw', 100)->nullable();
                $table->double('charge_treatment')->nullable();
                $table->double('charge_pp')->nullable();
                $table->string('withdraw', 100)->nullable();
                $table->string('non_withdraw', 100)->nullable();
                $table->string('pay', 100)->nullable();
                $table->double('payrate')->nullable();
                $table->string('delay', 100)->nullable();
                $table->string('delay_percent', 100)->nullable();
                $table->string('ccuf', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('act')->nullable();
                $table->double('case_iplg')->nullable();
                $table->double('case_oplg')->nullable();
                $table->double('case_palg')->nullable();
                $table->double('case_inslg')->nullable();
                $table->double('case_otlg')->nullable();
                $table->double('case_pp')->nullable();
                $table->double('case_drug')->nullable();
                $table->string('deny_iplg', 100)->nullable();
                $table->string('deny_oplg', 100)->nullable();
                $table->string('deny_palg', 100)->nullable();
                $table->string('deny_inslg', 100)->nullable();
                $table->string('deny_otlg', 100)->nullable();
                $table->string('ors', 100)->nullable();
                $table->string('va', 100)->nullable();
                $table->string('audit_results', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_lgo_kidney')) {
            Schema::create('stm_lgo_kidney', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->string('dep', 100)->nullable();
                $table->date('datetimeadm')->nullable()->index();
                $table->double('compensate_kidney')->nullable();
                $table->string('note', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_lgo_kidneyexcel')) {
            Schema::create('stm_lgo_kidneyexcel', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('pt_name', 100)->nullable();
                $table->string('dep', 100)->nullable();
                $table->date('datetimeadm')->nullable();
                $table->string('compensate_kidney', 100)->nullable();
                $table->string('note', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_lgoexcel')) {
            Schema::create('stm_lgoexcel', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('tran_id', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->string('dep', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->double('compensate_treatment')->nullable();
                $table->double('compensate_nhso')->nullable();
                $table->string('error_code', 100)->nullable();
                $table->string('fund', 100)->nullable();
                $table->string('service_type', 100)->nullable();
                $table->string('refer', 100)->nullable();
                $table->string('have_rights', 100)->nullable();
                $table->string('use_rights', 100)->nullable();
                $table->string('main_rights', 100)->nullable();
                $table->string('secondary_rights', 100)->nullable();
                $table->string('href', 100)->nullable();
                $table->string('hcode', 100)->nullable();
                $table->string('prov1', 100)->nullable();
                $table->string('hospcode', 100)->nullable();
                $table->string('hospname', 100)->nullable();
                $table->string('proj', 100)->nullable();
                $table->string('pa', 100)->nullable();
                $table->string('drg', 100)->nullable();
                $table->string('rw', 100)->nullable();
                $table->double('charge_treatment')->nullable();
                $table->double('charge_pp')->nullable();
                $table->string('withdraw', 100)->nullable();
                $table->string('non_withdraw', 100)->nullable();
                $table->string('pay', 100)->nullable();
                $table->double('payrate')->nullable();
                $table->string('delay', 100)->nullable();
                $table->string('delay_percent', 100)->nullable();
                $table->string('ccuf', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('act')->nullable();
                $table->double('case_iplg')->nullable();
                $table->double('case_oplg')->nullable();
                $table->double('case_palg')->nullable();
                $table->double('case_inslg')->nullable();
                $table->double('case_otlg')->nullable();
                $table->double('case_pp')->nullable();
                $table->double('case_drug')->nullable();
                $table->string('deny_iplg', 100)->nullable();
                $table->string('deny_oplg', 100)->nullable();
                $table->string('deny_palg', 100)->nullable();
                $table->string('deny_inslg', 100)->nullable();
                $table->string('deny_otlg', 100)->nullable();
                $table->string('ors', 100)->nullable();
                $table->string('va', 100)->nullable();
                $table->string('audit_results', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ofc')) {
            Schema::create('stm_ofc', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->string('projcode', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('charge')->nullable();
                $table->double('act')->nullable();
                $table->double('receive_room')->nullable();
                $table->double('receive_instument')->nullable();
                $table->double('receive_drug')->nullable();
                $table->double('receive_treatment')->nullable();
                $table->double('receive_car')->nullable();
                $table->double('receive_waitdch')->nullable();
                $table->double('receive_other')->nullable();
                $table->double('receive_total')->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ofc_cipn')) {
            Schema::create('stm_ofc_cipn', function (Blueprint $table) {
                $table->id();
                $table->string('stm_filename', 100)->nullable()->index();
                $table->string('round_no', 30)->nullable();
                $table->integer('rid')->nullable();
                $table->string('an', 15)->nullable()->index();
                $table->string('namepat', 100)->nullable();
                $table->date('datedsc')->nullable();
                $table->string('ptype', 5)->nullable();
                $table->string('drg', 10)->nullable();
                $table->double('adjrw')->nullable();
                $table->double('amreimb')->nullable();
                $table->double('amlim')->nullable();
                $table->double('pamreim')->nullable();
                $table->double('gtotal')->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ofc_csop')) {
            Schema::create('stm_ofc_csop', function (Blueprint $table) {
                $table->id();
                $table->string('stm_filename', 100)->nullable();
                $table->string('round_no', 30)->nullable();
                $table->string('stm_type', 20)->nullable();
                $table->string('hcode', 10)->nullable();
                $table->string('hname', 100)->nullable();
                $table->string('acc_period', 20)->nullable();
                $table->string('sys', 10)->nullable();
                $table->string('station', 10)->nullable();
                $table->string('hreg', 10)->nullable();
                $table->string('hn', 20)->nullable()->index();
                $table->string('pt_name', 190)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->string('invno', 50)->nullable()->unique();
                $table->double('amount')->nullable();
                $table->double('paid')->nullable();
                $table->string('extp_code', 10)->nullable();
                $table->double('extp_amount')->nullable();
                $table->string('rid', 20)->nullable();
                $table->string('cstat', 10)->nullable();
                $table->string('hdflag', 10)->nullable();
                $table->string('receive_no', 30)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ofc_kidney')) {
            Schema::create('stm_ofc_kidney', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('stmdoc', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('stm_type', 20)->nullable();
                $table->string('hcode', 100)->nullable();
                $table->string('hname', 100)->nullable();
                $table->string('acc_period', 20)->nullable();
                $table->string('sys', 10)->nullable();
                $table->string('station', 100)->nullable();
                $table->string('hreg', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('pt_name', 190)->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable();
                $table->string('invno', 100)->nullable();
                $table->dateTime('dttran')->nullable();
                $table->double('amount')->nullable();
                $table->string('paid', 100)->nullable();
                $table->string('rid', 100)->nullable();
                $table->string('hdflag', 255)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ofcexcel')) {
            Schema::create('stm_ofcexcel', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->string('projcode', 100)->nullable();
                $table->string('adjrw', 100)->nullable();
                $table->double('charge')->nullable();
                $table->double('act')->nullable();
                $table->double('receive_room')->nullable();
                $table->double('receive_instument')->nullable();
                $table->double('receive_drug')->nullable();
                $table->double('receive_treatment')->nullable();
                $table->double('receive_car')->nullable();
                $table->double('receive_waitdch')->nullable();
                $table->double('receive_other')->nullable();
                $table->double('receive_total')->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_sss_kidney')) {
            Schema::create('stm_sss_kidney', function (Blueprint $table) {
                $table->id();
                $table->string('stm_filename', 100)->nullable();
                $table->string('round_no', 30)->nullable();
                $table->string('hcode', 100)->nullable();
                $table->string('hname', 100)->nullable();
                $table->string('stmdoc', 100)->nullable();
                $table->string('station', 100)->nullable();
                $table->string('hreg', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('invno', 100)->nullable();
                $table->dateTime('dttran')->nullable();
                $table->date('vstdate')->nullable();
                $table->time('vsttime')->nullable();
                $table->double('amount')->nullable();
                $table->double('epopay')->nullable();
                $table->double('epoadm')->nullable();
                $table->string('paid', 100)->nullable();
                $table->string('rid', 100)->nullable();
                $table->string('hdflag', 255)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ucs')) {
            Schema::create('stm_ucs', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('tran_id', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->string('maininscl', 100)->nullable();
                $table->string('projcode', 100)->nullable();
                $table->double('charge')->nullable();
                $table->double('fund_ip_act')->nullable();
                $table->string('fund_ip_adjrw', 100)->nullable();
                $table->string('fund_ip_ps', 100)->nullable();
                $table->string('fund_ip_ps2', 100)->nullable();
                $table->string('fund_ip_ccuf', 100)->nullable();
                $table->string('fund_ip_adjrw2', 100)->nullable();
                $table->double('fund_ip_payrate')->nullable();
                $table->double('fund_ip_salary')->nullable();
                $table->double('fund_compensate_salary')->nullable();
                $table->double('receive_op')->nullable();
                $table->double('receive_ip_compensate_cal')->nullable();
                $table->double('receive_ip_compensate_pay')->nullable();
                $table->double('receive_hc_hc')->nullable();
                $table->double('receive_hc_drug')->nullable();
                $table->double('receive_ae_ae')->nullable();
                $table->double('receive_ae_drug')->nullable();
                $table->double('receive_inst')->nullable();
                $table->double('receive_dmis_compensate_cal')->nullable();
                $table->double('receive_dmis_compensate_pay')->nullable();
                $table->double('receive_dmis_drug')->nullable();
                $table->double('receive_palliative')->nullable();
                $table->double('receive_dmishd')->nullable();
                $table->double('receive_pp')->nullable();
                $table->double('receive_fs')->nullable();
                $table->double('receive_opbkk')->nullable();
                $table->double('receive_total')->nullable();
                $table->string('va', 100)->nullable();
                $table->string('covid', 100)->nullable();
                $table->string('resources', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ucs_kidney')) {
            Schema::create('stm_ucs_kidney', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->date('datetimeadm')->nullable()->index();
                $table->string('hd_type', 100)->nullable();
                $table->double('charge_total')->nullable();
                $table->double('receive_total')->nullable();
                $table->string('note', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->string('receive_no', 20)->nullable();
                $table->date('receipt_date')->nullable();
                $table->string('receipt_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ucs_kidneyexcel')) {
            Schema::create('stm_ucs_kidneyexcel', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('hn', 100)->nullable();
                $table->string('an', 100)->nullable();
                $table->string('cid', 100)->nullable();
                $table->string('pt_name', 100)->nullable();
                $table->date('datetimeadm')->nullable();
                $table->string('hd_type', 100)->nullable();
                $table->string('charge_total', 100)->nullable();
                $table->string('receive_total', 100)->nullable();
                $table->string('note', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('stm_ucsexcel')) {
            Schema::create('stm_ucsexcel', function (Blueprint $table) {
                $table->id();
                $table->string('round_no', 30)->nullable();
                $table->string('repno', 100)->nullable();
                $table->string('no', 100)->nullable();
                $table->string('tran_id', 100)->nullable();
                $table->string('hn', 100)->nullable()->index();
                $table->string('an', 100)->nullable()->index();
                $table->string('cid', 100)->nullable()->index();
                $table->string('pt_name', 100)->nullable();
                $table->dateTime('datetimeadm')->nullable();
                $table->date('vstdate')->nullable()->index();
                $table->time('vsttime')->nullable()->index();
                $table->dateTime('datetimedch')->nullable();
                $table->date('dchdate')->nullable()->index();
                $table->time('dchtime')->nullable()->index();
                $table->string('maininscl', 100)->nullable();
                $table->string('projcode', 100)->nullable();
                $table->double('charge')->nullable();
                $table->double('fund_ip_act')->nullable();
                $table->string('fund_ip_adjrw', 100)->nullable();
                $table->string('fund_ip_ps', 100)->nullable();
                $table->string('fund_ip_ps2', 100)->nullable();
                $table->string('fund_ip_ccuf', 100)->nullable();
                $table->string('fund_ip_adjrw2', 100)->nullable();
                $table->double('fund_ip_payrate')->nullable();
                $table->double('fund_ip_salary')->nullable();
                $table->double('fund_compensate_salary')->nullable();
                $table->double('receive_op')->nullable();
                $table->double('receive_ip_compensate_cal')->nullable();
                $table->double('receive_ip_compensate_pay')->nullable();
                $table->double('receive_hc_hc')->nullable();
                $table->double('receive_hc_drug')->nullable();
                $table->double('receive_ae_ae')->nullable();
                $table->double('receive_ae_drug')->nullable();
                $table->double('receive_inst')->nullable();
                $table->double('receive_dmis_compensate_cal')->nullable();
                $table->double('receive_dmis_compensate_pay')->nullable();
                $table->double('receive_dmis_drug')->nullable();
                $table->double('receive_palliative')->nullable();
                $table->double('receive_dmishd')->nullable();
                $table->double('receive_pp')->nullable();
                $table->double('receive_fs')->nullable();
                $table->double('receive_opbkk')->nullable();
                $table->double('receive_total')->nullable();
                $table->string('va', 100)->nullable();
                $table->string('covid', 100)->nullable();
                $table->string('resources', 100)->nullable();
                $table->string('stm_filename', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('subinscl')) {
            Schema::create('subinscl', function (Blueprint $table) {
                $table->string('code', 2)->default('');
                $table->string('name', 200)->nullable();
                $table->string('maininscl', 10)->nullable()->default('');
                $table->string('note', 100)->nullable();
            });
        }

    }

    public function down()
    {
        // Skipping drop to prevent data loss on existing tables
    }
};
