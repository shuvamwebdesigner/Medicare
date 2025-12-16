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

if ($orderId > 0) {
    // Fetch order data
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        // Fetch user data (including address and phone for shipping)
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $order['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Fetch order items with product details
        $stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.description as product_description, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $orderId; ?></title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/view_order.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-eye me-3"></i>Order Details</h1>
            <p>Complete information for Order #<?php echo $orderId; ?></p>
        </div>

        <?php if ($order): ?>
            <div class="row">
                <!-- Order Summary -->
                <div class="col-lg-8 mb-4">
                    <div class="card order-summary-card animate__animated animate__zoomIn animate__delay-1s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Order ID:</strong> #<?php echo $order['id']; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order Date:</strong> <?php echo date('M d, Y H:i:s', strtotime($order['created_at'])); ?>
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
                                    <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Payment Intent ID:</strong> <small><?php echo htmlspecialchars($order['payment_intent_id'] ?? 'N/A'); ?></small>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Amount:</strong> <span class="total-amount">$<?php echo number_format($order['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card order-items-card animate__animated animate__zoomIn animate__delay-2s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Order Items</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($orderItems as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="images/products/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-fluid rounded">
                                    </div>
                                    <div class="item-details">
                                        <h6 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                        <p class="item-description"><?php echo htmlspecialchars(substr($item['product_description'], 0, 100)) . (strlen($item['product_description']) > 100 ? '...' : ''); ?></p>
                                        <div class="item-meta">
                                            <span class="quantity">Quantity: <?php echo $item['quantity']; ?></span>
                                            <span class="price">$<?php echo number_format($item['price'], 2); ?> each</span>
                                            <span class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?> total</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Customer & Shipping Info -->
                <div class="col-lg-4 mb-4">
                    <!-- Customer Information -->
                    <div class="card customer-card animate__animated animate__zoomIn animate__delay-3s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($user): ?>
                                <div class="customer-avatar text-center mb-3">
                                    <i class="fas fa-user-circle fa-3x text-primary"></i>
                                </div>
                                <h6><?php echo htmlspecialchars($user['name']); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                                <?php if (isset($user['phone']) && $user['phone']): ?>
                                    <p class="mb-0"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['phone']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">Customer information not available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="card shipping-card animate__animated animate__zoomIn animate__delay-4s">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($user && isset($user['address']) && $user['address']): ?>
                                <address>
                                    <?php echo htmlspecialchars($user['name']); ?><br>
                                    <?php echo nl2br(htmlspecialchars($user['address'])); ?>
                                </address>
                                <?php if (isset($user['phone']) && $user['phone']): ?>
                                    <p class="mt-2 mb-0"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['phone']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">Shipping address not available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Actions -->
            <div class="card actions-card animate__animated animate__zoomIn animate__delay-5s">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Actions</h5>
                </div>
                <div class="card-body inner">
                    <a href="manage_orders.php" class="btn btn-secondary w-100 mb-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                    <a href="update_order.php?id=<?php echo $orderId; ?>" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-edit me-2"></i>Update Order
                    </a>
                    <button onclick="window.print()" class="btn btn-info w-100 mb-2">
                        <i class="fas fa-print me-2"></i>Print Order
                    </button>
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
