<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('early_bird_price')->nullable()->after('price');
            $table->timestamp('early_bird_ends_at')->nullable()->after('early_bird_price');
            $table->unsignedInteger('presale_price')->nullable()->after('early_bird_ends_at');
            $table->timestamp('presale_ends_at')->nullable()->after('presale_price');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['early_bird_price', 'early_bird_ends_at', 'presale_price', 'presale_ends_at']);
        });
    }
};
