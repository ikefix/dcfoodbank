@extends('layouts.adminapp')

@section('admincontent')
<div class="container-fluid">

    {{-- PAGE TITLE --}}
    <div class="mb-4">
        <h3 class="fw-bold text-primary">Profit & Loss Report</h3>
        <p class="text-muted">Revenue, expenses, and net performance for selected period</p>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.report.profit_loss') }}" class="row g-3 mb-4 align-items-end">

        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input
                type="date"
                name="start_date"
                class="form-control"
                value="{{ request('start_date') }}"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input
                type="date"
                name="end_date"
                class="form-control"
                value="{{ request('end_date') }}"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Shop</label>
            <select name="shop_id" class="form-select">
                <option value="">All Shops</option>
                @foreach($shops as $shop)
                    <option
                        value="{{ $shop->id }}"
                        {{ request('shop_id') == $shop->id ? 'selected' : '' }}
                    >
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-grid">
            <button class="btn btn-primary">
                Apply Filter
            </button>
        </div>

    </form>

    {{-- SUMMARY CARDS --}}
    <div class="row mb-4 g-3">

       <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Total Revenue</h6>
                <h4 class="fw-bold text-success">
                    ₦{{ number_format($totalRevenue ?? 0, 2) }}
                </h4>
            </div>
        </div>



        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Cost of Goods</h6>
                <h4 class="fw-bold text-danger">
                    ₦{{ number_format($totalCost ?? 0, 2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Gross Profit</h6>
                <h4 class="fw-bold text-info">
                    ₦{{ number_format($grossProfit ?? 0, 2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Total Expenses</h6>
                <h4 class="fw-bold text-warning">
                    ₦{{ number_format($totalExpenses ?? 0, 2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Net Profit / Loss</h6>
                <h4 class="fw-bold {{ ($netProfit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    ₦{{ number_format($netProfit ?? 0, 2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Profit Margin</h6>
                <h4 class="fw-bold">
                    {{ number_format($profitMargin ?? 0, 1) }}%
                </h4>
            </div>
        </div>

    </div>

    {{-- INSIGHTS --}}
    <div class="alert alert-light border mb-4">
        <strong>Insights:</strong><br>

        Best Day:
        <strong>{{ $bestDay['date'] ?? '-' }}</strong>
        (₦{{ number_format($bestDay['profit'] ?? 0, 2) }})

        <br>

        Worst Day:
        <strong>{{ $worstDay['date'] ?? '-' }}</strong>
        (₦{{ number_format($worstDay['profit'] ?? 0, 2) }})
    </div>

    {{-- PROFIT / LOSS CHART --}}
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <strong>Financial Trend (Last 10 Days)</strong>
        </div>
        <div class="card-body">
            <div style="height:300px;">
                <canvas id="profitChart"></canvas>
            </div>
        </div>
    </div>

    {{-- EXPENSE BREAKDOWN --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Goods That Made Profit</strong>

            <a
                href="{{ route('admin.report.profit_loss.download', request()->query()) }}"
                class="btn btn-sm btn-danger"
                target="_blank"
            >
                Download PDF
            </a>
        </div>

        <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th class="text-end">Revenue (₦)</th>
                    <th class="text-end">Cost (₦)</th>
                    <th class="text-end">Profit (₦)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $goodsByProfit = $sales->groupBy(fn($item) => $item->product->name)
                        ->map(fn($items) => [
                            'quantity' => $items->sum('quantity'),
                            'revenue' => $items->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0)),
                            'cost' => $items->sum(fn($i) => ($i->product->cost_price ?? 0) * $i->quantity),
                        ])
                        ->map(fn($item) => array_merge($item, [
                            'profit' => $item['revenue'] - $item['cost']
                        ]));

                    $totalQuantity = $goodsByProfit->sum('quantity');
                    $totalRevenue  = $goodsByProfit->sum('revenue');
                    $totalCost     = $goodsByProfit->sum('cost');
                    $totalProfit   = $goodsByProfit->sum('profit');
                @endphp

                @forelse($goodsByProfit as $product => $data)
                    <tr>
                        <td>{{ $product }}</td>
                        <td>{{ $data['quantity'] }}</td>
                        <td class="text-end">₦{{ number_format($data['revenue'], 2) }}</td>
                        <td class="text-end">₦{{ number_format($data['cost'], 2) }}</td>
                        <td class="text-end 
                            {{ $data['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ₦{{ number_format($data['profit'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No goods in this period
                        </td>
                    </tr>
                @endforelse

                @if($goodsByProfit->count() > 0)
                    <tr class="fw-bold table-light">
                        <td>Total</td>
                        <td>{{ $totalQuantity }}</td>
                        <td class="text-end text-success">
                            ₦{{ number_format($totalRevenue, 2) }}
                        </td>
                        <td class="text-end text-danger">
                            ₦{{ number_format($totalCost, 2) }}
                        </td>
                        <td class="text-end {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">
                            ₦{{ number_format($totalProfit, 2) }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>


</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('profitChart').getContext('2d');

    const labels   = {!! json_encode($profitByDay->pluck('date')) !!};
    const profit   = {!! json_encode($profitByDay->pluck('profit')) !!};
    const revenue  = {!! json_encode($profitByDay->pluck('revenue')) !!};
    const expenses = {!! json_encode($profitByDay->pluck('expenses')) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Profit / Loss (₦)',
                    data: profit,
                    tension: 0.3,
                    borderWidth: 2
                },
                {
                    label: 'Revenue (₦)',
                    data: revenue,
                    tension: 0.3,
                    borderWidth: 1
                },
                {
                    label: 'Expenses (₦)',
                    data: expenses,
                    tension: 0.3,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endsection
