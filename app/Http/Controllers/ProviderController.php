<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $providers = Provider::when($search, fn ($query) =>
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
        return view('providers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        Provider::create($data);

        return Redirect::route('providers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Provider $provider)
    {
        return view('providers.show', compact('provider'));
    }

    public function edit(Provider $provider)
    {
        return view('providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $provider->update($data);

        return Redirect::route('providers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Provider $provider)
    {
        $provider->delete();

        return Redirect::route('providers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
