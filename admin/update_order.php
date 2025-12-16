<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$orderItems = [];
$user = null;
$error = '';
$success = '';

// Fetch order data
if ($orderId > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        // Fetch user data
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $order['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Fetch order items
        $stmt = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
        $stmt->close();
    } else {
        $error = "Order not found.";
    }
} else {
    $error = "Invalid order ID.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order) {
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    
    if ($stmt->execute()) {
        $success = "Order status updated successfully.";
        $order['status'] = $status; // Update local variable
    } else {
        $error = "Error updating order: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/update_order.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-edit me-3"></i>Update Order</h1>
            <p>Manage order status and view details</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card order-card animate__animated animate__zoomIn animate__delay-1s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Order Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Order ID:</strong> #<?php echo $order['id']; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <?php if ($user): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Customer:</strong> <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Total:</strong> $<?php echo number_format($order['total'], 2); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Payment Intent:</strong> <?php echo htmlspecialchars($order['payment_intent_id'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            
                            <h6 class="mt-4 mb-3">Order Items</h6>
                            <div class="order-items">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="order-item">
                                        <div class="item-info">
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                                        </div>
                                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card update-card animate__animated animate__zoomIn animate__delay-2s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Update Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Order Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card info-card animate__animated animate__zoomIn animate__delay-3s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <a href="manage_orders.php" class="btn btn-secondary w-100 mb-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Orders
                            </a>
                            <a href="view_order.php?id=<?php echo $orderId; ?>" class="btn btn-info w-100">
                                <i class="fas fa-eye me-2"></i>View Full Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle fa-5x text-muted mb-3 animate__animated animate__bounceIn"></i>
                <h3>Order not found</h3>
                <p class="text-muted">The requested order could not be found.</p>
                <a href="manage_orders.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>