<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Combo;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Client;
use App\Models\InventoryLot;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role === 'cajero') {
            return redirect()->route('sales.create');
        }

        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalCombos = Combo::count();
        $totalSales = Sale::count();
        $totalClients = Client::count();
        $warningDays = config('michisys.expiration_warning_days', 30);
        $criticalDays = config('michisys.expiration_critical_days', 7);

        $stockProducts = Product::withSum(['lots as sellable_stock' => function ($query): void {
            $query->where('available_quantity', '>', 0)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhereDate('expires_at', '>=', now()->toDateString());
                });
        }], 'available_quantity')->get();
        $lowStockList = $stockProducts
            ->filter(fn ($product) => (int) ($product->sellable_stock ?? 0) <= $product->stock_minimo)
            ->sortBy('sellable_stock')
            ->take(10)
            ->values();
        $lowStockProducts = $stockProducts
            ->filter(fn ($product) => (int) ($product->sellable_stock ?? 0) <= $product->stock_minimo)
            ->count();

        $expirationLots = InventoryLot::with('product')
            ->where('available_quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->orderBy('expires_at')
            ->get()
            ->map(function ($lot) use ($warningDays, $criticalDays) {
                $daysRemaining = (int) today()->diffInDays($lot->expires_at, false);

                if ($daysRemaining < 0) {
                    $status = 'Vencido';
                    $badgeClass = 'danger';
                } elseif ($daysRemaining <= $criticalDays) {
                    $status = 'Crítico';
                    $badgeClass = 'warning';
                } elseif ($daysRemaining <= $warningDays) {
                    $status = 'Próximo a vencer';
                    $badgeClass = 'info';
                } else {
                    $status = 'Normal';
                    $badgeClass = 'success';
                }

                $lot->name = $lot->product?->name ?? 'Producto eliminado';
                $lot->fecha_vencimiento = $lot->expires_at;
                $lot->days_remaining = $daysRemaining;
                $lot->expiration_status = $status;
                $lot->expiration_badge = $badgeClass;

                return $lot;
            });

        $expiringSoonProducts = $expirationLots
            ->filter(fn ($lot) => $lot->days_remaining >= 0 && $lot->days_remaining <= $warningDays)
            ->values();
        $expiredProducts = $expirationLots->filter(fn ($lot) => $lot->days_remaining < 0)->values();

        $salesChartData = Sale::selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->limit(10)
            ->get()
            ->map(fn ($sale) => $sale->total)
            ->toArray();

        $salesChartLabels = Sale::selectRaw('DATE(created_at) as day')
            ->groupBy('day')
            ->orderBy('day')
            ->limit(10)
            ->get()
            ->map(fn ($sale) => $sale->day)
            ->toArray();

        return view('dashboard', compact(
            'totalProducts',
            'totalCategories',
            'totalCombos',
            'totalSales',
            'totalClients',
            'lowStockProducts',
            'lowStockList',
            'expiringSoonProducts',
            'expiredProducts',
            'salesChartData',
            'salesChartLabels'
        ));
    }
}
