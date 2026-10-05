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
    const clearBtn = document.getElementById('clear_cart_btn');
    const items = Object.values(cart);

    // If cart is empty, show empty cart message and disable clear button
    if (items.length === 0) {
        cartContainer.innerHTML = '<span class="empty_cart_msg">-- TILL IS EMPTY --</span>';
        if (totalElem) totalElem.textContent = 'Rp0';
        if (clearBtn) clearBtn.disabled = true;
        return;
    }

    // Enable clear button when items exist in the cart
    if (clearBtn) clearBtn.disabled = false;

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

// Removes all items from the cart
function clearCart() {
    const itemKeys = Object.keys(cart);

    // If cart is already empty, nothing to clear
    if (itemKeys.length === 0) return;

    // Delete all item entries from cart
    for (const key of itemKeys) {
        delete cart[key];
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

// Updates the live clock and date display in the top bar
function updateClock() {
    const dateTimeElem = document.getElementById('date_time');
    if (!dateTimeElem) return;

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    const timeStr = now.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });

    dateTimeElem.textContent = `${dateStr} • ${timeStr}`;
}

// Current active category filter ('all' or specific category_id)
let currentCategory = 'all';

// Filters products displayed in the catalog by selected category ID
function filterByCategory(categoryId, clickedBtn) {
    currentCategory = String(categoryId);

    // Update active state on category filter buttons
    document.querySelectorAll('.category_btn').forEach(btn => {
        const isMatch = clickedBtn ? btn === clickedBtn : btn.getAttribute('data-category') === currentCategory;
        btn.classList.toggle('active', isMatch);
    });

    const cards = document.querySelectorAll('.product_card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cardCategoryId = card.getAttribute('data-category-id');
        // Match either 'all' or the specific category ID
        if (currentCategory === 'all' || cardCategoryId === currentCategory) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Toggle message when no products match the selected category
    const noProductsMsg = document.getElementById('no_products_filter_msg');
    if (noProductsMsg) {
        noProductsMsg.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
    }

    // Update count badge in header
    updateProductCount(visibleCount, cards.length);
}

// Updates the product count badge in the products header
function updateProductCount(visibleCount, totalCount) {
    const badge = document.getElementById('product_count_badge');
    if (!badge) return;

    if (totalCount === 0) {
        badge.textContent = '0 products';
    } else if (visibleCount === totalCount) {
        badge.textContent = `${totalCount} products`;
    } else {
        badge.textContent = `${visibleCount} of ${totalCount} products`;
    }
}

// Synchronizes the visual height of checkout_header to match products_header on desktop
function syncHeaderHeights() {
    const productsHeader = document.querySelector('.products_header');
    const checkoutHeader = document.querySelector('.checkout_header');
    if (!productsHeader || !checkoutHeader) return;

    if (window.innerWidth > 900) {
        checkoutHeader.style.minHeight = productsHeader.offsetHeight + 'px';
    } else {
        checkoutHeader.style.minHeight = '';
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

    // Initializes live clock and updates every second
    updateClock();
    setInterval(updateClock, 1000);

    // Initializes category filter and product count
    filterByCategory('all');

    // Synchronizes header heights visually
    syncHeaderHeights();
    window.addEventListener('resize', syncHeaderHeights);
    if (window.ResizeObserver) {
        const ro = new ResizeObserver(syncHeaderHeights);
        const productsHeader = document.querySelector('.products_header');
        if (productsHeader) ro.observe(productsHeader);
    }
});


