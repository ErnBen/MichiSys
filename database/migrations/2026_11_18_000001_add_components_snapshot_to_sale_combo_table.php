<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_combo', function (Blueprint $table): void {
            $table->json('components_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_combo', function (Blueprint $table): void {
            $table->dropColumn('components_snapshot');
        });
    }
};
