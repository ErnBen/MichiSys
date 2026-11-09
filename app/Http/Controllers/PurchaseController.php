<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('provider', 'user')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $providers = Provider::orderBy('name')->get();
        $products = Product::where('active', true)->orderBy('name')->get();

        return view('purchases.create', compact('providers', 'products'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $businessId = $request->user()->business_id;
        $data = $request->validate([
            'provider_id' => ['required', Rule::exists('providers', 'id')->where('business_id', $businessId)],
            'purchase_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('business_id', $businessId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001|decimal:0,3',
            'items.*.purchase_unit' => 'required|in:piece,g,kg,ml,l',
            'items.*.unit_cost' => 'required|numeric|min:0|decimal:0,2',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expires_at' => 'nullable|date',
        ]);

        $purchase = DB::transaction(function () use ($data, $request, $businessId, $inventory): Purchase {
            $products = Product::where('business_id', $businessId)
                ->whereIn('id', collect($data['items'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Uno o más productos ya no pertenecen a este negocio.',
                ]);
            }

            $provider = Provider::whereKey($data['provider_id'])
                ->where('business_id', $businessId)
                ->firstOrFail();
            $lines = [];
            $total = 0;

            foreach ($data['items'] as $index => $item) {
                $product = $products->get($item['product_id']);
                $purchaseUnit = $item['purchase_unit'];
                $factor = $product->purchaseUnitFactors()[$purchaseUnit] ?? null;

                if (! $factor) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_unit" => "La unidad {$purchaseUnit} no es compatible con {$product->unit_label}.",
                    ]);
                }

                $purchasedQuantity = (float) $item['quantity'];
                $baseQuantity = $purchasedQuantity * $factor;

                if (abs($baseQuantity - round($baseQuantity)) > 0.000001 || $baseQuantity < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'La cantidad debe convertirse a una unidad base completa.',
                    ]);
                }

                $unitCost = round((float) $item['unit_cost'], 2);
                $baseUnitCost = round($unitCost / $factor, 6);
                $subtotal = round($unitCost * $purchasedQuantity, 2);
                $total += $subtotal;
                $lines[] = compact('product', 'item', 'unitCost', 'baseUnitCost', 'baseQuantity', 'purchasedQuantity', 'subtotal');
            }

            $purchase = Purchase::create([
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'user_id' => $request->user()->id,
                'purchase_date' => $data['purchase_date'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'total' => $total,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $quantity = (int) $line['baseQuantity'];
                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'base_unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_cost' => $line['unitCost'],
                    'purchased_quantity' => $line['purchasedQuantity'],
                    'purchase_unit' => $line['item']['purchase_unit'],
                    'base_unit_cost' => $line['baseUnitCost'],
                    'subtotal' => $line['subtotal'],
                    'lot_number' => $line['item']['lot_number'] ?? null,
                    'expires_at' => $line['item']['expires_at'] ?? null,
                ]);

                $inventory->receive(
                    $product,
                    $quantity,
                    "Compra #{$purchase->id}",
                    $request->user()->id,
                    'purchase',
                    $line['item']['lot_number'] ?? null,
                    $line['item']['expires_at'] ?? null,
                    $line['baseUnitCost'],
                    $purchase->id,
                    $purchaseItem->id
                );
            }

            return $purchase;
        }, 3);

        return Redirect::route('purchases.show', $purchase)
            ->with('success', 'Compra registrada y stock actualizado correctamente.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('provider', 'user', 'items.product');

        return view('purchases.show', compact('purchase'));
    }
}
