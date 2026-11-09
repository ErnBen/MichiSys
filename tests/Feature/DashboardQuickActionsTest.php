<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_quick_action_pages_load_successfully(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['products.create', 'combos.create', 'sales.create', 'reports.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}
