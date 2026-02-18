<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\Expense;
use App\Models\Shop;
use App\Models\Category;

class ProfitReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $shopId = $request->shop_id;

        /* ---------------- FILTER DATES ---------------- */
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfDay();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        /* ---------------- SALES ---------------- */
        $sales = PurchaseItem::whereBetween('created_at', [$startDate, $endDate])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->with('product')
            ->get();

        /* ---------------- TOTAL REVENUE ---------------- */
        // If filter is applied, show revenue for the range; otherwise today
        if ($request->start_date && $request->end_date) {
            $totalRevenue = $sales->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
        } else {
            $totalRevenue = PurchaseItem::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->get()
                ->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
        }

        /* ---------------- TOTAL COST ---------------- */
        $totalCost = $sales->sum(fn ($i) => ($i->product->cost_price ?? 0) * $i->quantity);

        $grossProfit = $totalRevenue - $totalCost;

        /* ---------------- EXPENSES ---------------- */
        $expensesQuery = Expense::whereBetween('date', [$startDate, $endDate])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId));

        $totalExpenses = $expensesQuery->sum('amount');

        $expensesByCategory = $expensesQuery
            ->selectRaw('title, SUM(amount) as total')
            ->groupBy('title')
            ->get();

        /* ---------------- NET PROFIT ---------------- */
        $netProfit = $grossProfit - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        /* ---------------- CHART DATA ---------------- */
        // ------------------- PERIOD FOR CHART -------------------
if ($request->start_date && $request->end_date) {
    $startDate = Carbon::parse($request->start_date)->startOfDay();
    $endDate = Carbon::parse($request->end_date)->endOfDay();
} else {
    $startDate = now()->subDays(9)->startOfDay(); // last 10 days
    $endDate = now()->endOfDay();
}

// Build period array
$period = [];
$current = $startDate->copy();
while ($current <= $endDate) {
    $period[] = $current->format('Y-m-d');
    $current->addDay();
}

// Group sales and expenses by date
$rawSales = $sales->groupBy(fn($item) => $item->created_at->format('Y-m-d'));
$rawExpenses = Expense::whereBetween('date', [$startDate, $endDate])
    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
    ->get()
    ->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

// Map profit by day
$profitByDay = collect($period)->map(function($date) use ($rawSales, $rawExpenses) {
    $items = $rawSales->get($date, collect());
    $expenses = $rawExpenses->get($date, collect());

    $revenue = $items->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
    $cost = $items->sum(fn($i) => ($i->product->cost_price ?? 0) * $i->quantity);
    $expenseTotal = $expenses->sum('amount');

    return [
        'date' => $date,
        'revenue' => $revenue,
        'expenses' => $expenseTotal,
        'profit' => $revenue - $cost - $expenseTotal
    ];
});


        /* ---------------- BEST & WORST DAY ---------------- */
        $bestDay = $profitByDay->sortByDesc('profit')->first();
        $worstDay = $profitByDay->sortBy('profit')->first();

        /* ---------------- SHOPS ---------------- */
        $shops = Shop::all();

        return view('admin.report.profit_loss', compact(
            'totalRevenue',
            'totalCost',
            'grossProfit',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'profitByDay',
            'expensesByCategory',
            'bestDay',
            'worstDay',
            'shops',
            'sales' // 👈 add this
        ));
    }
}
