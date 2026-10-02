<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "own_cashier_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT p.product_id, p.product_name, p.product_price, p.product_stock, c.category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.product_id ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="top_bar">
        <span class="title">OWN Cashier</span>
    </div>
    <div class="main_container">
        <div class="products_container">
            <span class="products_title">Products</span>
            <div class="product_cards_container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="product_card" data-id="<?= htmlspecialchars($row['product_id']); ?>"
                            data-name="<?= htmlspecialchars($row['product_name']); ?>"
                            data-price="<?= htmlspecialchars($row['product_price']); ?>"
                            data-stock="<?= htmlspecialchars($row['product_stock']); ?>">
                            <div class="product_info">
                                <span class="product_name"><?= htmlspecialchars($row['product_name']); ?></span>
                                <span class="product_price">Rp<?= number_format($row['product_price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="product_stock">
                                <span class="stock_title">Stock</span>
                                <span class="stock_count"><?= htmlspecialchars($row['product_stock']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="checkout_container">
            <span class="checkout_title">Checkout</span>
            <div class="cart_container" id="cart_container">
                <span class="empty_cart_msg">No items in cart</span>
            </div>
            <div class="checkout_summary">
                <div class="checkout_total_row">
                    <span>Total</span>
                    <span class="checkout_total_amount" id="checkout_total">Rp0</span>
                </div>
                <div class="checkout_summary_row">
                    <button type="button" class="pay_btn" onclick="processPayment()">Pay</button>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>

</html>
<?php
$conn->close();
?>