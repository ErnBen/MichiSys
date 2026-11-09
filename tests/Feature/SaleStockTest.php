<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_stock_and_creates_inventory_movement()
    {
        $user = User::factory()->create();

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Hamburguesa',
            'sku' => 'HMB-01',
            'description' => 'Prueba',
            'price' => 15000,
            'cost' => 8000,
            'stock' => 20,
            'stock_minimo' => 5,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 3],
            'discount' => 0,
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('sales', ['user_id' => $user->id]);

        $product->refresh();
        $this->assertEquals(17, $product->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => 3,
            'type' => 'out'
        ]);
    }

    public function test_low_stock_is_reported_when_stock_less_or_equal_minimum()
    {
        $user = User::factory()->create();

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Coca-Cola',
            'sku' => 'CC-01',
            'description' => 'Prueba',
            'price' => 7000,
            'cost' => 3500,
            'stock' => 5,
            'stock_minimo' => 5,
            'active' => true,
        ]);

        // Sell 1 -> stock becomes 4 (below minimo)
        $response = $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'discount' => 0,
        ]);

        $response->assertRedirect(route('sales.index'));

        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSeeText('Productos bajo stock')
            ->assertSeeText('1');
    }

    public function test_inventory_input_increases_stock_and_records_a_movement()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de inventario',
            'sku' => 'INV-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 4,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($admin)->post(route('inventory.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'type' => 'in',
            'note' => 'Recepción de compra',
        ])->assertRedirect(route('inventory.index'));

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => 3,
            'type' => 'in',
            'note' => 'Recepción de compra',
        ]);
    }

    public function test_inventory_output_cannot_decrement_below_zero()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de inventario',
            'sku' => 'INV-02',
            'price' => 1000,
            'cost' => 500,
            'stock' => 2,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($admin)->post(route('inventory.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'type' => 'out',
        ])->assertRedirect();

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'out',
        ]);
    }

    public function test_merma_decreases_stock_and_records_a_waste_movement()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto dañado',
            'sku' => 'MERMA-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 3,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($admin)->post(route('inventory.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'type' => 'waste',
            'note' => 'Producto vencido',
        ])->assertRedirect(route('inventory.index'));

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'quantity' => 2,
            'type' => 'waste',
            'note' => 'Producto vencido',
        ]);
    }
}
