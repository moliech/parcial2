Heiber crea el archivo en app/Services/DispensingService.php. Cumple con: DB::transaction, omite lotes vencidos, valida stock disponible con ValidationException, descuenta por FEFO y actualiza el stock del producto:

app/Services/DispensingService.php
 Copiar
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispensingService
{
    /**
     * Despacha medicamentos aplicando FEFO, validación de caducidad y DB::transaction.
     */
    public function dispense(Product $product, int $quantity): array
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad a dispensar debe ser mayor a cero.',
            ]);
        }

        return DB::transaction(function () use ($product, $quantity) {
            $today = now()->toDateString();

            // 1. Obtener lotes no vencidos con existencia, ordenados por FEFO
            $validLots = $product->lots()
                ->where('expiration_date', '>=', $today)
                ->where('quantity', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->lockForUpdate()
                ->get();

            // 2. Validar disponibilidad de stock no vencido
            $availableStock = $validLots->sum('quantity');

            if ($availableStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente en lotes no vencidos. Solicitado: {$quantity}, disponible vigente: {$availableStock}.",
                ]);
            }

            // 3. Descontar aplicando FEFO lote a lote
            $remaining = $quantity;
            $affected = [];

            foreach ($validLots as $lot) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($lot->quantity, $remaining);
                $lot->decrement('quantity', $deduct);

                $affected[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'expiration_date' => $lot->expiration_date->format('Y-m-d'),
                    'quantity_deducted' => $deduct,
                ];

                $remaining -= $deduct;
            }

            // 4. Descontar stock consolidado del producto si existe la columna
            if (isset($product->stock)) {
                $product->decrement('stock', $quantity);
            }

            return [
                'product_id' => $product->id,
                'total_dispensed' => $quantity,
                'lots_affected' => $affected,
            ];
        });
    }
}