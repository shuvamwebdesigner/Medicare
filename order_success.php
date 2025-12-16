<?php
include 'config/db.php';
include 'includes/auth.php';

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Fetch order details (optional, for display)
$stmt = $conn->prepare("SELECT total, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    echo "Order not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Success - Medicare Pharma</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Order Placed Successfully!</h1>
        <p>Order ID: <?php echo $order_id; ?></p>
        <p>Total: $<?php echo $order['total']; ?></p>
        <p>Status: <?php echo $order['status']; ?></p>
        <p>You will pay cash upon delivery.</p>
        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
</body>
</html>