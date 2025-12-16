<?php
include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    // Check if product exists and has stock
    $productStmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $product = $productStmt->get_result()->fetch_assoc();
    $productStmt->close();
    
    if (!$product) {
        header("Location: cart.php?error=Product not found");
        exit;
    }
    
    // Check stock availability
    if ($product['stock'] !== null && $quantity > $product['stock']) {
        header("Location: cart.php?error=Insufficient stock. Only " . $product['stock'] . " items available");
        exit;
    }
    
    // Add to cart (INSERT ... ON DUPLICATE KEY UPDATE to handle existing items)
    $cartStmt = $conn->prepare("
        INSERT INTO cart (user_id, product_id, quantity) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    $cartStmt->bind_param("iii", $userId, $productId, $quantity);
    
    if ($cartStmt->execute()) {
        header("Location: cart.php?success=Product added to cart successfully");
    } else {
        header("Location: cart.php?error=Failed to add product to cart");
    }
    
    $cartStmt->close();
} else {
    header("Location: index.php");
}
exit;
?>