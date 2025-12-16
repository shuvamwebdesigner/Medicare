<?php

include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Fetch user's orders with pagination
$query = "SELECT o.*, COUNT(oi.id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          WHERE o.user_id = ? 
          GROUP BY o.id 
          ORDER BY o.created_at DESC 
          LIMIT ? OFFSET ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $userId, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Get total orders count for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalOrders / $perPage);

// Function to get order items
function getOrderItems($orderId, $conn) {
    $stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.image as product_image 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

// Function to calculate order totals with current business logic
function getOrderCalculations($order) {
    // Get stored values or defaults
    $storedTotal = $order['total_amount'] ?? $order['total'] ?? 0;
    $storedShipping = $order['shipping_cost'] ?? 0;
    $storedTax = $order['tax_amount'] ?? 0;
    
    // Calculate actual subtotal from order items if possible
    $actualSubtotal = 0;
    if (isset($order['id'])) {
        global $conn;
        $itemsStmt = $conn->prepare("SELECT SUM(oi.price * oi.quantity) as items_total FROM order_items oi WHERE oi.order_id = ?");
        $itemsStmt->bind_param("i", $order['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result()->fetch_assoc();
        $actualSubtotal = $itemsResult['items_total'] ?? 0;
        $itemsStmt->close();
    }
    
    // Use actual subtotal if available, otherwise calculate from stored total
    $subtotal = $actualSubtotal > 0 ? $actualSubtotal : max(0, $storedTotal - $storedShipping - $storedTax);
    
    // Apply current shipping logic: free shipping over ₹500
    $isFreeShipping = $subtotal > 500;
    $shippingCost = $isFreeShipping ? 0 : 50; // Default shipping ₹50
    
    // Apply current tax logic: 18% GST
    $taxAmount = $subtotal * 0.18;
    
    // Calculate correct total
    $calculatedTotal = $subtotal + $shippingCost + $taxAmount;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shippingCost,
        'tax' => $taxAmount,
        'total' => $calculatedTotal,
        'isFreeShipping' => $isFreeShipping,
        'stored_total' => $storedTotal // Keep for reference
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/orders.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container orders-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-file-invoice me-3"></i>My Orders</h1>
            <p>View your order history and invoices</p>
        </div>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $index => $order): ?>
                <?php $calc = getOrderCalculations($order); ?>
                <div class="invoice-card animate__animated animate__zoomIn animate__delay-<?php echo $index + 1; ?>s">
                    <!-- Invoice Header -->
                    <div class="invoice-header">
                        <div class="invoice-brand">
                            <h3><i class="fas fa-receipt me-2"></i>INVOICE</h3>
                            <p class="mb-0"><strong>Medicare Pharma</strong></p>
                            <p>6th floor, Urmi Corporate Park Street,</p>
                            <p>Kolkata-700054</p>
                            <p><strong>GSTIN: </strong>19AAAMP2025S1Z4</p>
                            <p><strong>Email: </strong>support@medicare.com</p>
                            <p><strong>PAN NO: </strong>AAAMP2025S</p>
                        </div>
                        <div class="invoice-details">
                            <div class="invoice-number">
                                <strong>Invoice #:</strong> <?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="invoice-date">
                                <strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </div>
                            <div class="invoice-status">
                                <span class="status-badge status-<?php echo strtolower($order['status'] ?? 'pending'); ?>">
                                    <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Body -->
                    <div class="invoice-body">
                        <!-- Customer Info -->
                        <div class="customer-info">
                            <div class="billing-info">
                                <h6><i class="fas fa-user me-1"></i>Bill To:</h6>
                                <div class="customer-details">
                                    <?php
                                    // Get user info for this order
                                    $stmt = $conn->prepare("SELECT name, email, address, phone FROM users WHERE id = ?");
                                    $stmt->bind_param("i", $userId);
                                    $stmt->execute();
                                    $userInfo = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                    ?>
                                    <strong><?php echo htmlspecialchars($userInfo['name'] ?? 'N/A'); ?></strong><br>
                                    <span><?php echo htmlspecialchars($userInfo['email'] ?? 'N/A'); ?></span><br>
                                    <?php if (!empty($userInfo['phone'])): ?>
                                        <span><?php echo htmlspecialchars($userInfo['phone']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($userInfo['address'])): ?>
                                        <span><?php echo nl2br(htmlspecialchars($userInfo['address'] ?? '')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items Table -->
                        <div class="invoice-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Description</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $orderItems = getOrderItems($order['id'], $conn);
                                    foreach ($orderItems as $item): 
                                    ?>
                                        <tr>
                                            <td>
                                                <img src="admin/images/products/<?php echo htmlspecialchars($item['product_image'] ?? ''); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>" 
                                                     class="item-thumbnail">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></strong>
                                            </td>
                                            <td class="text-center"><?php echo $item['quantity'] ?? 1; ?></td>
                                            <td class="text-end">&#8377;<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                            <td class="text-end">&#8377;<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Invoice Summary -->
                        <div class="invoice-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>&#8377;<?php echo number_format($calc['subtotal'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping:</span>
                                <span class="<?php echo $calc['isFreeShipping'] ? 'free-shipping' : ''; ?>">
                                    <?php if ($calc['isFreeShipping']): ?>
                                        <strong>FREE</strong>
                                    <?php else: ?>
                                        &#8377;<?php echo number_format($calc['shipping'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($calc['isFreeShipping']): ?>
                                <div class="free-shipping-note">
                                    <i class="fas fa-truck me-1"></i> Free shipping on orders over &#8377;500
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Tax (GST 18%):</span>
                                <span>&#8377;<?php echo number_format($calc['tax'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Payment Method:</span>
                                <span><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (!empty($order['payment_intent_id'])): ?>
                                <div class="summary-row">
                                    <span>Transaction ID:</span>
                                    <small><?php echo htmlspecialchars($order['payment_intent_id']); ?></small>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row total-row">
                                <strong>Total Amount:</strong>
                                <strong>&#8377;<?php echo number_format($calc['total'], 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Footer -->
                    <div class="invoice-footer">
                        <div class="invoice-actions">
                            <button class="btn btn-outline-primary btn-sm" onclick="printInvoice(<?php echo $order['id']; ?>)">
                                <i class="fas fa-print me-1"></i>Print Invoice
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="downloadInvoice(<?php echo $order['id']; ?>)">
                                <i class="fas fa-download me-1"></i>Download PDF
                            </button>
                            <?php if (($order['status'] ?? 'pending') === 'delivered'): ?>
                                <button class="btn btn-warning btn-sm">
                                    <i class="fas fa-star me-1"></i>Write Review
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo me-1"></i>Reorder
                            </button>
                        </div>
                        <div class="invoice-note">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Thank you for shopping with Medicare Pharma!
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center no-orders animate__animated animate__fadeIn">
                <i class="fas fa-file-invoice fa-5x text-muted mb-3"></i>
                <h3>No orders yet</h3>
                <p class="text-muted">You haven't placed any orders yet. Start shopping to see your invoices here!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printInvoice(orderId) {
            // Open print-friendly version
            window.open('print_invoice.php?id=' + orderId, '_blank');
        }

        function downloadInvoice(orderId) {
            // This would typically call a PDF generation script
            alert('PDF download feature would be implemented here for Order #' + orderId);
        }
    </script>
</body>
</html>