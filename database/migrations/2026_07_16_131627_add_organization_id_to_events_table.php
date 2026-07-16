<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Nullable supaya event lama tetap selamat; OrganizationSeeder akan mengisinya.
            $table->foreignId('organization_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();

            // Kuota yang sedang "dikunci" oleh checkout yang belum lunas.
            // Stok tersedia = stock - reserved_stock
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('reserved_stock');
        });
    }
};