<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ComboController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $combos = Combo::with('products')
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
        $products = Product::where('active', true)->orderBy('name')->get();

        return view('combos.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
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
                $sync[$productId] = ['quantity' => $quantity];
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
        $products = Product::where('active', true)->orderBy('name')->get();

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
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
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
                $sync[$productId] = ['quantity' => $quantity];
            }
        }

        $combo->products()->sync($sync);

        return Redirect::route('combos.index')->with('success', 'Combo actualizado correctamente.');
    }

    public function destroy(Combo $combo)
    {
        if ($combo->image) {
            Storage::disk('public')->delete($combo->image);
        }

        $combo->delete();

        return Redirect::route('combos.index')->with('success', 'Combo eliminado correctamente.');
    }

    public function toggle(Combo $combo)
    {
        $combo->update(['active' => ! $combo->active]);

        return Redirect::route('combos.index')->with('success', 'Estado del combo actualizado correctamente.');
    }
}
