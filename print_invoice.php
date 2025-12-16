<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

if ($orderId <= 0) {
    die("Invalid order ID");
}

// Verify order belongs to user
$stmt = $conn->prepare("SELECT o.* FROM orders o WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found or access denied");
}

$order = $result->fetch_assoc();
$stmt->close();

// Get user info
$stmt = $conn->prepare("SELECT name, email, address, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.image as product_image 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderItems = [];
while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}
$stmt->close();

// Function to calculate order totals with current business logic
function getOrderCalculations($order, $orderItems) {
    // Calculate actual subtotal from order items
    $actualSubtotal = 0;
    foreach ($orderItems as $item) {
        $actualSubtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
    }
    
    // Apply current shipping logic: free shipping over ₹500
    $isFreeShipping = $actualSubtotal > 500;
    $shippingCost = $isFreeShipping ? 0 : 50; // Default shipping ₹50
    
    // Apply current tax logic: 18% GST
    $taxAmount = $actualSubtotal * 0.18;
    
    // Calculate correct total
    $calculatedTotal = $actualSubtotal + $shippingCost + $taxAmount;
    
    return [
        'subtotal' => $actualSubtotal,
        'shipping' => $shippingCost,
        'tax' => $taxAmount,
        'total' => $calculatedTotal,
        'isFreeShipping' => $isFreeShipping
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/print_invoice.css">
</head>
<body>
    <div class="no-print print-btn">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print Invoice
        </button>
    </div>

    <div class="invoice-container">
        <div class="invoice-title">
            <h2><img src="admin/images/medicare logo.png" alt="Medicine Store Logo" class="invoice-logo">INVOICE</h2>
        </div>
        <div class="print-stamp">
            <img src="admin/images/medicare logo.png" alt="Medicare Pharma Watermark" class="stamp-logo">
        </div>
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h2>Medicare Pharma</h2>
                <p>6th floor, Urmi Corporate Park Street,</p>
                <p>Kolkata-700054</p>
                <p><strong>GSTIN: </strong>19AAAMP2025S1Z4</p>
                <p><strong>Email: </strong>support@medicare.com</p>
                <p><strong>PAN NO: </strong>AAAMP2025S</p>
            </div>
            <div class="invoice-details">
                <h3>Invoice NO: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('H:i:s', strtotime($order['created_at'])); ?></p>
                <p><strong>Status: </strong>
                    <span class="status-badge status-<?php echo strtolower($order['status'] ?? 'pending'); ?>">
                        <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                    </span>
                </P>
            </div>
        </div>

        <!-- Billing Information -->
        <div class="billing-info">
            <h5><i class="fas fa-user me-1"></i>Bill To:</h5>
            <div class="customer-details">
                <strong><?php echo htmlspecialchars($userInfo['name'] ?? 'N/A'); ?></strong><br>
                <?php echo htmlspecialchars($userInfo['email'] ?? 'N/A'); ?><br>
                <?php if (!empty($userInfo['phone'])): ?>
                    <?php echo htmlspecialchars($userInfo['phone']); ?><br>
                <?php endif; ?>
                <?php if (!empty($userInfo['address'])): ?>
                    <?php echo nl2br(htmlspecialchars($userInfo['address'])); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Product</th>
                    <th style="width: 80px;">Qty</th>
                    <th style="width: 100px;">Unit Price</th>
                    <th style="width: 100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <img src="admin/images/products/<?php echo htmlspecialchars($item['product_image'] ?? ''); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>" 
                                 class="item-image">
                        </td>
                        <td class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></td>
                        <td><?php echo $item['quantity'] ?? 1; ?></td>
                        <td>&#8377;<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                        <td>&#8377;<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Invoice Summary -->
        <div class="invoice-summary">
            <?php
            $calc = getOrderCalculations($order, $orderItems);
            ?>
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

        <!-- Invoice Footer -->
        <div class="signature-section">
            <div class="signature-line">
                <span>Authorized Signature:</span>
                <img src="admin/images/signature.png" alt="Medicare Pharma Watermark" class="signature">
            </div>
            <div class="seal-placeholder">
                <span>Seal:</span>
                <div class="seal-box">
                    <img src="admin/images/medicare_stamp.png" alt="Medicare Pharma Watermark" class="seal-stamp">
                </div>
            </div>
        </div>
        <div class="invoice-footer">
            <p>Thank you for shopping with Medicare Pharma!</p>
            <p>This is a computer-generated invoice and does not require a signature.</p>
            <small>Generated on <?php echo date('M d, Y H:i:s'); ?></small>
        </div>
    </div>
</body>
</html>