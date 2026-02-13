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
                <h6 class="text-muted">Total Expenses</h6>
                <h4 class="fw-bold text-warning">
                    ₦{{ number_format($totalExpenses ?? 0, 2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 text-center bg-light">
                <h6 class="text-muted">Net Profit / Loss</h6>
                <h4 class="fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    ₦{{ number_format($netProfit ?? 0, 2) }}
                </h4>
            </div>
        </div>

    </div>

    {{-- PROFIT / LOSS CHART --}}
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <strong>Profit Trend (Daily)</strong>
        </div>
        <div class="card-body">
            <canvas id="profitChart" style="height:300px;"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('profitChart').getContext('2d');

    const labels = {!! json_encode($profitByDay->pluck('date')) !!};
    const data = {!! json_encode($profitByDay->pluck('profit')) !!};

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Profit / Loss (₦)',
                data: data,
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endsection
