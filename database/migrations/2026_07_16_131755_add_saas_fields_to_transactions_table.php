<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();

            // Batas waktu reservasi kuota (15 menit). Lewat ini, reserved_stock dilepas.
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('expires_at');

            // Penanda idempotensi: stok hanya boleh dipotong sekali per transaksi.
            $table->boolean('stock_applied')->default(false)->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['expires_at', 'paid_at', 'stock_applied']);
        });
    }
};