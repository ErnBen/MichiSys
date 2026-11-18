<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Combo;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $productSequence = 0;

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

    public function test_combo_create_edit_and_detail_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $product = $this->createProduct();
        $combo = $this->createCombo($admin, true);
        $combo->products()->attach($product->id, [
            'quantity' => 2,
            'business_id' => $admin->business_id,
        ]);

        $this->get(route('combos.create'))
            ->assertOk()
            ->assertSee('form-actions', false)
            ->assertSee('combo-product-search');
        $this->get(route('combos.edit', $combo))
            ->assertOk()
            ->assertSee('combo-selected-products');
        $this->get(route('combos.show', $combo))
            ->assertOk()
            ->assertSeeText('Detalle del combo')
            ->assertSeeText('Producto de prueba');
    }

    public function test_combo_sale_uses_final_price_and_records_component_snapshot_and_movements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(20, 'Pan');
        $patty = $this->createProduct(20, 'Hamburguesa', null, 'ingredient');
        $soda = $this->createProduct(20, 'Gaseosa');
        $combo = $this->createCombo($admin, true, 'Combo completo', 17000);
        $this->attachComponents($combo, $admin, [
            $bread->id => 2,
            $patty->id => 1,
            $soda->id => 1,
        ]);

        $this->post(route('sales.store'), [
            'combo_ids' => [$combo->id],
            'combo_quantities' => [$combo->id => 5],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();
        $saleCombo = SaleCombo::firstOrFail();
        $this->assertSame(85000.0, (float) $sale->total);
        $this->assertSame(0.0, (float) $sale->tax);
        $this->assertSame([
            'combo_name' => 'Combo completo',
            'components' => [
                ['product_id' => $bread->id, 'name' => 'Pan', 'quantity' => 2, 'unit_label' => 'Unidades'],
                ['product_id' => $patty->id, 'name' => 'Hamburguesa', 'quantity' => 1, 'unit_label' => 'Unidades'],
                ['product_id' => $soda->id, 'name' => 'Gaseosa', 'quantity' => 1, 'unit_label' => 'Unidades'],
            ],
        ], $saleCombo->components_snapshot);
        $this->assertSame(10, $bread->fresh()->stock);
        $this->assertSame(15, $patty->fresh()->stock);
        $this->assertSame(15, $soda->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'sale_id' => $sale->id,
            'product_id' => $bread->id,
            'quantity' => 10,
            'type' => 'out',
        ]);
        $combo->update(['name' => 'Nombre modificado después de la venta']);
        $combo->products()->detach();

        $this->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSeeText('Combo completo')
            ->assertSeeText('Incluye:')
            ->assertSeeText('Pan');
        $this->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertSeeText('Combo completo')
            ->assertSeeText('Pan');
    }

    public function test_combo_sale_reports_all_insufficient_components_before_creating_sale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(7, 'Pan');
        $patty = $this->createProduct(3, 'Hamburguesa', null, 'ingredient');
        $soda = $this->createProduct(4, 'Gaseosa');
        $combo = $this->createCombo($admin, true, 'Combo completo');
        $this->attachComponents($combo, $admin, [
            $bread->id => 2,
            $patty->id => 1,
            $soda->id => 1,
        ]);

        $response = $this->post(route('sales.store'), [
            'combo_ids' => [$combo->id],
            'combo_quantities' => [$combo->id => 5],
        ])->assertSessionHasErrors('stock');

        $messages = implode(' ', session('errors')->getBag('default')->get('stock'));
        $this->assertStringContainsString('Stock insuficiente para vender 5 combos.', $messages);
        $this->assertStringContainsString('Pan: se necesitan 10 unidades, hay 7 unidades y faltan 3 unidades.', $messages);
        $this->assertStringContainsString('Hamburguesa: se necesitan 5 unidades, hay 3 unidades y faltan 2 unidades.', $messages);
        $this->assertStringContainsString('Gaseosa: se necesitan 5 unidades, hay 4 unidades y falta 1 unidad.', $messages);
        $this->assertSame(0, Sale::count());
        $this->assertSame(7, $bread->fresh()->stock);
        $this->assertSame(3, $patty->fresh()->stock);
        $this->assertSame(4, $soda->fresh()->stock);
        $this->assertSame(0, InventoryMovement::whereNotNull('sale_id')->count());
    }

    public function test_two_combos_share_and_deduct_a_component_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(10, 'Pan');
        $chicken = $this->createProduct(5, 'Pollo');
        $firstCombo = $this->createCombo($admin, true, 'Combo uno');
        $secondCombo = $this->createCombo($admin, true, 'Combo dos');
        $this->attachComponents($firstCombo, $admin, [$bread->id => 2]);
        $this->attachComponents($secondCombo, $admin, [$bread->id => 3, $chicken->id => 1]);

        $this->post(route('sales.store'), [
            'combo_ids' => [$firstCombo->id, $secondCombo->id],
            'combo_quantities' => [$firstCombo->id => 2, $secondCombo->id => 1],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();
        $this->assertSame(3, $bread->fresh()->stock);
        $this->assertSame(4, $chicken->fresh()->stock);
        $this->assertSame(1, InventoryMovement::where('sale_id', $sale->id)
            ->where('product_id', $bread->id)
            ->where('type', 'out')
            ->count());
        $this->assertDatabaseHas('inventory_movements', [
            'sale_id' => $sale->id,
            'product_id' => $bread->id,
            'quantity' => 7,
            'type' => 'out',
        ]);
    }

    public function test_sales_screen_provides_combo_stock_data_and_quantity_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(8, 'Pan');
        $combo = $this->createCombo($admin, true, 'Combo de pan');
        $this->attachComponents($combo, $admin, [$bread->id => 2]);

        $this->get(route('sales.create'))
            ->assertOk()
            ->assertSee('data-components=', false)
            ->assertSee("id=\"combo-quantity-{$combo->id}\"", false)
            ->assertSee('cart-stock-status', false)
            ->assertSee('validateCartStock', false)
            ->assertSee("event.target.matches('.sale-item-quantity')", false);
    }

    public function test_combo_and_individual_product_share_the_same_stock_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(5, 'Pan');
        $combo = $this->createCombo($admin, true, 'Combo de pan');
        $this->attachComponents($combo, $admin, [$bread->id => 2]);

        $response = $this->post(route('sales.store'), [
            'product_ids' => [$bread->id],
            'product_quantities' => [$bread->id => 2],
            'combo_ids' => [$combo->id],
            'combo_quantities' => [$combo->id => 2],
        ])->assertSessionHasErrors('stock');

        $messages = implode(' ', session('errors')->getBag('default')->get('stock'));
        $this->assertStringContainsString('Pan: se necesitan 6 unidades, hay 5 unidades y falta 1 unidad.', $messages);
        $this->assertSame(0, Sale::count());
        $this->assertSame(5, $bread->fresh()->stock);
    }

    public function test_second_combo_sale_rechecks_stock_after_the_first_sale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(2, 'Pan');
        $combo = $this->createCombo($admin, true, 'Combo de pan');
        $this->attachComponents($combo, $admin, [$bread->id => 2]);
        $payload = [
            'combo_ids' => [$combo->id],
            'combo_quantities' => [$combo->id => 1],
        ];

        $this->post(route('sales.store'), $payload)
            ->assertRedirect(route('sales.index'));
        $this->post(route('sales.store'), $payload)
            ->assertSessionHasErrors('stock');

        $this->assertSame(1, Sale::count());
        $this->assertSame(0, $bread->fresh()->stock);
        $this->assertSame(2, InventoryMovement::where('product_id', $bread->id)
            ->where('type', 'out')
            ->sum('quantity'));
    }

    public function test_cancelling_a_combo_sale_restores_stock_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bread = $this->createProduct(10, 'Pan');
        $combo = $this->createCombo($admin, true, 'Combo de pan');
        $this->attachComponents($combo, $admin, [$bread->id => 2]);
        $this->post(route('sales.store'), [
            'combo_ids' => [$combo->id],
            'combo_quantities' => [$combo->id => 3],
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();
        $this->assertSame(4, $bread->fresh()->stock);
        $this->delete(route('sales.destroy', $sale), [
            'cancellation_reason' => 'Venta ingresada por error',
        ])->assertRedirect(route('sales.index'));

        $this->assertSame(10, $bread->fresh()->stock);
        $this->assertSame(1, InventoryMovement::where('product_id', $bread->id)
            ->where('note', "Anulación de venta #{$sale->id}")
            ->count());
        $this->delete(route('sales.destroy', $sale), [
            'cancellation_reason' => 'Intento de anulación duplicado',
        ])->assertSessionHasErrors('sale');

        $this->assertSame(10, $bread->fresh()->stock);
        $this->assertSame(1, InventoryMovement::where('product_id', $bread->id)
            ->where('note', "Anulación de venta #{$sale->id}")
            ->count());
    }

    private function createCombo(User $admin, bool $active, string $name = 'Combo de prueba', float $price = 100): Combo
    {
        return Combo::create([
            'name' => $name,
            'price' => $price,
            'active' => $active,
            'business_id' => $admin->business_id,
        ]);
    }

    private function attachComponents(Combo $combo, User $admin, array $components): void
    {
        foreach ($components as $productId => $quantity) {
            $combo->products()->attach($productId, [
                'quantity' => $quantity,
                'business_id' => $admin->business_id,
            ]);
        }
    }

    private function createProduct(int $stock = 0, string $name = 'Producto de prueba', ?string $sku = null, string $kind = 'product'): Product
    {
        $sequence = ++$this->productSequence;
        $category = Category::create([
            'name' => "Categoría {$sequence}",
            'slug' => "categoria-{$sequence}",
            'active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku ?? "TEST-COMBO-{$sequence}",
            'price' => 50,
            'cost' => 10,
            'stock' => $stock,
            'stock_minimo' => 0,
            'active' => true,
            'kind' => $kind,
        ]);
    }
}
