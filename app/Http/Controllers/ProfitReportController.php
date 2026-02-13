<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\Expense;
use App\Models\Shop;

class ProfitReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        // 🔍 FILTER RANGE (for cards)
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $shopId = $request->shop_id;

        // 🔥 SALES (for cards)
        $sales = PurchaseItem::whereBetween('created_at', [$startDate, $endDate])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->with('product')
            ->get();

        // 💰 Revenue (after discount)
        $totalRevenue = $sales->sum(fn ($i) =>
            $i->total_price - ($i->discount_value ?? 0)
        );

        // 📦 Cost of Goods
        $totalCost = $sales->sum(fn ($i) =>
            ($i->product->cost_price ?? 0) * $i->quantity
        );

        // 📈 Gross Profit
        $grossProfit = $totalRevenue - $totalCost;

        // 🧾 Expenses
        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->sum('amount');

        // 💵 Net Profit / Loss
        $netProfit = $grossProfit - $totalExpenses;
        $loss = $netProfit < 0 ? abs($netProfit) : 0;

        /*
        |--------------------------------------------------------------------------
        | 📊 PROFIT TREND — ALWAYS LAST 10 DAYS (NO EXCUSES)
        |--------------------------------------------------------------------------
        */
        $chartEnd = now()->endOfDay();
        $chartStart = now()->subDays(9)->startOfDay(); // last 10 days

        $rawSales = PurchaseItem::whereBetween('created_at', [$chartStart, $chartEnd])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->with('product')
            ->get()
            ->groupBy(fn ($item) => $item->created_at->format('Y-m-d'));

        $profitByDay = collect(range(0, 9))->map(function ($i) use ($rawSales) {
            $date = now()->subDays(9 - $i)->format('Y-m-d');
            $items = $rawSales->get($date, collect());

            $revenue = $items->sum(fn ($i) =>
                $i->total_price - ($i->discount_value ?? 0)
            );

            $cost = $items->sum(fn ($i) =>
                ($i->product->cost_price ?? 0) * $i->quantity
            );

            return [
                'date' => $date,
                'profit' => $revenue - $cost
            ];
        });

        // 🏪 Shops
        $shops = Shop::all();

        return view('admin.report.profit_loss', compact(
            'totalRevenue',
            'totalCost',
            'grossProfit',
            'totalExpenses',
            'netProfit',
            'loss',
            'profitByDay',
            'shops'
        ));
    }
}
