<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
            'sku_or_barcode' => ['required', 'string'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $qty = $data['qty'] ?? 1;

        $transaction = Transaction::where('status', 'draft')->findOrFail($data['transaction_id']);

        $variant = ProductVariant::query()
            ->where('sku', $data['sku_or_barcode'])
            ->orWhere('barcode', $data['sku_or_barcode'])
            ->firstOrFail();

        if ($variant->stock < $qty) {
            throw ValidationException::withMessages([
                'qty' => ['Stock tidak cukup.'],
            ]);
        }

        $item = TransactionItem::firstOrNew([
            'transaction_id' => $transaction->id,
            'product_variant_id' => $variant->id,
        ]);

        $item->qty = ($item->qty ?? 0) + $qty;
        $item->price = $variant->price;
        $item->discount_amount = $item->discount_amount ?? 0;
        $item->line_total = ($item->qty * $item->price) - $item->discount_amount;
        $item->save();

        $this->recalculateTransaction($transaction);

        return response()->json($transaction->fresh()->load('items.variant'));
    }

    public function updateItem(Request $request, TransactionItem $item): JsonResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($item->transaction->status !== 'draft') {
            throw ValidationException::withMessages([
                'transaction' => ['Hanya draft transaksi yang boleh diubah.'],
            ]);
        }

        if ($item->variant->stock < $data['qty']) {
            throw ValidationException::withMessages([
                'qty' => ['Stock tidak cukup.'],
            ]);
        }

        $discount = $data['discount_amount'] ?? 0;

        $item->update([
            'qty' => $data['qty'],
            'discount_amount' => $discount,
            'line_total' => ($data['qty'] * $item->price) - $discount,
        ]);

        $this->recalculateTransaction($item->transaction);

        return response()->json($item->fresh());
    }

    public function removeItem(TransactionItem $item): JsonResponse
    {
        if ($item->transaction->status !== 'draft') {
            throw ValidationException::withMessages([
                'transaction' => ['Hanya draft transaksi yang boleh diubah.'],
            ]);
        }

        $transaction = $item->transaction;
        $item->delete();
        $this->recalculateTransaction($transaction);

        return response()->json(['message' => 'Item removed']);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
            'method' => ['required', 'in:cash,transfer,qris,midtrans,xendit'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
        ]);

        $transaction = DB::transaction(function () use ($data) {
            $transaction = Transaction::query()
                ->with('items.variant')
                ->lockForUpdate()
                ->where('status', 'draft')
                ->findOrFail($data['transaction_id']);

            if ($transaction->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'transaction' => ['Cart masih kosong.'],
                ]);
            }

            foreach ($transaction->items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);

                if ($variant->stock < $item->qty) {
                    throw ValidationException::withMessages([
                        'stock' => ["Stock {$variant->sku} tidak cukup."],
                    ]);
                }

                $variant->decrement('stock', $item->qty);
            }

            if ($data['amount'] < $transaction->grand_total) {
                throw ValidationException::withMessages([
                    'amount' => ['Pembayaran kurang dari total belanja.'],
                ]);
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'method' => $data['method'],
                'amount' => $data['amount'],
                'reference_no' => $data['reference_no'] ?? null,
                'meta' => $data['meta'] ?? null,
                'paid_at' => now(),
            ]);

            $transaction->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $transaction->fresh()->load('items.variant');
        });

        return response()->json($transaction);
    }

    private function recalculateTransaction(Transaction $transaction): void
    {
        $transaction->loadMissing('items');

        $subtotal = (int) $transaction->items->sum(fn (TransactionItem $item) => $item->qty * $item->price);
        $discount = (int) $transaction->items->sum('discount_amount');
        $tax = 0;

        $transaction->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'grand_total' => max($subtotal - $discount + $tax, 0),
        ]);
    }
}
