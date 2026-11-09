<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_number', 100)->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('received_quantity');
            $table->unsignedInteger('available_quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('source', 30);
            $table->timestamps();
            $table->index(['business_id', 'product_id', 'expires_at']);
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->string('lot_number', 100)->nullable();
            $table->date('expires_at')->nullable();
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
        });

        $now = now();

        foreach (DB::table('products')->where('stock', '>', 0)->orderBy('id')->get() as $product) {
            DB::table('inventory_lots')->insert([
                'business_id' => $product->business_id,
                'product_id' => $product->id,
                'lot_number' => "LOTE-INICIAL-{$product->id}",
                'expires_at' => $product->fecha_vencimiento,
                'received_quantity' => $product->stock,
                'available_quantity' => $product->stock,
                'unit_cost' => $product->cost ?? 0,
                'source' => 'opening',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('sales')->select('id')->orderBy('id')->get() as $sale) {
            DB::table('inventory_movements')
                ->whereNull('sale_id')
                ->where('type', 'out')
                ->whereIn('note', [
                    "Venta #{$sale->id}",
                    "Venta de combo #{$sale->id}",
                    "Venta combo #{$sale->id}",
                ])
                ->update(['sale_id' => $sale->id]);
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'This migration records lot balances and expiry history; use a separate data-preservation procedure before removing it.'
        );
    }
};
