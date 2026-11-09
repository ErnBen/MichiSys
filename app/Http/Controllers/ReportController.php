<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Combo;
use App\Models\InventoryMovement;
use App\Models\Provider;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function products()
    {
        $products = Product::with('category')->orderBy('name')->get();
        $pdf = Pdf::loadView('reports.pdf_products', compact('products'));
        return $pdf->download('reporte-productos.pdf');
    }

    public function sales()
    {
        $sales = Sale::with('client', 'user')->orderByDesc('created_at')->get();
        $pdf = Pdf::loadView('reports.pdf_sales', compact('sales'));
        return $pdf->download('reporte-ventas.pdf');
    }

    public function inventory()
    {
        $products = Product::orderBy('name')->get();
        $pdf = Pdf::loadView('reports.pdf_inventory', compact('products'));
        return $pdf->download('reporte-inventario.pdf');
    }

    public function clients()
    {
        $clients = Client::orderBy('name')->get();
        $pdf = Pdf::loadView('reports.pdf_clients', compact('clients'));
        return $pdf->download('reporte-clientes.pdf');
    }

    public function providers()
    {
        $providers = Provider::orderBy('name')->get();
        $pdf = Pdf::loadView('reports.pdf_providers', compact('providers'));
        return $pdf->download('reporte-proveedores.pdf');
    }

    public function profitability(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'fixed_costs' => 'nullable|numeric|min:0|decimal:0,2',
        ]);
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();
        $start = "{$from} 00:00:00";
        $end = "{$to} 23:59:59";
        $salesQuery = fn (): Builder => Sale::query()->whereBetween('created_at', [$start, $end]);
        $totals = $salesQuery()
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(discount), 0) as discounts')
            ->first();

        $costSummary = InventoryMovement::query()
            ->join('sales', 'sales.id', '=', 'inventory_movements.sale_id')
            ->leftJoin('inventory_lots', 'inventory_lots.id', '=', 'inventory_movements.lot_id')
            ->where('inventory_movements.type', 'out')
            ->whereColumn('sales.business_id', 'inventory_movements.business_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_movements.lot_id IS NOT NULL THEN inventory_movements.quantity * COALESCE(inventory_lots.base_unit_cost, inventory_lots.unit_cost, 0) ELSE 0 END), 0) as cost_of_goods')
            ->first();

        $netSales = round((float) $totals->subtotal - (float) $totals->discounts, 2);
        $costOfGoods = round((float) $costSummary->cost_of_goods, 2);
        $grossProfit = round($netSales - $costOfGoods, 2);
        $marginRatio = $netSales > 0 ? $grossProfit / $netSales : 0;
        $fixedCosts = isset($filters['fixed_costs']) ? (float) $filters['fixed_costs'] : null;
        $breakEvenSales = $fixedCosts !== null && $marginRatio > 0
            ? round($fixedCosts / $marginRatio, 2)
            : null;
        $averageContribution = (int) $totals->sales_count > 0
            ? $grossProfit / (int) $totals->sales_count
            : 0;
        $breakEvenTransactions = $fixedCosts !== null && $averageContribution > 0
            ? (int) ceil($fixedCosts / $averageContribution)
            : null;

        $completeSalesCount = $salesQuery()
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('inventory_movements')
                    ->whereColumn('inventory_movements.sale_id', 'sales.id')
                    ->where('inventory_movements.type', 'out');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('inventory_movements')
                    ->whereColumn('inventory_movements.sale_id', 'sales.id')
                    ->where('inventory_movements.type', 'out')
                    ->whereNull('inventory_movements.lot_id');
            })
            ->count();
        $salesWithoutCompleteCost = max((int) $totals->sales_count - $completeSalesCount, 0);

        $sales = $salesQuery()
            ->with('client', 'user')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();
        $saleIds = $sales->getCollection()->pluck('id');
        $costsBySale = $saleIds->isEmpty()
            ? collect()
            : InventoryMovement::query()
                ->leftJoin('inventory_lots', 'inventory_lots.id', '=', 'inventory_movements.lot_id')
                ->whereIn('inventory_movements.sale_id', $saleIds)
                ->where('inventory_movements.type', 'out')
                ->select('inventory_movements.sale_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN inventory_movements.lot_id IS NOT NULL THEN inventory_movements.quantity * COALESCE(inventory_lots.base_unit_cost, inventory_lots.unit_cost, 0) ELSE 0 END), 0) as cost_of_goods')
                ->groupBy('inventory_movements.sale_id')
                ->get()
                ->keyBy('sale_id');
        $saleRows = $sales->getCollection()->map(function (Sale $sale) use ($costsBySale): array {
            $cost = round((float) ($costsBySale->get($sale->id)?->cost_of_goods ?? 0), 2);
            $net = round((float) $sale->subtotal - (float) $sale->discount, 2);

            return [
                'sale' => $sale,
                'net_sales' => $net,
                'cost' => $cost,
                'profit' => round($net - $cost, 2),
            ];
        });

        return view('reports.profitability', compact(
            'sales',
            'saleRows',
            'from',
            'to',
            'fixedCosts',
            'netSales',
            'costOfGoods',
            'grossProfit',
            'marginRatio',
            'breakEvenSales',
            'breakEvenTransactions',
            'salesWithoutCompleteCost'
        ));
    }
}
