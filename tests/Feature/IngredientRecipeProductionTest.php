<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Production;
use App\Models\Recipe;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientRecipeProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_recipe_and_produce_from_fefo_ingredient_lots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Preparaciones', 'slug' => 'preparaciones', 'active' => true]);
        $finishedProduct = $this->createProduct($category, 'Producto final', 'product', 'piece');
        $ingredient = $this->createProduct($category, 'Harina', 'ingredient', 'g');
        $this->get(route('recipes.create'))->assertOk();
        app(InventoryService::class)->receive(
            $ingredient,
            1000,
            'Compra de prueba',
            $admin->id,
            'purchase',
            'HAR-01',
            today()->addDays(30)->toDateString(),
            0.1255
        );

        $this->post(route('recipes.store'), [
            'product_id' => $finishedProduct->id,
            'yield_quantity' => 2,
            'items' => [[
                'ingredient_id' => $ingredient->id,
                'quantity' => 200,
            ]],
        ])->assertRedirect(route('recipes.index'));

        $recipe = Recipe::firstOrFail();
        $this->get(route('recipes.index'))->assertOk();
        $this->get(route('recipes.edit', $recipe))->assertOk();
        $this->assertSame('g', $recipe->items->first()->unit);
        $this->get(route('productions.create'))->assertOk();
        $this->post(route('productions.store'), [
            'recipe_id' => $recipe->id,
            'quantity' => 4,
            'lot_number' => 'PROD-04',
            'expires_at' => today()->addDays(90)->toDateString(),
        ])->assertRedirect();

        $production = Production::firstOrFail();
        $this->get(route('productions.show', $production))->assertOk();
        $this->assertSame(4, $finishedProduct->fresh()->stock);
        $this->assertSame(600, $ingredient->fresh()->stock);
        $this->assertSame('50.20', $production->total_cost);
        $this->assertDatabaseHas('production_items', [
            'production_id' => $production->id,
            'product_id' => $ingredient->id,
            'quantity' => 400,
            'unit' => 'g',
            'total_cost' => 50.2,
        ]);
        $this->assertDatabaseHas('inventory_lots', [
            'product_id' => $finishedProduct->id,
            'production_id' => $production->id,
            'received_quantity' => 4,
            'lot_number' => 'PROD-04',
            'source' => 'production',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $ingredient->id,
            'production_id' => $production->id,
            'quantity' => 400,
            'type' => 'production',
        ]);
        $this->assertSame('0.00', $finishedProduct->fresh()->cost);
    }

    public function test_production_rolls_back_when_only_expired_ingredient_stock_is_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Preparaciones', 'slug' => 'preparaciones', 'active' => true]);
        $finishedProduct = $this->createProduct($category, 'Producto final', 'product', 'piece');
        $ingredient = $this->createProduct($category, 'Leche', 'ingredient', 'ml');
        app(InventoryService::class)->receive(
            $ingredient,
            500,
            'Lote vencido de prueba',
            $admin->id,
            'purchase',
            'LECHE-01',
            today()->subDay()->toDateString(),
            0.02
        );
        $recipe = Recipe::create([
            'product_id' => $finishedProduct->id,
            'yield_quantity' => 1,
        ]);
        $recipe->items()->create([
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->name,
            'quantity' => 250,
            'unit' => 'ml',
        ]);

        $this->post(route('productions.store'), [
            'recipe_id' => $recipe->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('stock');

        $this->assertSame(500, $ingredient->fresh()->stock);
        $this->assertSame(0, $finishedProduct->fresh()->stock);
        $this->assertSame(0, Production::count());
        $this->assertSame(0, InventoryLot::where('product_id', $finishedProduct->id)->count());
        $this->assertSame(0, InventoryMovement::where('type', 'production')->count());
    }

    private function createProduct(Category $category, string $name, string $kind, string $unit): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'sku' => strtoupper(str_replace(' ', '-', $name)),
            'price' => 0,
            'cost' => 0,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
            'kind' => $kind,
            'unit' => $unit,
        ]);
    }
}
