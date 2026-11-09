<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $providers = Provider::with('products')
            ->when($search, fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('providers.index', compact('providers', 'search'));
    }

    public function create()
    {
        $products = Product::where('active', true)->orderBy('name')->get();

        return view('providers.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'products' => 'nullable|array',
            'products.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)],
        ]);

        $provider = Provider::create($data);
        $provider->products()->syncWithPivotValues(
            $request->input('products', []),
            ['business_id' => $request->user()->business_id]
        );

        return Redirect::route('providers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Provider $provider)
    {
        $provider->load('products');

        return view('providers.show', compact('provider'));
    }

    public function edit(Provider $provider)
    {
        $provider->load('products');
        $products = Product::where('active', true)->orderBy('name')->get();

        return view('providers.edit', compact('provider', 'products'));
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'products' => 'nullable|array',
            'products.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)],
        ]);

        $provider->update($data);
        $provider->products()->syncWithPivotValues(
            $request->input('products', []),
            ['business_id' => $request->user()->business_id]
        );

        return Redirect::route('providers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Provider $provider)
    {
        $provider->products()->detach();
        $provider->delete();

        return Redirect::route('providers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
