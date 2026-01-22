@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h3>Edit Payment - Invoice {{ $invoice->invoice_number }}</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('cashier.invoices.update-payment', $invoice->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Customer</label>
            <input type="text" class="form-control" value="{{ $invoice->customer->name }}" readonly>
        </div>

        <div class="mb-3">
            <label>Shop</label>
            <input type="text" class="form-control" value="{{ $invoice->shop->name }}" readonly>
        </div>

        <div class="mb-3">
            <label>Total Invoice Amount</label>
            <input type="text" class="form-control" value="{{ number_format($invoice->total, 2) }}" readonly>
        </div>

        <div class="mb-3">
            <label>Amount Already Paid</label>
            <input type="text" class="form-control" value="{{ number_format($invoice->amount_paid, 2) }}" readonly>
        </div>

        <div class="mb-3">
            <label>Remaining Balance</label>
            <input type="text" id="remaining_balance" class="form-control" value="{{ number_format($invoice->balance, 2) }}" readonly>
        </div>

        {{-- Payment Type --}}
        <div class="mb-3">
            <label>Payment Type</label>
            <select name="payment_type" id="payment_type" class="form-control" required>
                <option value="full" {{ $invoice->balance <= 0 ? 'selected' : '' }}>Full Payment</option>
                <option value="part" {{ $invoice->balance > 0 ? 'selected' : '' }}>Part Payment</option>
            </select>
        </div>

        {{-- Add Payment --}}
        <div class="mb-3">
            <label>Payment Amount</label>
            <input type="number" name="amount_paid" id="amount_paid" class="form-control" min="0" max="{{ $invoice->balance }}" value="{{ $invoice->balance }}">
        </div>

        <div class="mb-3">
            <label>New Balance</label>
            <input type="text" id="new_balance" class="form-control" readonly>
        </div>

        <button type="submit" class="btn btn-success">Update Payment</button>
        <a href="{{ route('cashier.invoices.owing') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

<script>
    const amountPaidInput = document.querySelector('#amount_paid');
    const remainingBalance = Number('{{ $invoice->balance }}');
    const newBalanceInput = document.querySelector('#new_balance');

    function calculateNewBalance() {
        const paid = Number(amountPaidInput.value) || 0;
        const balance = remainingBalance - paid;
        newBalanceInput.value = balance.toFixed(2);
    }

    amountPaidInput.addEventListener('input', calculateNewBalance);
    window.addEventListener('load', calculateNewBalance);
</script>
@endsection
