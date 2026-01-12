@extends('layouts.app')

@vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css'])

@section('content')

<div class="container">
    <h3 class="mb-3">Welcome {{ Auth::user()->name }}</h3>
    <h2 class="mb-4">🧾 Cashier Sales</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- 🔍 Receipt Search -->
    <form action="{{ route('receipt.search') }}" method="GET" class="mb-4" target="_blank">
        <div class="input-group">
            <input type="text" name="transaction_id" class="form-control" placeholder="Enter Receipt Tracking ID" required>
            <button type="submit" class="btn btn-primary">Search Receipt</button>
        </div>
    </form>

    <form class="form" method="POST" action="{{ route('purchaseitem.store') }}">
        @csrf

        <!-- 🧍 Customer Info -->
        <div class="card mb-4">
            <div class="card-header fw-bold">👤 Customer Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Customer Name (Optional)</label>
                        <input type="text" id="customer_name" class="form-control" placeholder="Full name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Customer Phone (Optional)</label>
                        <input type="text" id="customer_phone" class="form-control" placeholder="080xxxxxxxx">
                    </div>
                </div>
            </div>
        </div>

        <!-- 📦 Product Section -->
        <div class="card mb-4">
            <div class="card-header fw-bold">📦 Product Details</div>
            <div class="card-body">

                <div class="mb-3">
                    <label>Scan Barcode</label>
                    <input type="text" id="barcode_input" class="form-control" placeholder="Scan barcode here" autofocus>
                </div>

                <div class="mb-3">
                    <label>Search Product</label>
                    <input type="text" id="product_name" class="form-control" placeholder="Search product">
                    <div id="product_suggestions" class="suggestions-box"></div>
                </div>

                <input type="hidden" id="product">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Price</label>
                        <input type="text" id="price" class="form-control" readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Quantity</label>
                        <input type="number" id="quantity" class="form-control" min="1">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Total</label>
                        <input type="text" id="total_price" class="form-control" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- 💳 Payment & Discount -->
        <div class="card mb-4">
            <div class="card-header fw-bold">💳 Payment & Discount</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Payment Method</label>
                        <select id="payment_method" class="form-control">
                            <option value="">Select</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Discount Type</label>
                        <select id="discount_type" class="form-control">
                            <option value="none">None</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="flat">Flat (₦)</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Discount Value</label>
                        <input type="number" id="discount_value" class="form-control" value="0" min="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-dark w-100">
                    ➕ Add Product to Cart
                </button>
            </div>
        </div>
    </form>

    <!-- 🛒 Preview -->
    <div id="preview-box" class="card d-none">
        <div class="card-header fw-bold">🛒 Cart Preview</div>
        <div class="card-body"></div>

        <form id="final-submit-form" method="POST" action="{{ route('purchaseitem.store') }}">
            @csrf
        </form>
    </div>
</div>

<script>
let productsList = [];
const form = document.querySelector('.form');
const previewBox = document.querySelector('#preview-box');
const previewBody = document.querySelector('#preview-box .card-body');

const finalForm = document.querySelector('#final-submit-form');
const barcodeInput = document.getElementById('barcode_input');

const customerNameInput = document.getElementById('customer_name');
const customerPhoneInput = document.getElementById('customer_phone');

let scanTimeout;
let scanningTimer;
let scanningActive = false;

