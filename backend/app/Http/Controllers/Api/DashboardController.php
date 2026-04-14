<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $date = $request->date('date') ?? now();

        $dailyTransactions = Transaction::query()
            ->whereDate('paid_at', $date)
            ->where('status', 'paid');

        $gross = (int) $dailyTransactions->sum('subtotal');
        $discount = (int) $dailyTransactions->sum('discount_amount');
        $tax = (int) $dailyTransactions->sum('tax_amount');
        $net = (int) $dailyTransactions->sum('grand_total');

        $lowStockCount = ProductVariant::query()
            ->whereColumn('stock', '<=', 'min_stock_alert')
            ->count();

        return response()->json([
            'date' => $date->toDateString(),
            'sales' => [
                'gross' => $gross,
                'discount' => $discount,
                'tax' => $tax,
                'net' => $net,
                'paid_transactions' => (int) $dailyTransactions->count(),
            ],
            'inventory' => [
                'low_stock_variants' => $lowStockCount,
            ],
        ]);
    }
}
