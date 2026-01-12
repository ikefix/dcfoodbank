
@extends('layouts.app')


@vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])

@section('content')


<div class="container">
    <h3>Welcome {{ Auth::user()->name }}</h3>
    
    <h2>Cashier Sales</h2>
<!-- 🔍 Receipt Search by Tracking ID -->
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('receipt.search') }}" method="GET" class="mb-4" target="_blank">
    <div class="input-group">
        <input type="text" name="transaction_id" class="form-control" placeholder="Enter Receipt Tracking ID" required>
        <button type="submit" class="btn btn-primary">Search Receipt</button>
    </div>
</form>
    <form class="form" method="POST" action="{{ route('purchaseitem.store') }}">
        @csrf

        <!-- Barcode Scanner Input -->
        <div class="form-group mb-3">
            <label for="barcode-input">Scan Barcode</label>
            <input type="text" id="barcode_input" class="form-control" placeholder="Scan barcode here" autofocus>
        </div>

        <!-- Product Search Input -->
        <div class="form-group">
            <label for="product_name">Search Product</label>
            <input type="text" id="product_name" class="form-control" placeholder="Search product name" autocomplete="off">
            <div id="product_suggestions" class="suggestions-box"></div>
            <small id="product-error" class="text-danger" style="display: none;">Product does not exist</small>
        </div>

        <!-- Hidden Product ID Input -->
        <input type="hidden" id="product" name="product_id">

        <!-- Product Price Display (Non-editable) -->
        <div class="form-group">
            <label for="price">Price</label>
            <input type="text" id="price" class="form-control" readonly>
        </div>

        <!-- Quantity Input -->
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" class="form-control" required min='1'>
        </div>

        <!-- Total Price Display (Non-editable) -->
        <div class="form-group">
            <label for="total_price">Total Price</label>
            <input type="text" id="total_price" class="form-control" readonly>
        </div>

        <!-- Payment Method Selection -->
        <div class="form-group">
            <label for="payment_method">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-control" required>
                <option value="">Select Payment Method</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="transfer">Bank Transfer</option>
            </select>
        </div>
    <!-- Discount Section -->
    <div class="form-group">
        <label for="discount_type">Discount Type</label>
        <select id="discount_type" name="discount_type" class="form-control">
            <option value="none">No Discount</option>
            <option value="percentage">Percentage (%)</option>
            <option value="flat">Flat (₦)</option>
        </select>
    </div>

    <div class="form-group">
        <label for="discount_value">Discount Value</label>
        <input type="number" id="discount_value" name="discount_value" class="form-control" placeholder="Enter discount value" min="0" value="0">
    </div>


        <!-- Submit Button -->
        <div class="form-submit">
            <button type="submit" class="btn-add-product">Add Product</button>
        </div>
    </form>

    <!-- Final Preview Section -->
    <div id="preview-box" class="card mt-4 d-none">
        <div class="card-header">🛒 Final Preview</div>
        <div class="card-body">
            <p><strong>Product:</strong> <span id="preview-name"></span></p>
            <p><strong>Price:</strong> ₦<span id="preview-price"></span></p>
            <p><strong>Quantity:</strong> 
                <button type="button" class="btn btn-sm btn-secondary" id="minus-btn">−</button>
                <span id="preview-quantity">1</span>
                <button type="button" class="btn btn-sm btn-secondary" id="plus-btn">+</button>
            </p>
            <p><strong>Total:</strong> ₦<span id="preview-total"></span></p>
            <form id="final-submit-form" method="POST" action="{{ route('purchaseitem.store') }}">
                @csrf
                <input type="hidden" name="product_id" id="final-product-id">
                <input type="hidden" name="quantity" id="preview-total">
                <button type="submit" class="btn btn-success">✅ Complete</button>
            </form>
        </div>
    </div>
</div>

<script>
let productsList = [];
const form = document.querySelector('.form');
const previewBox = document.querySelector('#preview-box');
const previewBody = document.querySelector('.card-body');
const finalForm = document.querySelector('#final-submit-form');
const barcodeInput = document.getElementById('barcode_input');
let scanTimeout;
let scanningTimer;
let scanningActive = false;

