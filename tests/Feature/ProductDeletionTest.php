<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_linked_to_sale_is_deactivated_instead_of_deleted()
    {
        $user = User::factory()->create();

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Del',
            'sku' => 'PD-01',
            'description' => 'Desc',
            'price' => 1000,
            'cost' => 500,
            'stock' => 10,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => null,
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 190,
            'total' => 1190,
        ]);

        SaleProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($user)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertFalse((bool) $product->active);
    }

    public function test_product_with_inventory_lots_is_deactivated_instead_of_deleted()
    {
        $user = User::factory()->create();

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Solo',
            'sku' => 'PS-01',
            'description' => 'Desc',
            'price' => 1200,
            'cost' => 600,
            'stock' => 5,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => false]);
        $this->assertDatabaseHas('inventory_lots', ['product_id' => $product->id]);
    }

    public function test_product_without_inventory_history_can_still_be_deleted()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto Vacío',
            'sku' => 'PV-01',
            'price' => 1200,
            'cost' => 600,
            'stock' => 0,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
