<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Combo;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalCombos = Combo::count();
        $totalSales = Sale::count();
        $totalClients = Client::count();
        $lowStockProducts = Product::whereColumn('stock', '<=', 'stock_minimo')->count();
        $lowStockList = Product::whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(10)
            ->get();

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
            'salesChartData',
            'salesChartLabels'
        ));
    }
}