/* ✅ Popup Message Helper */
function showPrompt(message, type = 'info') {
    let promptBox = document.getElementById('scanPrompt');
    if (!promptBox) {
        promptBox = document.createElement('div');
        promptBox.id = 'scanPrompt';
        promptBox.style.position = 'fixed';
        promptBox.style.top = '50%';
        promptBox.style.left = '50%';
        promptBox.style.transform = 'translate(-50%, -50%)';
        promptBox.style.padding = '20px 30px';
        promptBox.style.borderRadius = '10px';
        promptBox.style.fontSize = '18px';
        promptBox.style.color = '#fff';
        promptBox.style.fontWeight = 'bold';
        promptBox.style.zIndex = '99999';
        promptBox.style.textAlign = 'center';
        document.body.appendChild(promptBox);
    }

    if (type === 'success') promptBox.style.background = '#28a745';
    else if (type === 'error') promptBox.style.background = '#dc3545';
    else promptBox.style.background = '#007bff';

    promptBox.innerHTML = message;
    promptBox.style.display = 'block';
    setTimeout(() => promptBox.style.display = 'none', 1800);
}

/* ✅ Detect Barcode Scanning */
barcodeInput.addEventListener('keydown', () => {
    if (!scanningActive) {
        scanningActive = true;
        showPrompt('🔍 Scanning in progress...', 'info');
    }
    clearTimeout(scanningTimer);
    scanningTimer = setTimeout(() => scanningActive = false, 800);
});

/* ✅ Handle Barcode or Manual Input */
barcodeInput.addEventListener('input', () => {
    clearTimeout(scanTimeout);
    scanTimeout = setTimeout(async () => {
        const query = barcodeInput.value.trim();
        if (!query) return;

        try {
            const res = await fetch(`/products/search-suggestions?query=${encodeURIComponent(query)}`);
            const data = await res.json();

            // If single product (barcode or exact name match)
            if (data.success && data.name) {
                document.querySelector('#product_name').value = data.name;
                document.querySelector('#product').value = data.id;
                document.querySelector('#price').value = data.price;
                document.querySelector('#quantity').value = 1;
                document.querySelector('#total_price').value = data.price;
                showPrompt(`✅ ${data.name} loaded successfully!`, 'success');
                barcodeInput.value = '';
                return;
            }

            // If multiple results (manual typing)
            if (Array.isArray(data) && data.length > 0) {
                const suggestionBox = document.querySelector('#product_suggestions');
                suggestionBox.innerHTML = '';
                data.forEach(prod => {
                    const item = document.createElement('div');
                    item.classList.add('suggestion-item');
                    item.textContent = `${prod.name} (₦${prod.price})`;
                    item.style.cursor = 'pointer';
                    item.onclick = () => {
                        document.querySelector('#product_name').value = prod.name;
                        document.querySelector('#product').value = prod.id;
                        document.querySelector('#price').value = prod.price;
                        document.querySelector('#quantity').value = 1;
                        document.querySelector('#total_price').value = prod.price;
                        suggestionBox.innerHTML = '';
                    };
                    suggestionBox.appendChild(item);
                });
            } else {
                showPrompt('❌ Product not found!', 'error');
            }
        } catch (error) {
            console.error(error);
            showPrompt('⚠️ Error fetching product details.', 'error');
        } finally {
            barcodeInput.value = '';
        }
    }, 500);
});

