<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $categories = Category::when($search, fn ($query) =>
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
        )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories', 'search'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'active' => 'sometimes|boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['active'] = $request->has('active');

        Category::create($data);

        return Redirect::route('categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'active' => 'sometimes|boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['active'] = $request->has('active');

        $category->update($data);

        return Redirect::route('categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return Redirect::route('categories.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
