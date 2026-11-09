<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('kind', 20)->default('product')->index();
            $table->string('unit', 10)->default('piece');
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->decimal('purchased_quantity', 12, 3)->default(0);
            $table->string('purchase_unit', 10)->default('piece');
            $table->string('base_unit', 10)->default('piece');
            $table->decimal('base_unit_cost', 14, 6)->default(0);
        });

        Schema::table('inventory_lots', function (Blueprint $table): void {
            $table->string('base_unit', 10)->default('piece');
            $table->decimal('base_unit_cost', 14, 6)->default(0);
        });

        DB::table('purchase_items')->update([
            'purchased_quantity' => DB::raw('quantity'),
            'base_unit_cost' => DB::raw('unit_cost'),
        ]);
        DB::table('inventory_lots')->update([
            'base_unit_cost' => DB::raw('unit_cost'),
        ]);

        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('yield_quantity')->default(1);
            $table->text('instructions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'product_id']);
        });

        Schema::create('recipe_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained('products')->restrictOnDelete();
            $table->string('ingredient_name');
            $table->unsignedInteger('quantity');
            $table->string('unit', 10);
            $table->timestamps();
            $table->unique(['recipe_id', 'ingredient_id']);
        });

        Schema::create('productions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->dateTime('produced_at');
            $table->string('lot_number', 100)->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['business_id', 'produced_at']);
        });

        Schema::create('production_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->string('unit', 10);
            $table->decimal('base_unit_cost', 14, 6)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['business_id', 'production_id']);
        });

        Schema::table('inventory_lots', function (Blueprint $table): void {
            $table->foreignId('production_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('production_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        throw new LogicException(
            'This migration stores recipes and production cost history; use a separate data-preservation procedure before removing it.'
        );
    }
};
