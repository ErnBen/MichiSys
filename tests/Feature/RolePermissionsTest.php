<?php
namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_cajero_cannot_sell_a_product_from_another_business()
    {
        $business = Business::create([
            'name' => 'Otro negocio',
            'owner_name' => 'Otra administradora',
        ]);
        $businessAdmin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $this->actingAs($businessAdmin);
        $category = Category::create([
            'name' => 'Categoría ajena',
            'slug' => 'categoria-ajena',
            'active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto ajeno',
            'sku' => 'AJENO-01',
            'price' => 1000,
            'cost' => 500,
            'stock' => 10,
            'stock_minimo' => 1,
            'active' => true,
        ]);
        $cajero = User::factory()->create(['role' => 'cajero']);

        $this->actingAs($cajero)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
        ])->assertSessionHasErrors('product_ids.0');

        $this->assertDatabaseMissing('sales', ['user_id' => $cajero->id]);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_cajero_cannot_view_another_cashiers_sale_in_the_same_business()
    {
        $owner = User::factory()->create(['role' => 'cajero']);
        $sale = Sale::create([
            'user_id' => $owner->id,
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 190,
            'total' => 1190,
        ]);
        $otherCashier = User::factory()->create(['role' => 'cajero']);

        $this->actingAs($otherCashier)
            ->get(route('sales.show', $sale))
            ->assertNotFound();
        $this->get(route('sales.receipt', $sale))->assertNotFound();
    }

    public function test_admin_cannot_manage_a_user_from_another_business()
    {
        $business = Business::create([
            'name' => 'Otro negocio',
            'owner_name' => 'Otra administradora',
        ]);
        $otherUser = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'cajero',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('users.edit', $otherUser))
            ->assertNotFound();
    }
}
