<?php

declare(strict_types=1);

use App\Models\Products;
use App\Services\DatabaseConnection;

header('Content-Type: application/json');

require_once __DIR__ . '/../autoload.php';

/**
 * Send a JSON response and stop the script.
 */
function respond(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Read and parse the incoming JSON payload
$data = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($data) || !isset($data['items']) || !is_array($data['items']) || $data['items'] === []) {
    respond(false, 'Cart is empty or invalid data provided.', 400);
}

// Normalize the cart: validate each item and merge duplicate product IDs
$cart = [];
foreach ($data['items'] as $item) {
    $productId = isset($item['id']) ? trim((string) $item['id']) : '';
    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

    if ($productId === '' || $qty <= 0) {
        continue; // skip invalid items or quantities
    }
    $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
}

if ($cart === []) {
    respond(false, 'No valid items to checkout.', 400);
}

$pdo = null;

try {
    DatabaseConnection::initialize();
    $db  = DatabaseConnection::getConnectionInstance();
    $pdo = DatabaseConnection::getConnection();

    $pdo->beginTransaction();

    foreach ($cart as $productId => $qty) {
        $productId = (string) $productId; // numeric-looking IDs become int array keys

        // Lock the row so concurrent checkouts can't oversell the same stock
        $db->query(
            'SELECT product_name, product_stock FROM products WHERE product_id = :id FOR UPDATE',
            ['id' => $productId]
        );
        $rows = $db->getFetchResults();

        if ($rows === []) {
            throw new RuntimeException("Product with ID '{$productId}' was not found.");
        }

        $productName  = $rows[0]['product_name'];
        $currentStock = (int) $rows[0]['product_stock'];

        if ($currentStock < $qty) {
            throw new RuntimeException(
                "Insufficient stock for '{$productName}'. Available: {$currentStock}, requested: {$qty}."
            );
        }

        // Deduct the purchased quantity
        $db->query(
            'UPDATE products SET product_stock = product_stock - :qty WHERE product_id = :id',
            ['qty' => $qty, 'id' => $productId]
        );
    }

    $pdo->commit();

    respond(true, 'Checkout successful! Products have been subtracted from inventory.');
} catch (RuntimeException $e) {
    // Expected business errors (unknown product, not enough stock)
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(false, $e->getMessage(), 409);
} catch (Throwable $e) {
    // Unexpected errors (database down, SQL error): log details, hide them from the client
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Checkout error: ' . $e->getMessage());
    respond(false, 'An internal error occurred during checkout. Please try again.', 500);
}