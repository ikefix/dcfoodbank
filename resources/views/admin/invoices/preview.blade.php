<div class="invoice-letter">
    <img src="{{ asset('logo.png') }}" class="logo">

    <h2>INVOICE</h2>

    <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
    <p><strong>Customer:</strong> {{ $invoice->customer->name }}</p>

    <hr>

    {{-- Invoice items table --}}

    <h3>Total: ₦{{ number_format($invoice->total, 2) }}</h3>

    <a href="{{ route('admin.invoices.download', $invoice->id) }}"
       class="btn btn-dark">
        Download PDF
    </a>
</div>
