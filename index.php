<?php
require_once 'db.php';

// Fetch all categories for the filter dropdown
$categories = [];
$catSql = "SELECT category_id, category_code, category_name FROM categories ORDER BY category_id ASC";
$catResult = $conn->query($catSql);
if ($catResult && $catResult->num_rows > 0) {
    while ($catRow = $catResult->fetch_assoc()) {
        $categories[] = $catRow;
    }
}

// Fetch products with their category information, sorted by category and product ID
$sql = "SELECT p.product_id, p.product_name, p.product_price, p.product_stock, p.category_id, c.category_name 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        ORDER BY c.category_id ASC, p.product_id ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWN Cashier</title>
    <!-- Retro Google Fonts: Space Grotesk (Neo-Retro Grotesque) & Space Mono (Mechanical Monospace / Dot-matrix) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>

<body>
    <div class="top_bar">
        <div class="brand_container">
            <span class="title">OWN Cashier</span>
        </div>
        <div class="clock_display">
            <span class="date_time" id="date_time"></span>
        </div>
    </div>
    <div class="main_container">
        <div class="products_container">
            <div class="products_header">
                <div class="products_title_group">
                    <span class="products_title">Products</span>
                    <div class="category_filter_buttons" id="category_filter_buttons">
                        <button type="button" class="category_btn active" data-category="all"
                            onclick="filterByCategory('all', this)">All</button>
                        <?php foreach ($categories as $cat): ?>
                            <button type="button" class="category_btn"
                                data-category="<?= htmlspecialchars($cat['category_id']); ?>"
                                onclick="filterByCategory('<?= htmlspecialchars($cat['category_id']); ?>', this)">
                                <?= htmlspecialchars($cat['category_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="product_count_badge" id="product_count_badge"></span>
            </div>
            <div class="product_cards_container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="product_card" data-id="<?= htmlspecialchars($row['product_id']); ?>"
                            data-name="<?= htmlspecialchars($row['product_name']); ?>"
                            data-price="<?= htmlspecialchars($row['product_price']); ?>"
                            data-stock="<?= htmlspecialchars($row['product_stock']); ?>"
                            data-category-id="<?= htmlspecialchars($row['category_id']); ?>"
                            data-category-name="<?= htmlspecialchars($row['category_name']); ?>">
                            <div class="product_info">
                                <span class="product_category_tag"><?= htmlspecialchars($row['category_name']); ?></span>
                                <span class="product_name"><?= htmlspecialchars($row['product_name']); ?></span>
                                <span class="product_price">Rp<?= number_format($row['product_price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="product_stock">
                                <span class="stock_title">STOCK</span>
                                <span class="stock_count"><?= htmlspecialchars($row['product_stock']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no_products_msg">No products found.</p>
                <?php endif; ?>
                <p class="no_products_msg" id="no_products_filter_msg" style="display: none;">No products found in this
                    category.</p>
            </div>
        </div>
        <div class="checkout_container">
            <div class="checkout_header">
                <span class="checkout_title">Till // Receipt</span>
                <button type="button" class="clear_cart_btn" id="clear_cart_btn" onclick="clearCart()"
                    title="Remove all items from cart" disabled>Reset</button>
            </div>
            <div class="cart_container" id="cart_container">
                <span class="empty_cart_msg">-- TILL IS EMPTY --</span>
            </div>
            <div class="checkout_summary">
                <div class="checkout_total_row">
                    <span class="total_label">TOTAL DUE</span>
                    <span class="checkout_total_amount" id="checkout_total">Rp0</span>
                </div>
                <div class="checkout_summary_row">
                    <button type="button" class="pay_btn" onclick="processPayment()">Charge // Pay</button>
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