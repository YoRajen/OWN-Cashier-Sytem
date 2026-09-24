<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "own_cashier_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $_POST['name'] ?? '';
$price = (float) $_POST['price'] ?? 0;
$stock = (int) $_POST['stock'] ?? 0;
$category = $_POST['category'] ?? '';

$category_map = [
    "main_course" => 1,
    "snack" => 2,
    "drink" => 3,
    "dessert" => 4
];

if (isset($category_map[$category])) {
    $category_id = $category_map[$category];
}

$sql = "INSERT INTO products 
(product_name, product_price, product_stock, category_id)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("sdii", $name, $price, $stock, $category_id);

    if ($stmt->execute()) {
        header("Location: ../index.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>