<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keranjang multi-event tidak punya satu event tunggal.
        // event_id tetap dipertahankan (diisi event pertama) demi kompatibilitas view lama.
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable(false)->change();
        });
    }
};