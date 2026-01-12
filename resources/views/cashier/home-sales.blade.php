@extends('layouts.app')

@vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2>Cashier Sales</h2>
        <div>
            <button id="downloadPDF" class="btn btn-success btn-sm">📥 Download PDF</button>
            <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print</button>
        </div>
    </div>
<div class="card p-3 mb-3 no-print" style="background:#f8f9fa;">
    <form method="GET" action="{{ route('cashier.home-sales') }}" class="row g-2">
        <div class="col-md-4">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control"
                   value="{{ request('start_date') }}">
        </div>

        <div class="col-md-4">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control"
                   value="{{ request('end_date') }}">
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter Sales</button>
        </div>
    </form>

    <!-- Quick Filters -->
    <div class="mt-2 d-flex gap-2">
        <a href="{{ route('cashier.home-sales', ['quick' => 'today']) }}" class="btn btn-outline-secondary btn-sm">Today</a>
        <a href="{{ route('cashier.home-sales', ['quick' => 'yesterday']) }}" class="btn btn-outline-secondary btn-sm">Yesterday</a>
        <a href="{{ route('cashier.home-sales', ['quick' => 'week']) }}" class="btn btn-outline-secondary btn-sm">This Week</a>
        <a href="{{ route('cashier.home-sales', ['quick' => 'month']) }}" class="btn btn-outline-secondary btn-sm">This Month</a>
    </div>
</div>

    <!-- ✅ Report Header -->
    <div class="mb-4 text-center">
        <h4><strong>Cashier Sales Report</strong></h4>
        <p>
            <strong>Cashier:</strong> {{ Auth::user()->name }} <br>
            <strong>Date:</strong> {{ now()->format('F j, Y') }}
        </p>
    </div>

@php
    // Calculate total directly in the view (safe for small datasets)
    $grandTotal = 0;
    foreach ($sales as $sale) {
        $priceAfterDiscount = $sale->total_price;

        if (!empty($sale->discount_value) && $sale->discount_value > 0) {
            $priceAfterDiscount = $sale->total_price - $sale->discount;
        }

        $grandTotal += $priceAfterDiscount;
    }
@endphp

<h1 style="margin-bottom: 20px; font-size: 24px; color: #28a745; text-align:center;" class="total">
    Total Sales: ₦{{ number_format($grandTotal, 2) }}
</h1>


    <!-- ✅ Sales Table -->
    <table class="table table-bordered" id="salesTable">
        <thead class="table-dark">
            <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Payment Method</th>
                <th>Date</th>
                <th>Shop</th>
                <th>Transaction ID</th>
                <th>Discount Value</th>
                <th>Customer Name</th>
                <th>Customer Phone</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->product->name ?? 'Product Deleted' }}</td>
                    <td>{{ $sale->product->category->name ?? 'Category Missing' }}</td>
                    <td>{{ $sale->quantity }}</td>
                    <td>
                        @if(!empty($sale->discount_value) && $sale->discount_value > 0)
                            <span style="text-decoration: line-through; color: red;">
                                ₦{{ number_format($sale->total_price, 2) }}
                            </span><br>
                            <span style="color: #28a745; font-weight: bold;">
                                ₦{{ number_format($sale->total_price - $sale->discount, 2) }}
                            </span>
                        @else
                            <span style="color: #000;">
                                ₦{{ number_format($sale->total_price, 2) }}
                            </span>
                        @endif
                    </td>
                    <td>{{ ucfirst($sale->payment_method) }}</td>
                    <td>{{ $sale->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $sale->shop->name ?? 'Unknown Shop' }}</td>
                    <td>{{ $sale->transaction_id ?? 'Unknown Transaction' }}</td>
                    <td>{{ $sale->discount_value ?? 'Unknown Transaction' }}</td>
                    <td>{{ $sale->customer_name ?? 'Unknown Transaction' }}</td>
                    <td>{{ $sale->customer_phone ?? 'Empty' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No sales found for today</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- ✅ PDF Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<!-- ✅ PDF Generation Script -->
<script>
document.getElementById("downloadPDF").addEventListener("click", function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Add header
    doc.setFontSize(16);
    doc.text("Cashier Sales Report", 14, 15);

    // Cashier info
    doc.setFontSize(11);
    doc.text("Cashier: {{ Auth::user()->name }}", 14, 23);
    doc.text("Date: {{ now()->format('F j, Y') }}", 14, 30);
    doc.text("<h1 style="margin-bottom: 20px; font-size: 24px; color: #28a745; text-align:center;" class="total">
    Total Sales: ₦{{ number_format($grandTotal, 2) }}
</h1>")

    // Get table rows
    const table = document.getElementById("salesTable");
    const rows = [];
    const headers = [];
    
    table.querySelectorAll("thead th").forEach(th => headers.push(th.innerText));
    table.querySelectorAll("tbody tr").forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            // Clean currency and remove special chars
            let text = td.innerText.replace(/₦/g, "N"); // Replace ₦ with N for PDF
            row.push(text);
        });
        rows.push(row);
    });

    // Add table to PDF
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 40,
        styles: {
            fontSize: 10,
            cellPadding: 2,
        },
        headStyles: { fillColor: [40, 40, 40] },
        alternateRowStyles: { fillColor: [245, 245, 245] },
    });

    // Save the file
    doc.save("cashier_sales_{{ Auth::user()->name }}.pdf");
});
</script>


<!-- ✅ Print Styling -->
<style>
@media print {
.total{
    display:block;
}

    .no-print {
        display: none !important;
    }

    body {
        background: #fff;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #000;
        padding: 6px;
        text-align: left;
    }

    h4 {
        margin-bottom: 5px;
    }
}
</style>
@endsection