/* ✅ Add Product to Cart */
form.addEventListener('submit', function (e) {
    e.preventDefault();

    const name = document.querySelector('#product_name').value;
    const price = parseFloat(document.querySelector('#price').value);
    const productId = document.querySelector('#product').value;
    const quantity = parseInt(document.querySelector('#quantity').value);
    const paymentMethod = document.querySelector('#payment_method').value;
    const discountType = document.querySelector('#discount_type').value || 'none';
    const discountValue = parseFloat(document.querySelector('#discount_value').value) || 0;

    if (!productId || quantity < 1 || !paymentMethod) return;

    fetch(`/api/product-stock/${productId}`)
        .then(res => res.json())
        .then(data => {
            const availableStock = data.stock;

            if (quantity > availableStock) {
                showPrompt(`⚠️ Not enough stock. Available: ${availableStock}`, 'error');
                return;
            }

            // Push product with its own discount
            productsList.push({
                name,
                price,
                productId,
                quantity,
                paymentMethod,
                stock: availableStock,
                discount_type: discountType,
                discount_value: discountValue
            });

            // Reset form inputs
            document.querySelector('#product_name').value = '';
            document.querySelector('#product').value = '';
            document.querySelector('#price').value = '';
            document.querySelector('#quantity').value = '';
            document.querySelector('#total_price').value = '';
            document.querySelector('#payment_method').value = '';
            document.querySelector('#discount_type').value = 'none';
            document.querySelector('#discount_value').value = 0;

            // Update preview and final form
            updateCartPreview();
            updateFinalForm();
            previewBox.classList.remove('d-none');
            showPrompt(`${name} added to cart 🛒`, 'success');
        })
        .catch(err => {
            console.error(err);
            showPrompt('⚠️ Could not check stock', 'error');
        });
});


function updateCartPreview() {
    previewBody.innerHTML = '';
    let totalSum = 0;       // Total without discounts
    let finalTotal = 0;     // Total after discounts
    let totalDiscount = 0;  // Total discount applied

    productsList.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        let discountedSubtotal = subtotal;

        // Calculate discount per product
        let discountAmount = 0;
        if (item.discount_type === 'percentage') {
            discountAmount = (subtotal * item.discount_value / 100);
        } else if (item.discount_type === 'flat') {
            discountAmount = item.discount_value;
        }
        discountedSubtotal -= discountAmount;
        if (discountedSubtotal < 0) discountedSubtotal = 0;

        totalSum += subtotal;
        finalTotal += discountedSubtotal;
        totalDiscount += discountAmount;

        const itemDiv = document.createElement('div');
        itemDiv.classList.add('mb-2', 'border-bottom', 'pb-2');
        itemDiv.innerHTML = `
            <p><strong>Product:</strong> ${item.name}</p>
            <p><strong>Price:</strong> ₦${item.price.toFixed(2)}</p>
            <p><strong>Quantity:</strong>
                <button type="button" class="btn btn-sm btn-secondary minus-btn" data-index="${index}">−</button>
                <span id="preview-quantity-${index}">${item.quantity}</span>
                <button type="button" class="btn btn-sm btn-secondary plus-btn" data-index="${index}">+</button>
            </p>
            <p><strong>Discount:</strong> ${
                item.discount_type === 'percentage' ? item.discount_value + '%' :
                item.discount_type === 'flat' ? '₦' + item.discount_value : '₦0'
            }</p>
            <p><strong>Total after Discount:</strong> ₦<span id="preview-total-${index}">${discountedSubtotal.toFixed(2)}</span></p>
            <p><strong>Payment:</strong>
                <select class="form-control form-control-sm payment-select" data-index="${index}">
                    <option value="cash" ${item.paymentMethod === 'cash' ? 'selected' : ''}>Cash</option>
                    <option value="card" ${item.paymentMethod === 'card' ? 'selected' : ''}>Card</option>
                    <option value="transfer" ${item.paymentMethod === 'transfer' ? 'selected' : ''}>Transfer</option>
                </select>
            </p>
            <button type="button" class="btn btn-sm btn-danger remove-btn" data-index="${index}">❌ Remove</button>
        `;
        previewBody.appendChild(itemDiv);
    });

    // Show totals including total discount
    const totalDiv = document.createElement('div');
    totalDiv.id = 'cart-total-div';
    totalDiv.innerHTML = `
        <p><strong>Subtotal: ₦${totalSum.toFixed(2)}</strong></p>
        <p><strong>Total Discount: ₦${totalDiscount.toFixed(2)}</strong></p>
        <p><strong>Final Total after Discounts: ₦<span id="cart-total">${finalTotal.toFixed(2)}</span></strong></p>
    `;
    previewBody.appendChild(totalDiv);
    previewBody.appendChild(finalForm);

    attachQtyListeners();
    attachRemoveListeners();
    attachPaymentListeners();
}



