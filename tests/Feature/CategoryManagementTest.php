<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_activated_and_deactivated_without_breaking_product_relations(): void
    {
        $owner = User::factory()->create(['role' => 'dueno']);

        $this->actingAs($owner)
            ->post(route('categories.store'), [
                'name' => 'Alimentos',
                'description' => 'Productos para mascotas',
                'active' => '1',
            ])
            ->assertRedirect(route('categories.index'));

        $category = Category::where('name', 'Alimentos')->firstOrFail();
        $this->assertTrue($category->active);

        $this->put(route('categories.update', $category), [
            'name' => $category->name,
            'description' => $category->description,
            'active' => '0',
        ])->assertRedirect(route('categories.index'));
        $this->assertFalse($category->fresh()->active);

        $this->put(route('categories.toggle', $category))->assertRedirect(route('categories.index'));
        $this->assertTrue($category->fresh()->active);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Alimento balanceado',
            'price' => 100,
            'cost' => 50,
            'stock' => 1,
            'stock_minimo' => 0,
            'active' => true,
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'active' => 0]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id]);
    }
}
