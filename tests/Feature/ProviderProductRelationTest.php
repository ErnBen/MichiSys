<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderProductRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_manage_associated_products(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::create([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
            'description' => 'Bebidas',
            'active' => true,
        ]);

        $productOne = Product::create([
            'category_id' => $category->id,
            'name' => 'Coca-Cola',
            'sku' => 'CC-001',
            'description' => null,
            'price' => 15.50,
            'cost' => 8.00,
            'stock' => 25,
            'stock_minimo' => 5,
            'fecha_vencimiento' => null,
            'active' => true,
        ]);

        $productTwo = Product::create([
            'category_id' => $category->id,
            'name' => 'Agua Mineral',
            'sku' => 'AG-001',
            'description' => null,
            'price' => 12.00,
            'cost' => 5.00,
            'stock' => 30,
            'stock_minimo' => 6,
            'fecha_vencimiento' => null,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('providers.store'), [
                'name' => 'Distribuidora M',
                'email' => 'contacto@distribuidora.com',
                'phone' => '5551234',
                'address' => 'San Miguel',
                'products' => [$productOne->id, $productTwo->id],
            ])
            ->assertRedirect(route('providers.index'));

        $provider = Provider::query()->where('name', 'Distribuidora M')->firstOrFail();

        $this->assertCount(2, $provider->products()->get());
        $this->assertTrue($provider->products()->whereKey($productOne->id)->exists());
        $this->assertTrue($provider->products()->whereKey($productTwo->id)->exists());
    }
}
