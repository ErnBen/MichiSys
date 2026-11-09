<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_cashier_in_own_business_even_if_admin_role_is_submitted(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Cajero A',
            'email' => 'cajero@example.test',
            'password' => 'password123',
            'active' => '1',
            'role' => 'admin',
        ])->assertRedirect(route('users.index'));

        $cashier = User::where('email', 'cajero@example.test')->firstOrFail();
        $this->assertSame('cajero', $cashier->role);
        $this->assertSame($business->id, $cashier->business_id);
        $this->assertTrue(Hash::check('password123', $cashier->password));
    }

    public function test_admin_can_change_cashier_password_and_deactivate_the_account(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);
        $cashier = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'cajero',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)->put(route('users.update', $cashier), [
            'name' => $cashier->name,
            'email' => $cashier->email,
            'password' => 'new-password',
            'active' => '0',
        ])->assertRedirect(route('users.index'));

        $cashier->refresh();
        $this->assertSame('cajero', $cashier->role);
        $this->assertFalse($cashier->active);
        $this->assertTrue(Hash::check('new-password', $cashier->password));
    }

    public function test_cashier_cannot_access_administrative_user_management_and_is_sent_to_sales(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $cashier = User::factory()->create(['business_id' => $business->id, 'role' => 'cajero']);

        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertRedirect(route('sales.create'));
    }

    public function test_cashier_uses_the_shared_login_and_lands_on_the_sales_screen(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $cashier = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'cajero',
            'email' => 'cashier@example.test',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))->assertRedirect(route('sales.create'));
        $this->get(route('sales.create'))->assertOk();
    }

    public function test_admin_can_render_the_cashier_creation_form(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => 'admin']);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSeeText('Crear cajero')
            ->assertSeeText('El cajero iniciará sesión');
    }

    public function test_inactive_cashier_cannot_keep_using_authenticated_routes(): void
    {
        $business = Business::create(['name' => 'Tienda A', 'owner_name' => 'Admin A']);
        $cashier = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'cajero',
            'active' => false,
        ]);

        $this->actingAs($cashier)->get(route('prices.index'))->assertForbidden();
    }
}
