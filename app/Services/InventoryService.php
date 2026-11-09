<?php

namespace App\Services;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function createOpeningLot(Product $product): void
    {
        if ($product->stock <= 0) {
            return;
        }

        $lot = $this->createLot(
            $product,
            $product->stock,
            'opening',
            "LOTE-INICIAL-{$product->id}",
            $product->fecha_vencimiento?->toDateString(),
            (float) $product->cost
        );

        $this->recordMovement([
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'quantity' => $product->stock,
            'type' => 'in',
            'note' => 'Stock inicial',
            'user_id' => Auth::id(),
        ], $product->business_id);
    }

    public function receive(
        Product $product,
        int $quantity,
        string $note,
        ?int $userId,
        string $source = 'adjustment',
        ?string $lotNumber = null,
        ?string $expiresAt = null,
        ?float $unitCost = null,
        ?int $purchaseId = null,
        ?int $purchaseItemId = null,
        ?int $productionId = null
    ): InventoryLot {
        $updated = Product::whereKey($product->id)
            ->where('business_id', $product->business_id)
            ->increment('stock', $quantity);

        if ($updated !== 1) {
            throw ValidationException::withMessages([
                'stock' => "No se pudo actualizar el inventario de {$product->name}.",
            ]);
        }

        $lot = $this->createLot(
            $product,
            $quantity,
            $source,
            $lotNumber,
            $expiresAt,
            $unitCost ?? (float) $product->cost,
            $purchaseItemId,
            $productionId
        );

        $this->recordMovement([
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'purchase_id' => $purchaseId,
            'production_id' => $productionId,
            'quantity' => $quantity,
            'type' => 'in',
            'note' => $note,
            'user_id' => $userId,
        ], $product->business_id);

        return $lot;
    }

    public function consume(
        Product $product,
        int $quantity,
        string $note,
        ?int $userId,
        ?int $saleId = null,
        bool $allowExpired = false,
        string $movementType = 'out',
        ?int $productionId = null
    ): float {
        $updated = Product::whereKey($product->id)
            ->where('business_id', $product->business_id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($updated !== 1) {
            throw ValidationException::withMessages([
                'stock' => "Stock insuficiente para el producto {$product->name}.",
            ]);
        }

        $lots = InventoryLot::where('product_id', $product->id)
            ->where('business_id', $product->business_id)
            ->where('available_quantity', '>', 0)
            ->when(! $allowExpired, fn ($query) => $query->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now()->toDateString());
            }))
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->get();

        $remaining = $quantity;
        $totalCost = 0;

        foreach ($lots as $lot) {
            $allocated = min($remaining, $lot->available_quantity);
            $updated = InventoryLot::whereKey($lot->id)
                ->where('business_id', $product->business_id)
                ->where('available_quantity', '>=', $allocated)
                ->decrement('available_quantity', $allocated);

            if ($updated !== 1) {
                continue;
            }

            $this->recordMovement([
                'product_id' => $product->id,
                'lot_id' => $lot->id,
                'sale_id' => $saleId,
                'production_id' => $productionId,
                'quantity' => $allocated,
                'type' => $movementType,
                'note' => $note,
                'user_id' => $userId,
            ], $product->business_id);

            $totalCost += $allocated * (float) ($lot->base_unit_cost ?? $lot->unit_cost);
            $remaining -= $allocated;

            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'stock' => "No hay lotes vigentes suficientes para completar la salida de {$product->name}.",
            ]);
        }

        return round($totalCost, 2);
    }

    public function restoreLotMovement(InventoryMovement $movement, ?int $userId, string $note): void
    {
        $product = Product::whereKey($movement->product_id)->firstOrFail();
        $quantity = (int) $movement->quantity;

        if ($movement->lot_id) {
            $updated = InventoryLot::whereKey($movement->lot_id)
                ->where('business_id', $product->business_id)
                ->increment('available_quantity', $quantity);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'stock' => "No se pudo restaurar el lote del producto {$product->name}.",
                ]);
            }

            $updated = Product::whereKey($product->id)
                ->where('business_id', $product->business_id)
                ->increment('stock', $quantity);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'stock' => "No se pudo restaurar el stock del producto {$product->name}.",
                ]);
            }

            $this->recordMovement([
                'product_id' => $product->id,
                'lot_id' => $movement->lot_id,
                'quantity' => $quantity,
                'type' => 'in',
                'note' => $note,
                'user_id' => $userId,
            ], $product->business_id);

            return;
        }

        $this->receive($product, $quantity, $note, $userId, 'adjustment');
    }

    private function createLot(
        Product $product,
        int $quantity,
        string $source,
        ?string $lotNumber,
        ?string $expiresAt,
        float $unitCost,
        ?int $purchaseItemId = null,
        ?int $productionId = null
    ): InventoryLot {
        $lot = new InventoryLot([
            'product_id' => $product->id,
            'purchase_item_id' => $purchaseItemId,
            'production_id' => $productionId,
            'lot_number' => $lotNumber,
            'expires_at' => $expiresAt,
            'received_quantity' => $quantity,
            'available_quantity' => $quantity,
            'unit_cost' => $unitCost,
            'base_unit' => $product->unit,
            'base_unit_cost' => $unitCost,
            'source' => $source,
        ]);
        $lot->business_id = $product->business_id;
        $lot->save();

        return $lot;
    }

    private function recordMovement(array $attributes, int $businessId): void
    {
        $movement = new InventoryMovement($attributes);
        $movement->business_id = $businessId;
        $movement->save();
    }
}
