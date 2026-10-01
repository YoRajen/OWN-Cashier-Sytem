const cart = {};

function formatRupiah(number) {
    return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
}

function renderCart() {
    const cartContainer = document.getElementById('cart_container');
    const totalElem = document.getElementById('checkout_total');
    const items = Object.values(cart);

    if (items.length === 0) {
        cartContainer.innerHTML = '<span class="empty_cart_msg">No items in cart</span>';
        if (totalElem) totalElem.textContent = 'Rp0';
        return;
    }

    let total = 0;
    cartContainer.innerHTML = '';

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

document.addEventListener('DOMContentLoaded', () => {
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
