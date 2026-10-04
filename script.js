// Stores items added to the cart: { id: {id, name, price, stock, qty} }
const cart = {};

// Converts numbers into Indonesian Rupiah format
function formatRupiah(number) { 
    return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
}

// Renders or updates the checkout cart display
function renderCart() {
    const cartContainer = document.getElementById('cart_container');
    const totalElem = document.getElementById('checkout_total');
    const items = Object.values(cart);

    // If cart is empty, show empty cart message
    if (items.length === 0) {
        cartContainer.innerHTML = '<span class="empty_cart_msg">No items in cart</span>';
        if (totalElem) totalElem.textContent = 'Rp0';
        return;
    }

    let total = 0;
    cartContainer.innerHTML = '';

    // For each item in the cart
    items.forEach(item => {
        const subtotal = item.price * item.qty;
        total += subtotal;

        const cartItem = document.createElement('div');
        cartItem.className = 'cart_item';
        cartItem.innerHTML = `
            <div class="cart_item_info">
                <span>${item.name}</span>
                <span>${formatRupiah(item.price)}</span>
            </div>
            <div class="cart_item_qty">
                <button type="button" class="qty_btn" onclick="updateQty('${item.id}', -1)">-</button>
                <span>${item.qty}</span>
                <button type="button" class="qty_btn" onclick="updateQty('${item.id}', 1)">+</button>
            </div>
            <div class="cart_item_price">
                <span>${formatRupiah(subtotal)}</span>
            </div>
        `;
        cartContainer.appendChild(cartItem);
    });

    if (totalElem) {
        totalElem.textContent = formatRupiah(total);
    }
}

// Adds a product to the cart or increments its quantity if already present
function addToCart(id, name, price, stock) {
    const numPrice = Number(price);
    const numStock = Number(stock);

    if (numStock <= 0) {
        alert('This product is out of stock!');
        return;
    }

    if (cart[id]) {
        if (cart[id].qty < numStock) {
            cart[id].qty++;
        } else {
            alert(`Cannot add more than available stock (${numStock})!`);
            return;
        }
    } else {
        cart[id] = {
            id: id,
            name: name,
            price: numPrice,
            stock: numStock,
            qty: 1
        };
    }
    renderCart();
}

// Updates the quantity of a product in the cart
function updateQty(id, delta) {
    if (!cart[id]) return;

    const newQty = cart[id].qty + delta;
    if (newQty <= 0) {
        delete cart[id];
    } else if (newQty > cart[id].stock) {
        alert(`Cannot add more than available stock (${cart[id].stock})!`);
        return;
    } else {
        cart[id].qty = newQty;
    }
    renderCart();
}

// Processes the checkout: sends cart items to backend to deduct product stock from database
async function processPayment() {
    const items = Object.values(cart);

    // Validate that the cart contains at least one item
    if (items.length === 0) {
        alert('Cart is empty! Please add products before checking out.');
        return;
    }

    // Disable pay button temporarily to prevent multiple submissions
    const payBtn = document.querySelector('.pay_btn');
    if (payBtn) payBtn.disabled = true;

    try {
        // Send cart data as a JSON payload to the checkout backend endpoint
        const response = await fetch('actions/do_checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ items: items })
        });

        const result = await response.json();

        // Handle response from the server
        if (result.success) {
            alert(result.message);

            // Empty the cart in memory
            for (const key in cart) {
                delete cart[key];
            }

            // Reload the page to refresh product list and stock counts from the database
            location.reload();
        } else {
            // Display error returned by the server (e.g., insufficient stock)
            alert('Checkout failed: ' + result.message);
            if (payBtn) payBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error during checkout:', error);
        alert('An unexpected error occurred while communicating with the server.');
        if (payBtn) payBtn.disabled = false;
    }
}

// Ensures cart updates when page is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Adds click event listeners to all product cards
    document.querySelectorAll('.product_card').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.getAttribute('data-id');
            const name = card.getAttribute('data-name');
            const price = card.getAttribute('data-price');
            const stock = card.getAttribute('data-stock');
            addToCart(id, name, price, stock);
        });
    });

    renderCart();
});
