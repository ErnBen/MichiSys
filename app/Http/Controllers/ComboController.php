<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $combos = Combo::with('products')
            ->withCount('sales')
            ->when($search, fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('combos.index', compact('combos', 'search'));
    }

    public function create()
    {
        $products = Product::where('active', true)->where('kind', 'product')->orderBy('name')->get();

        return view('combos.create', compact('products'))->with('combo', null);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
            'products' => 'required|array|min:1',
            'products.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)->where('kind', 'product')],
            'quantities' => 'nullable|array',
            'quantities.*' => 'integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('combos', 'public');
        }

        $data['active'] = $request->has('active');

        $combo = Combo::create($data);
        $sync = [];

        foreach ($request->input('products', []) as $productId) {
            $quantity = intval($request->input('quantities.' . $productId, 1));
            if ($quantity > 0) {
                $sync[$productId] = [
                    'quantity' => $quantity,
                    'business_id' => $request->user()->business_id,
                ];
            }
        }

        $combo->products()->sync($sync);

        return Redirect::route('combos.index')->with('success', 'Combo creado correctamente.');
    }

    public function show(Combo $combo)
    {
        return view('combos.show', compact('combo'));
    }

    public function edit(Combo $combo)
    {
        $products = Product::where('active', true)->where('kind', 'product')->orderBy('name')->get();

        return view('combos.edit', compact('combo', 'products'));
    }

    public function update(Request $request, Combo $combo)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
            'products' => 'required|array|min:1',
            'products.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)->where('kind', 'product')],
            'quantities' => 'nullable|array',
            'quantities.*' => 'integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            if ($combo->image) {
                Storage::disk('public')->delete($combo->image);
            }

            $data['image'] = $request->file('image')->store('combos', 'public');
        }

        $data['active'] = $request->has('active');

        $combo->update($data);

        $sync = [];
        foreach ($request->input('products', []) as $productId) {
            $quantity = intval($request->input('quantities.' . $productId, 1));
            if ($quantity > 0) {
                $sync[$productId] = [
                    'quantity' => $quantity,
                    'business_id' => $request->user()->business_id,
                ];
            }
        }

        $combo->products()->sync($sync);

        return Redirect::route('combos.index')->with('success', 'Combo actualizado correctamente.');
    }

    public function destroy(Combo $combo)
    {
        $linkedToSales = $combo->sales()->exists();

        if ($linkedToSales) {
            return Redirect::route('combos.index')->with(
                'warning',
                'Este combo tiene ventas asociadas y no se puede borrar para conservar el historial. Puedes desactivarlo.'
            );
        }

        if ($combo->image) {
            Storage::disk('public')->delete($combo->image);
        }

        $combo->delete();

        return Redirect::route('combos.index')->with('success', 'Combo eliminado correctamente.');
    }

    public function toggle(Combo $combo)
    {
        if (! $combo->active && ! $combo->products()->exists()) {
            return Redirect::route('combos.index')->with(
                'warning',
                'No se puede activar un combo sin productos. Edítalo y agrega al menos un producto vendible.'
            );
        }

        $combo->update(['active' => ! $combo->active]);

        return Redirect::route('combos.index')->with('success', 'Estado del combo actualizado correctamente.');
    }
}
