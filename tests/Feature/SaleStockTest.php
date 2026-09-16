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
}