function attachQtyListeners() {
    document.querySelectorAll('.plus-btn').forEach(btn => {
        btn.onclick = function () {
            const index = parseInt(this.dataset.index);
            productsList[index].quantity++;
            refreshQty(index);
        };
    });

    document.querySelectorAll('.minus-btn').forEach(btn => {
        btn.onclick = function () {
            const index = parseInt(this.dataset.index);
            if (productsList[index].quantity > 1) {
                productsList[index].quantity--;
                refreshQty(index);
            }
        };
    });
}

function attachRemoveListeners() {
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.onclick = function () {
            const index = parseInt(this.dataset.index);
            showPrompt(`${productsList[index].name} removed`, 'error');
            productsList.splice(index, 1);
            updateCartPreview();
            updateFinalForm();
        };
    });
}

function attachPaymentListeners() {
    document.querySelectorAll('.payment-select').forEach(select => {
        select.onchange = function () {
            const index = parseInt(this.dataset.index);
            productsList[index].paymentMethod = this.value;
            updateFinalForm();
        };
    });
}

function refreshQty(index) {
    const item = productsList[index];
    const newTotal = item.price * item.quantity;
    document.getElementById(`preview-quantity-${index}`).textContent = item.quantity;
    document.getElementById(`preview-total-${index}`).textContent = newTotal.toFixed(2);
    updateCartTotal();
    updateFinalForm();
}

function updateCartTotal() {
    let totalSum = 0;

    productsList.forEach(item => {
        let subtotal = item.price * item.quantity;

        // Apply individual product discount
        if (item.discount_type === 'percentage') {
            subtotal -= (subtotal * item.discount_value / 100);
        } else if (item.discount_type === 'flat') {
            subtotal -= item.discount_value;
        }

        if (subtotal < 0) subtotal = 0;

        totalSum += subtotal;
    });

    const totalSpan = document.querySelector('#cart-total');
    if (totalSpan) totalSpan.textContent = totalSum.toFixed(2);
}

function updateFinalForm() {
    finalForm.innerHTML = `@csrf`;

    productsList.forEach((item, index) => {
        finalForm.innerHTML += `
            <input type="hidden" name="products[${index}][product_id]" value="${item.productId}">
            <input type="hidden" name="products[${index}][quantity]" value="${item.quantity}">
            <input type="hidden" name="products[${index}][payment_method]" value="${item.paymentMethod}">
            <input type="hidden" name="products[${index}][discount_type]" value="${item.discount_type || 'none'}">
            <input type="hidden" name="products[${index}][discount_value]" value="${item.discount_value || 0}">
        `;
    });

    if (productsList.length) {
        finalForm.innerHTML += `<button type="submit" class="btn btn-success mt-2">✅ Complete</button>`;
    }
}

finalForm.addEventListener('submit', function (e) {
    e.preventDefault();

    if (productsList.length === 0) {
        showPrompt('⚠️ Add at least one product', 'error');
        return;
    }

    // Prepare payload with individual product discounts
    const payload = {
            products: productsList.map(item => ({
            product_id: item.productId,
            quantity: item.quantity,
            discount_type: item.discount_type || 'none',
            discount_value: item.discount_value || 0,
        })),
        payment_method: productsList[0]?.paymentMethod || 'cash' // top-level
    };


    fetch(finalForm.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.open(`/purchaseitem/receipt/${data.receipt_id}`, '_blank');
            productsList = [];
            updateCartPreview();
            showPrompt('✅ Sale completed successfully!', 'success');
        } else {
            // Handle Laravel validation errors or messages properly
            let msg = '';
            if (typeof data.message === 'object') {
                msg = Object.values(data.message).flat().join(', ');
            } else {
                msg = data.message || 'Failed to complete sale';
            }
            showPrompt('❌ ' + msg, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showPrompt('⚠️ Network/server error', 'error');
    });
});


</script>




@endsection





