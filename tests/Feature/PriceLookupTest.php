<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_cashier_can_view_only_active_sellable_products_from_their_business(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Alimento Michi',
            'sku' => 'ALIM-01',
            'price' => 15000,
            'cost' => 9876,
            'stock' => 10,
            'stock_minimo' => 2,
            'image' => 'products/alimento.jpg',
            'active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Producto inactivo',
            'sku' => 'INACT-01',
            'price' => 20000,
            'cost' => 10000,
            'stock' => 1,
            'stock_minimo' => 1,
            'active' => false,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Insumo interno',
            'sku' => 'INS-01',
            'price' => 0,
            'cost' => 1200,
            'stock' => 1,
            'stock_minimo' => 1,
            'active' => true,
            'kind' => 'ingredient',
        ]);

        $otherBusiness = Business::create(['name' => 'Tienda B', 'owner_name' => 'Admin B']);
        $otherAdmin = User::factory()->create(['business_id' => $otherBusiness->id, 'role' => 'admin']);
        $this->actingAs($otherAdmin);
        $otherCategory = Category::create(['name' => 'Otra categoría', 'slug' => 'otra', 'active' => true]);
        Product::create([
            'category_id' => $otherCategory->id,
            'name' => 'Producto de otro negocio',
            'sku' => 'AJENO-01',
            'price' => 50000,
            'cost' => 25000,
            'stock' => 1,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('prices.index', ['search' => 'ALIM-01']))
            ->assertOk()
            ->assertSeeText('Alimento Michi')
            ->assertSeeText('Alimentos')
            ->assertSeeText('Gs. 15.000')
            ->assertSee('products/alimento.jpg')
            ->assertDontSeeText('9876')
            ->assertDontSeeText('Producto inactivo')
            ->assertDontSeeText('Insumo interno')
            ->assertDontSeeText('Producto de otro negocio');

        $cashier = User::factory()->create(['business_id' => $business->id, 'role' => 'cajero']);
        $this->actingAs($cashier)->get(route('prices.index'))->assertOk()->assertSeeText('Alimento Michi');

        $this->assertSame($business->id, $product->business_id);
    }

    public function test_price_lookup_rejects_non_string_search_parameters(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('prices.index', ['search' => ['invalid']]))
            ->assertSessionHasErrors('search');
    }

    public function test_sales_product_and_client_searches_are_limited_and_scoped(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $cashier = User::factory()->create(['business_id' => $business->id, 'role' => 'cajero']);
        $this->actingAs($cashier);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Croquetas premium',
            'sku' => 'CROQ-SEARCH',
            'price' => 1000,
            'cost' => 500,
            'stock' => 4,
            'stock_minimo' => 1,
            'active' => true,
        ]);
        \App\Models\Client::create([
            'name' => 'Cliente buscable',
            'document' => 'DOC-555',
        ]);

        $this->getJson(route('sales.products.search', ['search' => 'CROQ-SEARCH']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.sellable_stock', 4);
        $this->getJson(route('sales.clients.search', ['search' => 'DOC-555']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cliente buscable');

        $otherBusiness = Business::create(['name' => 'Tienda B', 'owner_name' => 'Admin B']);
        $otherOwner = User::factory()->create(['business_id' => $otherBusiness->id, 'role' => 'dueno']);
        $this->actingAs($otherOwner);
        $otherCategory = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos-b', 'active' => true]);
        Product::create([
            'category_id' => $otherCategory->id,
            'name' => 'Croquetas ajenas',
            'sku' => 'CROQ-SEARCH-B',
            'price' => 1000,
            'cost' => 500,
            'stock' => 4,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $this->actingAs($cashier)
            ->getJson(route('sales.products.search', ['search' => 'CROQ-SEARCH']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['name' => 'Croquetas ajenas']);
    }

    public function test_inventory_filters_products_by_search_and_low_stock_without_cross_business_results(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $owner = User::factory()->create(['business_id' => $business->id, 'role' => 'dueno']);
        $this->actingAs($owner);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Croquetas con stock bajo',
            'sku' => 'LOW-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 1,
            'stock_minimo' => 3,
            'active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Croquetas con stock suficiente',
            'sku' => 'OK-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 10,
            'stock_minimo' => 3,
            'active' => true,
        ]);

        $this->get(route('products.index', ['search' => 'LOW-01', 'stock' => 'low']))
            ->assertOk()
            ->assertSeeText('Croquetas con stock bajo')
            ->assertDontSeeText('Croquetas con stock suficiente');
        $this->get(route('inventory.index', ['search' => 'LOW-01']))
            ->assertOk()
            ->assertSeeText('Croquetas con stock bajo')
            ->assertDontSeeText('Croquetas con stock suficiente');
    }
}
