<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('cajero')->change();
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'dueno']);

        Schema::table('businesses', function (Blueprint $table): void {
            $table->string('ruc', 50)->nullable();
            $table->text('description')->nullable();
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->index(['business_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['business_id', 'cancelled_at']);
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });

        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropColumn(['ruc', 'description']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('cajero')->change();
        });
    }
};
