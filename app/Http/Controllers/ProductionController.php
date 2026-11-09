<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionItem;
use App\Models\Recipe;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductionController extends Controller
{
    public function index()
    {
        $productions = Production::with('product', 'user')->orderByDesc('produced_at')->paginate(15);

        return view('productions.index', compact('productions'));
    }

    public function create()
    {
        $recipes = Recipe::with('product')->where('active', true)->orderBy('product_id')->get();

        return view('productions.create', compact('recipes'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $businessId = $request->user()->business_id;
        $data = $request->validate([
            'recipe_id' => ['required', Rule::exists('recipes', 'id')->where('business_id', $businessId)->where('active', true)],
            'quantity' => 'required|integer|min:1',
            'lot_number' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        $production = DB::transaction(function () use ($data, $request, $businessId, $inventory): Production {
            $recipe = Recipe::with('items')
                ->where('business_id', $businessId)
                ->where('active', true)
                ->findOrFail($data['recipe_id']);
            $product = Product::where('business_id', $businessId)
                ->where('kind', 'product')
                ->where('active', true)
                ->findOrFail($recipe->product_id);

            if ($recipe->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'recipe_id' => 'La receta no contiene insumos.',
                ]);
            }

            $ingredients = Product::where('business_id', $businessId)
                ->where('kind', 'ingredient')
                ->where('active', true)
                ->whereIn('id', $recipe->items->pluck('ingredient_id'))
                ->get()
                ->keyBy('id');
            $scaledItems = [];

            foreach ($recipe->items as $recipeItem) {
                $ingredient = $ingredients->get($recipeItem->ingredient_id);
                $scaledQuantity = $recipeItem->quantity * (int) $data['quantity'];

                if (! $ingredient || $ingredient->unit !== $recipeItem->unit) {
                    throw ValidationException::withMessages([
                        'recipe_id' => 'La receta contiene un insumo inválido o cuya unidad cambió.',
                    ]);
                }

                if ($scaledQuantity % $recipe->yield_quantity !== 0) {
                    throw ValidationException::withMessages([
                        'quantity' => "La cantidad debe ser múltiplo de {$recipe->yield_quantity} para respetar las cantidades base de la receta.",
                    ]);
                }

                $scaledItems[] = [
                    'recipe_item' => $recipeItem,
                    'ingredient' => $ingredient,
                    'quantity' => intdiv($scaledQuantity, $recipe->yield_quantity),
                ];
            }

            $production = new Production([
                'recipe_id' => $recipe->id,
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'quantity' => (int) $data['quantity'],
                'produced_at' => now(),
                'lot_number' => $data['lot_number'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'total_cost' => 0,
            ]);
            $production->business_id = $businessId;
            $production->save();

            $totalCost = 0;
            foreach ($scaledItems as $line) {
                $lineCost = $inventory->consume(
                    $line['ingredient'],
                    $line['quantity'],
                    "Producción #{$production->id}",
                    $request->user()->id,
                    movementType: 'production',
                    productionId: $production->id
                );
                $totalCost += $lineCost;

                $productionItem = new ProductionItem([
                    'product_id' => $line['ingredient']->id,
                    'product_name' => $line['ingredient']->name,
                    'quantity' => $line['quantity'],
                    'unit' => $line['recipe_item']->unit,
                    'base_unit_cost' => $line['quantity'] > 0 ? $lineCost / $line['quantity'] : 0,
                    'total_cost' => $lineCost,
                ]);
                $productionItem->business_id = $businessId;
                $production->items()->save($productionItem);
            }

            $totalCost = round($totalCost, 2);
            $production->update(['total_cost' => $totalCost]);
            $inventory->receive(
                $product,
                (int) $data['quantity'],
                "Producción #{$production->id}",
                $request->user()->id,
                'production',
                $data['lot_number'] ?? null,
                $data['expires_at'] ?? null,
                $totalCost / (int) $data['quantity'],
                productionId: $production->id
            );

            return $production;
        }, 3);

        return Redirect::route('productions.show', $production)->with('success', 'Producción registrada y existencias actualizadas.');
    }

    public function show(Production $production)
    {
        $production->load('product', 'user', 'items');

        return view('productions.show', compact('production'));
    }
}