/* ✅ Popup Message Helper */
function showPrompt(message, type = 'info') {
    let promptBox = document.getElementById('scanPrompt');
    if (!promptBox) {
        promptBox = document.createElement('div');
        promptBox.id = 'scanPrompt';
        Object.assign(promptBox.style, {
            position: 'fixed',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            padding: '20px 30px',
            borderRadius: '10px',
            fontSize: '18px',
            color: '#fff',
            fontWeight: 'bold',
            zIndex: '99999',
            textAlign: 'center'
        });
        document.body.appendChild(promptBox);
    }

    promptBox.style.background =
        type === 'success' ? '#28a745' :
        type === 'error' ? '#dc3545' : '#007bff';

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

/* ✅ Handle Barcode / Manual Input */
barcodeInput.addEventListener('input', () => {
    clearTimeout(scanTimeout);
    scanTimeout = setTimeout(async () => {
        const query = barcodeInput.value.trim();
        if (!query) return;

        try {
            const res = await fetch(`/products/search-suggestions?query=${encodeURIComponent(query)}`);
            const data = await res.json();

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

    if (!productId || quantity < 1 || !paymentMethod) {
        showPrompt('⚠️ Fill product & payment method', 'error');
        return;
    }

    fetch(`/api/product-stock/${productId}`)
        .then(res => res.json())
        .then(data => {
            const availableStock = data.stock;
            if (quantity > availableStock) {
                showPrompt(`⚠️ Not enough stock. Available: ${availableStock}`, 'error');
                return;
            }

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

            // Reset inputs
            document.querySelector('#product_name').value = '';
            document.querySelector('#product').value = '';
            document.querySelector('#price').value = '';
            document.querySelector('#quantity').value = '';
            document.querySelector('#total_price').value = '';
            document.querySelector('#payment_method').value = '';
            document.querySelector('#discount_type').value = 'none';
            document.querySelector('#discount_value').value = 0;

            updateCartPreview();
            previewBox.classList.remove('d-none');
            showPrompt(`${name} added to cart 🛒`, 'success');
        })
        .catch(err => {
            console.error(err);
            showPrompt('⚠️ Could not check stock', 'error');
        });
});

/* ✅ Update Cart Preview & Attach Listeners */
function updateCartPreview() {
    previewBody.innerHTML = '';
    let totalSum = 0, finalTotal = 0, totalDiscount = 0;

    productsList.forEach((item, index) => {
        let subtotal = item.price * item.quantity;
        let discountAmount = 0;

        if (item.discount_type === 'percentage') discountAmount = subtotal * item.discount_value / 100;
        else if (item.discount_type === 'flat') discountAmount = item.discount_value;

        let discountedSubtotal = subtotal - discountAmount;
        if (discountedSubtotal < 0) discountedSubtotal = 0;

        totalSum += subtotal;
        finalTotal += discountedSubtotal;
        totalDiscount += discountAmount;

        const itemDiv = document.createElement('div');
        itemDiv.classList.add('mb-3', 'p-2', 'border', 'rounded', 'd-flex', 'justify-content-between', 'align-items-center');

        itemDiv.innerHTML = `
            <div>
                <p class="mb-1"><strong>${item.name}</strong></p>
                <p class="mb-0">₦${item.price.toFixed(2)} × ${item.quantity} - Discount: ₦${discountAmount.toFixed(2)}</p>
            </div>
            <div class="d-flex flex-column align-items-end">
                <select class="form-control form-control-sm mb-2 payment-select" data-index="${index}">
                    <option value="cash" ${item.paymentMethod==='cash'?'selected':''}>Cash</option>
                    <option value="card" ${item.paymentMethod==='card'?'selected':''}>Card</option>
                    <option value="transfer" ${item.paymentMethod==='transfer'?'selected':''}>Transfer</option>
                </select>
                <button type="button" class="btn btn-sm btn-danger remove-btn" data-index="${index}">❌ Remove</button>
            </div>
        `;
        previewBody.appendChild(itemDiv);
    });

    const totalDiv = document.createElement('div');
    totalDiv.id = 'cart-total-div';
    totalDiv.classList.add('mt-3', 'p-2', 'border-top');
    totalDiv.innerHTML = `
        <p><strong>Subtotal:</strong> ₦${totalSum.toFixed(2)}</p>
        <p><strong>Total Discount:</strong> ₦${totalDiscount.toFixed(2)}</p>
        <p><strong>Final Total:</strong> ₦${finalTotal.toFixed(2)}</p>
    `;
    previewBody.appendChild(totalDiv);

    previewBody.appendChild(finalForm);

    attachRemoveListeners();
    attachPaymentListeners();

    finalForm.innerHTML = `@csrf`;
    productsList.forEach((item,index)=>{
        finalForm.innerHTML += `
            <input type="hidden" name="products[${index}][product_id]" value="${item.productId}">
            <input type="hidden" name="products[${index}][quantity]" value="${item.quantity}">
            <input type="hidden" name="products[${index}][payment_method]" value="${item.paymentMethod}">
            <input type="hidden" name="products[${index}][discount_type]" value="${item.discount_type || 'none'}">
            <input type="hidden" name="products[${index}][discount_value]" value="${item.discount_value || 0}">
        `;
    });

    if (productsList.length) finalForm.innerHTML += `<button type="submit" class="btn btn-success mt-3 w-100">✅ Complete Sale</button>`;
}


function attachRemoveListeners() {
    document.querySelectorAll('.remove-btn').forEach(btn=>{
        btn.onclick = function(){
            const index = parseInt(this.dataset.index);
            showPrompt(`${productsList[index].name} removed`, 'error');
            productsList.splice(index,1);
            updateCartPreview();
        };
    });
}

function attachPaymentListeners() {
    document.querySelectorAll('.payment-select').forEach(sel=>{
        sel.onchange = function(){
            const index = parseInt(this.dataset.index);
            productsList[index].paymentMethod = this.value;
            updateCartPreview();
        };
    });
}

/* ✅ Final Submission */
finalForm.addEventListener('submit', function(e){
    e.preventDefault();

    if(!productsList.length){
        showPrompt('⚠️ Add at least one product', 'error');
        return;
    }

    const payload = {
        customer_name: customerNameInput?.value || null,
        customer_phone: customerPhoneInput?.value || null,
        products: productsList.map(item=>({
            product_id: item.productId,
            quantity: item.quantity,
            discount_type: item.discount_type || 'none',
            discount_value: item.discount_value || 0,
            payment_method: item.paymentMethod
        })),
    payment_method: productsList[0]?.paymentMethod || null  // top-level if backend needs it
    };

    fetch(finalForm.action,{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'Accept':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            window.open(`/purchaseitem/receipt/${data.receipt_id}`,'_blank');
            productsList = [];
            updateCartPreview();
            showPrompt('✅ Sale completed successfully!', 'success');
        } else {
            let msg = '';
            if(typeof data.message==='object') msg = Object.values(data.message).flat().join(', ');
            else msg = data.message || 'Failed to complete sale';
            showPrompt('❌ '+msg,'error');
        }
    })
    .catch(err=>{
        console.error(err);
        showPrompt('⚠️ Network/server error','error');
    });
});
</script>


@endsection
