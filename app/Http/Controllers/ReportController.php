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
}
