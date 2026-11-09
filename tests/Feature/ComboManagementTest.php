<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Combo;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_activate_and_deactivate_a_combo_with_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $product = $this->createProduct();
        $combo = $this->createCombo($admin, false);
        $combo->products()->attach($product->id, [
            'quantity' => 1,
            'business_id' => $admin->business_id,
        ]);

        $this->get(route('combos.index'))
            ->assertOk()
            ->assertSee('Activar')
            ->assertSee('Eliminar');

        $this->put(route('combos.toggle', $combo))->assertRedirect(route('combos.index'));
        $this->assertDatabaseHas('combos', ['id' => $combo->id, 'active' => true]);

        $this->put(route('combos.toggle', $combo))->assertRedirect(route('combos.index'));
        $this->assertDatabaseHas('combos', ['id' => $combo->id, 'active' => false]);
    }

    public function test_empty_combo_cannot_be_activated_and_listing_explains_why(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $combo = $this->createCombo($admin, false);

        $this->get(route('combos.index'))
            ->assertOk()
            ->assertSee('Sin productos')
            ->assertSee('Agrega al menos un producto para poder activarlo.')
            ->assertSee('Edítalo y agrega productos antes de activarlo');

        $this->put(route('combos.toggle', $combo))
            ->assertRedirect(route('combos.index'))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('combos', ['id' => $combo->id, 'active' => false]);
    }

    public function test_combo_with_sales_cannot_be_deleted_or_silently_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $combo = $this->createCombo($admin, true);
        $sale = Sale::create([
            'user_id' => $admin->id,
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 19,
            'total' => 119,
        ]);
        SaleCombo::create([
            'sale_id' => $sale->id,
            'combo_id' => $combo->id,
            'quantity' => 1,
            'price' => 100,
            'subtotal' => 100,
        ]);

        $this->get(route('combos.index'))
            ->assertOk()
            ->assertSee('Eliminar (1 venta(s))')
            ->assertSee('Se conserva para no alterar las ventas históricas.');

        $this->delete(route('combos.destroy', $combo))
            ->assertRedirect(route('combos.index'))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('combos', ['id' => $combo->id, 'active' => true]);
        $this->assertDatabaseHas('sale_combo', ['sale_id' => $sale->id, 'combo_id' => $combo->id]);
    }

    public function test_combo_without_sales_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $combo = $this->createCombo($admin, false);

        $this->delete(route('combos.destroy', $combo))
            ->assertRedirect(route('combos.index'));

        $this->assertDatabaseMissing('combos', ['id' => $combo->id]);
    }

    public function test_editing_combo_accepts_checked_active_checkbox_and_saves_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $product = $this->createProduct();
        $combo = $this->createCombo($admin, false);

        $this->put(route('combos.update', $combo), [
            'name' => 'Combo actualizado',
            'description' => 'Descripción',
            'price' => 150,
            'active' => '1',
            'products' => [$product->id],
            'quantities' => [$product->id => 2],
        ])->assertRedirect(route('combos.index'));

        $this->assertDatabaseHas('combos', [
            'id' => $combo->id,
            'name' => 'Combo actualizado',
            'active' => true,
        ]);
        $this->assertDatabaseHas('combo_product', [
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'business_id' => $admin->business_id,
        ]);
    }

    private function createCombo(User $admin, bool $active): Combo
    {
        return Combo::create([
            'name' => 'Combo de prueba',
            'price' => 100,
            'active' => $active,
            'business_id' => $admin->business_id,
        ]);
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Categoría',
            'slug' => 'categoria',
            'active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de prueba',
            'sku' => 'TEST-COMBO-01',
            'price' => 50,
            'cost' => 10,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
        ]);
    }
}
