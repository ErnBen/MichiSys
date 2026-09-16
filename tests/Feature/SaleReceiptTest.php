<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_html_is_accessible()
    {
        $user = User::factory()->create();

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto A',
            'sku' => 'PA-01',
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

        $response = $this->actingAs($user)->get(route('sales.receipt', $sale));

        $response->assertStatus(200)->assertSeeText('Producto A');
    }

    public function test_receipt_pdf_downloads()
    {
        $user = User::factory()->create();

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto B',
            'sku' => 'PB-01',
            'description' => 'Desc',
            'price' => 2000,
            'cost' => 1000,
            'stock' => 10,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => null,
            'subtotal' => 2000,
            'discount' => 0,
            'tax' => 380,
            'total' => 2380,
        ]);

        SaleProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => 2000,
        ]);

        $response = $this->actingAs($user)->get(route('sales.receipt.pdf', $sale));

        $response->assertStatus(200);
        $this->assertStringContainsString('pdf', $response->headers->get('content-type') ?: '');
    }
}
