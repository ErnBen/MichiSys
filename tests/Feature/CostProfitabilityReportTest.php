<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostProfitabilityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_lot_cost_profit_and_break_even_for_the_business(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Alimento',
            'sku' => 'ALIM-REPORTE',
            'price' => 10,
            'cost' => 0,
            'stock' => 0,
            'stock_minimo' => 0,
            'active' => true,
        ]);
        $lot = InventoryLot::create([
            'product_id' => $product->id,
            'lot_number' => 'COSTO-01',
            'received_quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 5,
            'base_unit' => 'piece',
            'base_unit_cost' => 5,
            'source' => 'purchase',
        ]);
        $lot->business_id = $admin->business_id;
        $lot->save();
        $product->increment('stock', 10);

        $this->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
        ])->assertRedirect();

        $this->get(route('reports.profitability', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
            'fixed_costs' => '50.00',
        ]))
            ->assertOk()
            ->assertSee('10.00')
            ->assertSee('5.00')
            ->assertSee('100.00')
            ->assertSee('10 ventas');
    }

    public function test_cashiers_cannot_access_profitability_reports(): void
    {
        $cashier = User::factory()->create(['role' => 'cajero']);

        $this->actingAs($cashier)
            ->get(route('reports.profitability'))
            ->assertForbidden();
    }
}
