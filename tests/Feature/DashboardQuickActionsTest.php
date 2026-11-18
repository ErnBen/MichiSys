<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sales_summary_is_scoped_to_the_owner_and_excludes_cancelled_sales(): void
    {
        $otherBusiness = \App\Models\Business::create(['name' => 'Otra tienda', 'owner_name' => 'Otro dueño']);
        $otherOwner = User::factory()->create(['role' => 'dueno', 'business_id' => $otherBusiness->id]);
        $this->actingAs($otherOwner);
        \App\Models\Sale::create(['user_id' => $otherOwner->id, 'total' => 900000]);

        $business = \App\Models\Business::create(['name' => 'Mi tienda', 'owner_name' => 'Dueño']);
        $owner = User::factory()->create(['role' => 'dueno', 'business_id' => $business->id]);
        $this->actingAs($owner);
        \App\Models\Sale::create(['user_id' => $owner->id, 'total' => 25000]);
        $cancelled = \App\Models\Sale::create(['user_id' => $owner->id, 'total' => 10000]);
        $cancelled->forceFill(['cancelled_at' => now()])->save();

        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('todaySales', fn ($total) => (float) $total === 25000.0)
            ->assertViewHas('pendingBalance', fn ($total) => (float) $total === 0.0)
            ->assertViewHas('recentPurchases', fn ($purchases) => $purchases->isEmpty())
            ->assertSee('dashboard-stat-card', false)
            ->assertSee(route('products.index'), false)
            ->assertSee('Compras recientes');
    }
    public function test_dashboard_quick_action_pages_load_successfully(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['products.create', 'combos.create', 'sales.create', 'reports.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}
