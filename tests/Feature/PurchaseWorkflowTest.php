<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_purchase_increases_stock_and_preserves_its_historical_cost()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create([
            'name' => 'Alimentos',
            'slug' => 'alimentos',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Croquetas',
            'sku' => 'CROQ-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 3,
            'stock_minimo' => 1,
            'active' => true,
        ]);
        $provider = Provider::create(['name' => 'Distribuidora Central']);

        $this->actingAs($admin)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Registrar compra recibida');

        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'provider_id' => $provider->id,
            'purchase_date' => '2026-11-08',
            'invoice_number' => 'FAC-100',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'purchase_unit' => 'piece',
                'unit_cost' => '125.50',
                'lot_number' => 'LOT-CROQ-1',
                'expires_at' => '2027-01-15',
            ]],
        ]);

        $purchase = Purchase::firstOrFail();
        $response->assertRedirect(route('purchases.show', $purchase));
        $this->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee('Croquetas')
            ->assertSee('FAC-100');
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('500.00', $product->fresh()->cost);
        $this->assertSame('251.00', $purchase->total);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_name' => 'Croquetas',
            'quantity' => 2,
            'unit_cost' => 125.5,
            'subtotal' => 251,
            'lot_number' => 'LOT-CROQ-1',
            'business_id' => $admin->business_id,
        ]);
        $purchaseItem = $purchase->items()->firstOrFail();
        $this->assertSame('2027-01-15', $purchaseItem->expires_at->toDateString());
        $lot = InventoryLot::where('purchase_item_id', $purchaseItem->id)->firstOrFail();
        $this->assertSame('2027-01-15', $lot->expires_at->toDateString());
        $this->assertDatabaseHas('inventory_lots', [
            'product_id' => $product->id,
            'purchase_item_id' => $purchaseItem->id,
            'lot_number' => 'LOT-CROQ-1',
            'received_quantity' => 2,
            'available_quantity' => 2,
            'source' => 'purchase',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => 2,
            'type' => 'in',
            'note' => "Compra #{$purchase->id}",
        ]);
    }

    public function test_purchase_routes_are_restricted_to_administrators()
    {
        $cashier = User::factory()->create(['role' => 'cajero']);

        $this->actingAs($cashier)
            ->get(route('purchases.index'))
            ->assertForbidden();
    }

    public function test_purchase_cannot_use_another_business_provider()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create([
            'name' => 'Alimentos',
            'slug' => 'alimentos',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto propio',
            'sku' => 'PROPIO-01',
            'price' => 100,
            'cost' => 50,
            'stock' => 1,
            'stock_minimo' => 0,
            'active' => true,
        ]);
        $otherBusiness = Business::create([
            'name' => 'Otro negocio',
            'owner_name' => 'Otra administradora',
        ]);
        $otherAdmin = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'role' => 'admin',
        ]);
        $this->actingAs($otherAdmin);
        $otherProvider = Provider::create(['name' => 'Proveedor ajeno']);

        $this->actingAs($admin)->post(route('purchases.store'), [
            'provider_id' => $otherProvider->id,
            'purchase_date' => '2026-11-08',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'purchase_unit' => 'piece',
                'unit_cost' => '10.00',
            ]],
        ])->assertSessionHasErrors('provider_id');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_converts_kilograms_to_grams_and_keeps_the_entered_cost(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Insumos', 'slug' => 'insumos', 'active' => true]);
        $ingredient = Product::create([
            'category_id' => $category->id,
            'name' => 'Harina',
            'sku' => 'HARINA-01',
            'price' => 0,
            'cost' => 0,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
            'kind' => 'ingredient',
            'unit' => 'g',
        ]);
        $provider = Provider::create(['name' => 'Distribuidora de insumos']);

        $this->post(route('purchases.store'), [
            'provider_id' => $provider->id,
            'purchase_date' => today()->toDateString(),
            'items' => [[
                'product_id' => $ingredient->id,
                'quantity' => '2.500',
                'purchase_unit' => 'kg',
                'unit_cost' => '125.50',
            ]],
        ])->assertRedirect();

        $purchaseItem = PurchaseItem::firstOrFail();
        $this->assertSame(2500, $ingredient->fresh()->stock);
        $this->assertSame(2500, $purchaseItem->quantity);
        $this->assertSame('2.500', $purchaseItem->purchased_quantity);
        $this->assertSame('kg', $purchaseItem->purchase_unit);
        $this->assertSame('g', $purchaseItem->base_unit);
        $this->assertSame('0.125500', $purchaseItem->base_unit_cost);
        $this->assertSame('313.75', $purchaseItem->subtotal);
    }

    public function test_purchase_rejects_units_incompatible_with_the_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Botella',
            'sku' => 'BOT-01',
            'price' => 0,
            'cost' => 0,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
            'unit' => 'piece',
        ]);
        $provider = Provider::create(['name' => 'Proveedor']);

        $this->post(route('purchases.store'), [
            'provider_id' => $provider->id,
            'purchase_date' => today()->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'purchase_unit' => 'kg',
                'unit_cost' => '25.00',
            ]],
        ])->assertSessionHasErrors('items.0.purchase_unit');

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseCount('purchases', 0);
    }
}
