<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// Read and parse incoming JSON payload from the request body
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validate that cart items were provided
if (!$data || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Cart is empty or invalid data provided.'
    ]);
    exit;
}

$items = $data['items'];

// Begin a database transaction to ensure atomicity
// If any product stock deduction fails, all changes will be rolled back
$conn->begin_transaction();

try {
    // Prepared statement to check current stock and lock the row to avoid race conditions
    $checkStmt = $conn->prepare("SELECT product_name, product_stock FROM products WHERE product_id = ? FOR UPDATE");

    // Prepared statement to decrement the purchased quantity from product_stock
    $updateStmt = $conn->prepare("UPDATE products SET product_stock = product_stock - ? WHERE product_id = ?");

    if (!$checkStmt || !$updateStmt) {
        throw new Exception("Failed to prepare database statements: " . $conn->error);
    }

    $itemsProcessed = 0;

    foreach ($items as $item) {
        $productId = isset($item['id']) ? trim((string) $item['id']) : '';
        $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

        // Skip invalid items or quantities
        if ($productId === '' || $qty <= 0) {
            continue;
        }

        // Query product information from database (product_id is a VARCHAR code like 'DR001')
        $checkStmt->bind_param("s", $productId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Product with ID '{$productId}' was not found.");
        }

        $product = $result->fetch_assoc();
        $currentStock = (int) $product['product_stock'];
        $productName = $product['product_name'];

        // Verify that enough stock is available for the checkout quantity
        if ($currentStock < $qty) {
            throw new Exception("Insufficient stock for '{$productName}'. Available: {$currentStock}, requested: {$qty}.");
        }

        // Deduct the checked out quantity from product stock (qty is INT, productId is VARCHAR)
        $updateStmt->bind_param("is", $qty, $productId);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update stock for '{$productName}': " . $updateStmt->error);
        }

        $itemsProcessed++;
    }

    if ($itemsProcessed === 0) {
        throw new Exception("No valid items to checkout.");
    }

    // Commit the transaction after all products have been successfully deducted
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Checkout successful! Products have been subtracted from inventory.'
    ]);
} catch (Exception $e) {
    // Roll back all changes if an error or stock deficit occurs
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean up statement and connection resources
    if (isset($checkStmt) && $checkStmt)
        $checkStmt->close();
    if (isset($updateStmt) && $updateStmt)
        $updateStmt->close();
    $conn->close();
}
