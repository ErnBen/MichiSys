<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_open_another_business_product(): void
    {
        $businessA = Business::create(['name' => 'Tienda A', 'owner_name' => 'Dueño A']);
        $businessB = Business::create(['name' => 'Tienda B', 'owner_name' => 'Dueño B']);
        $ownerA = User::factory()->create(['business_id' => $businessA->id, 'role' => 'dueno']);
        $ownerB = User::factory()->create(['business_id' => $businessB->id, 'role' => 'dueno']);

        $this->actingAs($ownerB);
        $category = Category::create(['name' => 'Categoría B', 'slug' => 'categoria-b', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto privado B',
            'price' => 100,
            'cost' => 50,
            'stock' => 1,
            'stock_minimo' => 0,
            'active' => true,
        ]);

        $this->actingAs($ownerA)
            ->get(route('products.show', $product))
            ->assertNotFound();
    }

    public function test_owner_cannot_cancel_a_sale_from_another_business(): void
    {
        $businessA = Business::create(['name' => 'Tienda A', 'owner_name' => 'Dueño A']);
        $businessB = Business::create(['name' => 'Tienda B', 'owner_name' => 'Dueño B']);
        $ownerA = User::factory()->create(['business_id' => $businessA->id, 'role' => 'dueno']);
        $ownerB = User::factory()->create(['business_id' => $businessB->id, 'role' => 'dueno']);

        $this->actingAs($ownerB);
        $sale = Sale::create(['user_id' => $ownerB->id, 'total' => 100]);

        $this->actingAs($ownerA)->delete(route('sales.destroy', $sale), [
            'cancellation_reason' => 'Anulación no autorizada',
        ])->assertNotFound();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'cancelled_at' => null]);
    }

    public function test_cashier_cannot_open_administrative_routes(): void
    {
        $business = Business::create(['name' => 'Tienda', 'owner_name' => 'Dueño']);
        $cashier = User::factory()->create(['business_id' => $business->id, 'role' => 'cajero']);

        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->get(route('inventory.index'))->assertForbidden();
        $this->get(route('business.settings.edit'))->assertForbidden();
    }

    public function test_superadmin_creates_a_business_and_associated_owner(): void
    {
        $superadmin = User::factory()->create(['business_id' => null, 'role' => 'superadmin']);

        $this->actingAs($superadmin)->post(route('superadmin.businesses.store'), [
            'name' => 'Nueva tienda',
            'owner_name' => 'Dueño Nuevo',
            'owner_email' => 'owner@nueva.test',
            'owner_password' => 'secure-owner-password',
            'phone' => '555-0100',
        ])->assertRedirect(route('superadmin.businesses.index'));

        $business = Business::where('name', 'Nueva tienda')->firstOrFail();
        $owner = User::where('email', 'owner@nueva.test')->firstOrFail();
        $this->assertSame('dueno', $owner->role);
        $this->assertSame($business->id, $owner->business_id);
        $this->assertTrue($business->active);
        $this->assertNotNull($owner->email_verified_at);

        $this->actingAs($superadmin)->get(route('superadmin.businesses.create'))
            ->assertOk()
            ->assertSeeText('Cuenta inicial del dueño');
        $this->get(route('superadmin.businesses.index'))
            ->assertOk()
            ->assertSeeText('Nueva tienda');
        $this->get(route('superadmin.businesses.edit', $business))->assertOk();
        $this->get(route('superadmin.businesses.users', $business))->assertOk()
            ->assertSeeText('owner@nueva.test');

    }

    public function test_owner_can_manage_their_business_profile(): void
    {
        $business = Business::create(['name' => 'Tienda', 'owner_name' => 'Dueño']);
        $owner = User::factory()->create(['business_id' => $business->id, 'role' => 'dueno']);

        $this->actingAs($owner)->get(route('business.settings.edit'))->assertOk();
        $this->put(route('business.settings.update'), [
            'name' => 'Tienda actualizada',
            'owner_name' => 'Dueño',
            'phone' => '555-0101',
            'ruc' => '80012345-6',
            'description' => 'Descripción comercial',
        ])->assertRedirect(route('business.settings.edit'));
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'ruc' => '80012345-6',
            'description' => 'Descripción comercial',
        ]);
    }

    public function test_superadmin_login_redirects_to_the_platform_panel(): void
    {
        $superadmin = User::factory()->create([
            'business_id' => null,
            'role' => 'superadmin',
            'email' => 'superadmin@example.test',
            'password' => Hash::make('secure-superadmin-password'),
        ]);

        $this->post('/login', [
            'email' => $superadmin->email,
            'password' => 'secure-superadmin-password',
        ])->assertRedirect(route('superadmin.businesses.index', absolute: false));
    }

    public function test_superadmin_can_assign_an_owner_to_an_existing_business_without_one(): void
    {
        $business = Business::create(['name' => 'Negocio heredado', 'owner_name' => 'Sin cuenta']);
        $superadmin = User::factory()->create(['business_id' => null, 'role' => 'superadmin']);

        $this->actingAs($superadmin)->put(route('superadmin.businesses.update', $business), [
            'name' => $business->name,
            'owner_name' => 'Dueño asignado',
            'active' => 1,
            'owner_email' => 'assigned-owner@example.test',
            'owner_password' => 'secure-assigned-owner-password',
        ])->assertRedirect(route('superadmin.businesses.index'));

        $owner = User::where('email', 'assigned-owner@example.test')->firstOrFail();
        $this->assertSame($business->id, $owner->business_id);
        $this->assertSame('dueno', $owner->role);
    }

    public function test_owner_cannot_create_businesses_or_access_superadmin_routes(): void
    {
        $business = Business::create(['name' => 'Tienda', 'owner_name' => 'Dueño']);
        $owner = User::factory()->create(['business_id' => $business->id, 'role' => 'dueno']);

        $this->actingAs($owner)->get(route('superadmin.businesses.index'))->assertForbidden();
        $this->post(route('superadmin.businesses.store'), [])->assertForbidden();
    }

    public function test_superadmin_must_select_one_business_before_opening_its_modules(): void
    {
        $business = Business::create(['name' => 'Tienda elegida', 'owner_name' => 'Dueño']);
        $superadmin = User::factory()->create(['business_id' => null, 'role' => 'superadmin']);

        $this->actingAs($superadmin)->get(route('products.index'))->assertForbidden();
        $this->post(route('superadmin.businesses.select', $business))
            ->assertRedirect(route('dashboard'));
        $this->get(route('products.index'))->assertOk();
        $this->post(route('users.store'), [
            'name' => 'Cajero asignado',
            'email' => 'selected-cashier@example.test',
            'password' => 'secure-cashier-password',
            'active' => 1,
        ])->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'selected-cashier@example.test',
            'business_id' => $business->id,
            'role' => 'cajero',
        ]);
    }

    public function test_cashier_can_cancel_a_sale_once_with_audited_reason_and_restored_stock(): void
    {
        $business = Business::create(['name' => 'Tienda', 'owner_name' => 'Dueño']);
        $owner = User::factory()->create(['business_id' => $business->id, 'role' => 'dueno']);
        $cashier = User::factory()->create(['business_id' => $business->id, 'role' => 'cajero']);

        $this->actingAs($owner);
        $category = Category::create(['name' => 'Categoría', 'slug' => 'categoria', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto vendible',
            'sku' => 'PROD-ANULACION',
            'price' => 100,
            'cost' => 50,
            'stock' => 3,
            'stock_minimo' => 0,
            'active' => true,
        ]);
        $this->actingAs($cashier)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'cash',
            'cash_received' => 100,
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::where('user_id', $cashier->id)->firstOrFail();
        $this->delete(route('sales.destroy', $sale), [
            'cancellation_reason' => 'Venta ingresada por error',
        ])->assertRedirect(route('sales.index'));

        $sale->refresh();
        $this->assertNotNull($sale->cancelled_at);
        $this->assertSame($cashier->id, $sale->cancelled_by);
        $this->assertSame('Venta ingresada por error', $sale->cancellation_reason);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->actingAs($owner)->get(route('dashboard'))->assertViewHas('totalSales', 0);
        $this->get(route('reports.profitability'))->assertOk()->assertViewHas('netSales', 0.0);

        $this->actingAs($cashier);
        $this->delete(route('sales.destroy', $sale), [
            'cancellation_reason' => 'Segunda anulación',
        ])->assertSessionHasErrors('sale');
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_public_registration_cannot_assign_the_superadmin_role(): void
    {
        $this->post('/register', [
            'name' => 'Nuevo usuario',
            'email' => 'public-owner@example.test',
            'business_name' => 'Tienda pública',
            'responsible_name' => 'Nuevo usuario',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'superadmin',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'public-owner@example.test',
            'role' => 'dueno',
        ]);
    }
}
