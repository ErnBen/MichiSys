<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
    }

    public function test_cajero_cannot_access_users_index()
    {
        $cajero = User::factory()->create(['role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_cajero_can_access_sales_create()
    {
        $cajero = User::factory()->create(['role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('sales.create'));

        $response->assertStatus(200);
    }
}
