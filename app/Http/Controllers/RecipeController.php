<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('product')->withCount('items')->orderBy('product_id')->paginate(15);

        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::where('kind', 'product')
            ->where('active', true)
            ->whereDoesntHave('recipe')
            ->orderBy('name')
            ->get();
        $ingredients = Product::where('kind', 'ingredient')->where('active', true)->orderBy('name')->get();

        return view('recipes.create', compact('products', 'ingredients'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $businessId = $request->user()->business_id;

        $recipe = DB::transaction(function () use ($data, $businessId): Recipe {
            $product = Product::where('business_id', $businessId)
                ->where('kind', 'product')
                ->findOrFail($data['product_id']);
            $ingredients = $this->ingredientsFor($data['items'], $businessId);

            $recipe = new Recipe([
                'product_id' => $product->id,
                'yield_quantity' => $data['yield_quantity'],
                'instructions' => $data['instructions'] ?? null,
                'active' => true,
            ]);
            $recipe->business_id = $businessId;
            $recipe->save();
            $this->saveItems($recipe, $data['items'], $ingredients, $businessId);

            return $recipe;
        }, 3);

        return Redirect::route('recipes.index')->with('success', "Receta de {$recipe->product->name} guardada.");
    }

    public function edit(Recipe $recipe)
    {
        $products = Product::where('kind', 'product')
            ->where(function ($query) use ($recipe): void {
                $query->whereDoesntHave('recipe')->orWhere('id', $recipe->product_id);
            })
            ->orderBy('name')
            ->get();
        $ingredients = Product::where('kind', 'ingredient')->orderBy('name')->get();
        $recipe->load('items');

        return view('recipes.edit', compact('recipe', 'products', 'ingredients'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $this->validatedData($request, $recipe);
        $businessId = $request->user()->business_id;

        DB::transaction(function () use ($data, $recipe, $businessId): void {
            $product = Product::where('business_id', $businessId)
                ->where('kind', 'product')
                ->findOrFail($data['product_id']);
            $ingredients = $this->ingredientsFor($data['items'], $businessId);

            $recipe->update([
                'product_id' => $product->id,
                'yield_quantity' => $data['yield_quantity'],
                'instructions' => $data['instructions'] ?? null,
            ]);
            $recipe->items()->delete();
            $this->saveItems($recipe, $data['items'], $ingredients, $businessId);
        }, 3);

        return Redirect::route('recipes.index')->with('success', 'Receta actualizada correctamente.');
    }

    private function validatedData(Request $request, ?Recipe $recipe = null): array
    {
        $businessId = $request->user()->business_id;

        return $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('business_id', $businessId)->where('kind', 'product'),
                Rule::unique('recipes', 'product_id')
                    ->where('business_id', $businessId)
                    ->ignore($recipe?->id),
            ],
            'yield_quantity' => 'required|integer|min:1',
            'instructions' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('business_id', $businessId)->where('kind', 'ingredient'),
            ],
            'items.*.quantity' => 'required|integer|min:1',
        ]);
    }

    private function ingredientsFor(array $items, int $businessId)
    {
        $ingredients = Product::where('business_id', $businessId)
            ->where('kind', 'ingredient')
            ->whereIn('id', collect($items)->pluck('ingredient_id'))
            ->get()
            ->keyBy('id');

        if ($ingredients->count() !== count($items)) {
            throw ValidationException::withMessages([
                'items' => 'Uno o más insumos no pertenecen a este negocio.',
            ]);
        }

        return $ingredients;
    }

    private function saveItems(Recipe $recipe, array $items, $ingredients, int $businessId): void
    {
        foreach ($items as $item) {
            $ingredient = $ingredients->get($item['ingredient_id']);
            $recipeItem = new RecipeItem([
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'quantity' => $item['quantity'],
                'unit' => $ingredient->unit,
            ]);
            $recipeItem->business_id = $businessId;
            $recipe->items()->save($recipeItem);
        }
    }
}
