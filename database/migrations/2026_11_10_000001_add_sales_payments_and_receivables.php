<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->string('payment_entity', 120)->nullable();
            $table->dateTime('payment_paid_at')->nullable();
            $table->text('payment_notes')->nullable();
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change_due', 12, 2)->nullable();
            $table->string('idempotency_key', 36)->nullable()->unique();
        });

        Schema::create('receivables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->unique()->constrained('sales')->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->date('due_date')->nullable();
            $table->text('observations')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['business_id', 'status', 'due_date']);
            $table->index(['business_id', 'client_id']);
        });

        Schema::create('receivable_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('receivable_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('reference', 120)->nullable();
            $table->string('entity', 120)->nullable();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 36)->nullable()->unique();
            $table->dateTime('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'paid_at']);
            $table->index(['receivable_id', 'reversed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');
        Schema::dropIfExists('receivables');

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_entity',
                'payment_paid_at',
                'payment_notes',
                'cash_received',
                'change_due',
                'idempotency_key',
            ]);
        });
    }
};
