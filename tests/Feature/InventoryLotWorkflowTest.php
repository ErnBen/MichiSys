<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLotWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_consumes_the_earliest_expiring_lots_and_cancellation_restores_them()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Alimento',
            'sku' => 'ALIM-01',
            'price' => 100,
            'cost' => 50,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
        ]);
        $provider = Provider::create(['name' => 'Proveedor']);

        $this->actingAs($admin);
        $this->recordLot($provider, $product, 'LOTE-TARDE', today()->addDays(60)->toDateString(), 5);
        $this->recordLot($provider, $product, 'LOTE-PRONTO', today()->addDays(10)->toDateString(), 3);

        $this->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 4],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();
        $earlierLot = InventoryLot::where('product_id', $product->id)
            ->where('lot_number', 'LOTE-PRONTO')
            ->firstOrFail();
        $laterLot = InventoryLot::where('product_id', $product->id)
            ->where('lot_number', 'LOTE-TARDE')
            ->firstOrFail();

        $this->assertSame(0, $earlierLot->available_quantity);
        $this->assertSame(4, $laterLot->available_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'sale_id' => $sale->id,
            'lot_id' => $earlierLot->id,
            'quantity' => 3,
            'type' => 'out',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'sale_id' => $sale->id,
            'lot_id' => $laterLot->id,
            'quantity' => 1,
            'type' => 'out',
        ]);

        $this->delete(route('sales.destroy', $sale))->assertRedirect(route('sales.index'));

        $this->assertSame(3, $earlierLot->fresh()->available_quantity);
        $this->assertSame(5, $laterLot->fresh()->available_quantity);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_sales_reject_expired_lots_without_changing_stock()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto vencido',
            'sku' => 'VENC-01',
            'price' => 100,
            'cost' => 50,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
        ]);
        $provider = Provider::create(['name' => 'Proveedor']);

        $this->actingAs($admin);
        $this->recordLot($provider, $product, 'VENCIDO', today()->subDay()->toDateString(), 1);
        $this->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
        ])->assertSessionHasErrors('stock');

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(
            1,
            InventoryLot::where('product_id', $product->id)->sum('available_quantity')
        );
    }

    private function recordLot(
        Provider $provider,
        Product $product,
        string $lotNumber,
        string $expiresAt,
        int $quantity
    ): void {
        $this->post(route('purchases.store'), [
            'provider_id' => $provider->id,
            'purchase_date' => today()->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => $quantity,
                'purchase_unit' => 'piece',
                'unit_cost' => '50.00',
                'lot_number' => $lotNumber,
                'expires_at' => $expiresAt,
            ]],
        ])->assertRedirect();
    }
}
