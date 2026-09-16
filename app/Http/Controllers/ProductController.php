<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $products = Product::with('category')
            ->when($search, fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'))->with('product', null);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['active'] = $request->has('active');

        Product::create($data);

        return Redirect::route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['active'] = $request->has('active');

        if ($request->filled('stock_minimo')) {
            $data['stock_minimo'] = (int) $request->input('stock_minimo');
        } else {
            $data['stock_minimo'] = 0;
        }

        if ($request->filled('fecha_vencimiento')) {
            $data['fecha_vencimiento'] = $request->input('fecha_vencimiento');
        } else {
            $data['fecha_vencimiento'] = null;
        }

        $product->update($data);

        return Redirect::route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        // Evitar eliminación si el producto está vinculado a ventas para mantener integridad histórica.
        $linkedToSales = DB::table('sale_product')->where('product_id', $product->id)->exists();

        if ($linkedToSales) {
            // En lugar de borrar, desactivar el producto para conservar historial de ventas.
            $product->update(['active' => false]);

            return Redirect::route('products.index')->with('warning', 'El producto está vinculado a ventas y no puede eliminarse; se ha desactivado en su lugar.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return Redirect::route('products.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function toggle(Product $product)
    {
        $product->update(['active' => ! $product->active]);

        return Redirect::route('products.index')->with('success', 'Estado del producto actualizado correctamente.');
    }
}
