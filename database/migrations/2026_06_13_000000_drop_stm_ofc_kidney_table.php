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
        Schema::dropIfExists('stm_ofc_kidney');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback action needed since the table is obsolete.
    }
};
