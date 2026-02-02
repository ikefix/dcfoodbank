@extends('layouts.adminapp')

@section('admincontent')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Create Invoice</h3>
                </div>
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ auth()->user()->role === 'admin' ? route('admin.invoices.store') : (auth()->user()->role === 'manager' ? route('manager.invoices.store') : route('cashier.invoices.store')) }}" method="POST">
                        @csrf

                        {{-- Customer Selection --}}
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Select Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">-- Choose Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        data-email="{{ $customer->email }}"
                                        data-phone="{{ $customer->phone }}"
                                        data-company="{{ $customer->company }}">
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Customer Info --}}
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Email:</strong> <span id="customer_email">-</span></div>
                            <div class="col-md-4"><strong>Phone:</strong> <span id="customer_phone">-</span></div>
                            <div class="col-md-4"><strong>Company:</strong> <span id="customer_company">-</span></div>
                        </div>

                        {{-- Shop Selection --}}
                        <div class="mb-3">
                            <label for="shop_id" class="form-label">Select Shop</label>
                            <select name="shop_id" id="shop_id" class="form-select" required>
                                <option value="">-- Choose Shop --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Product Selection --}}
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Select Product</label>
                            <select name="goods[product_id]" id="product_id" class="form-select" required disabled>
                                <option value="">-- Choose Product --</option>
                            </select>
                        </div>

                        <div class="row">
                            {{-- Price --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price</label>
                                <input type="text" id="product_price" class="form-control" readonly>
                            </div>

                            {{-- Quantity --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="goods[quantity]" id="product_quantity" class="form-control" min="1" value="1">
                            </div>

                            {{-- Total --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total</label>
                                <input type="text" name="goods[total_price]" id="product_total" class="form-control" readonly>
                            </div>
                        </div>

                        {{-- Discount + Tax --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount (Optional)</label>
                                <input type="number" name="discount" id="discount" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax (Optional)</label>
                                <input type="number" name="tax" id="tax" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        {{-- Final Total --}}
                        <div class="mb-3">
                            <label class="form-label">Final Total</label>
                            <input type="text" name="total" id="final_total" class="form-control" readonly>
                        </div>

                        {{-- Payment Type --}}
                        <div class="mb-3">
                            <label class="form-label">Payment Type</label>
                            <select name="payment_type" id="payment_type" class="form-select" required>
                                <option value="full">Select Plan</option>
                                <option value="full">Full Payment</option>
                                <option value="part">Part Payment</option>
                            </select>
                        </div>

                        {{-- Amount Paid --}}
                        <div class="mb-3 d-none" id="amount_paid_wrapper">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" name="amount_paid" id="amount_paid" class="form-control" min="0" value="0">
                        </div>

                        {{-- Balance --}}
                        <div class="mb-3">
                            <label class="form-label">Balance</label>
                            <input type="text" name="balance" id="balance" class="form-control" readonly>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Create Invoice</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script>
    const customers = document.querySelector('#customer_id');
    const emailSpan = document.querySelector('#customer_email');
    const phoneSpan = document.querySelector('#customer_phone');
    const companySpan = document.querySelector('#customer_company');

    const shopSelect = document.querySelector('#shop_id');
    const productSelect = document.querySelector('#product_id');
    const productPrice = document.querySelector('#product_price');
    const quantityInput = document.querySelector('#product_quantity');
    const totalInput = document.querySelector('#product_total');
    const discountInput = document.querySelector('#discount');
    const taxInput = document.querySelector('#tax');
    const finalTotalInput = document.querySelector('#final_total');

    const paymentType = document.querySelector('#payment_type');
    const amountPaidWrapper = document.querySelector('#amount_paid_wrapper');
    const amountPaidInput = document.querySelector('#amount_paid');
    const balanceInput = document.querySelector('#balance');

    let products = @json($products);

    // CUSTOMER AUTOFILL
    customers.addEventListener('change', function () {
        const selected = customers.selectedOptions[0];
        emailSpan.textContent = selected.dataset.email;
        phoneSpan.textContent = selected.dataset.phone;
        companySpan.textContent = selected.dataset.company;
    });

    // FILTER PRODUCT BY SHOP
    shopSelect.addEventListener('change', function () {
        const shopId = Number(this.value);
        productSelect.innerHTML = '<option value="">-- Choose Product --</option>';

        if (shopId) {
            productSelect.disabled = false;

            products.filter(p => Number(p.shop_id) === shopId)
                .forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.dataset.price = p.price;
                    opt.textContent = p.name;
                    productSelect.appendChild(opt);
                });
        } else {
            productSelect.disabled = true;
        }

        resetPriceTotal();
    });

    // PRICE + TOTAL CALC
    productSelect.addEventListener('change', updatePriceTotal);
    quantityInput.addEventListener('input', updatePriceTotal);
    discountInput.addEventListener('input', updateFinalTotal);
    taxInput.addEventListener('input', updateFinalTotal);
    amountPaidInput.addEventListener('input', calculateBalance);
    paymentType.addEventListener('change', toggleAmountPaid);

    function updatePriceTotal() {
        const selected = productSelect.selectedOptions[0];
        const price = parseFloat(selected?.dataset?.price || 0);
        const qty = Number(quantityInput.value) || 1;

        productPrice.value = price.toFixed(2);
        totalInput.value = (price * qty).toFixed(2);

        updateFinalTotal();
    }

    function updateFinalTotal() {
        const total = Number(totalInput.value) || 0;
        const discount = Number(discountInput.value) || 0;
        const tax = Number(taxInput.value) || 0;

        const finalTotal = total - discount + tax;
        finalTotalInput.value = finalTotal.toFixed(2);

        calculateBalance();
    }

function toggleAmountPaid() {
    const finalTotal = Number(finalTotalInput.value) || 0;

    if (paymentType.value === "part") {
        amountPaidWrapper.classList.remove('d-none');
        amountPaidInput.value = 0;
    } else {
        // FULL PAYMENT
        amountPaidWrapper.classList.add('d-none');
        amountPaidInput.value = finalTotal; // ✅ PAY EVERYTHING
    }

    calculateBalance();
}


    function calculateBalance() {
        const finalTotal = Number(finalTotalInput.value) || 0;
        const amountPaid = Number(amountPaidInput.value) || 0;

        const balance = finalTotal - amountPaid;
        balanceInput.value = balance.toFixed(2);
    }

    function resetPriceTotal() {
        productPrice.value = '';
        totalInput.value = '';
        finalTotalInput.value = '';
        balanceInput.value = '';
    }
</script>
@endsection
