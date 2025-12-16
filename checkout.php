<?php
include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user details
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// Fetch cart items
$cartStmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.image, p.mrp 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartItems = $cartStmt->get_result();
$cartStmt->close();

// Calculate totals
$subtotal = 0;
$itemCount = 0;
while ($item = $cartItems->fetch_assoc()) {
    $subtotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
}
$cartItems->data_seek(0); // Reset pointer

$shipping = 50; // Fixed shipping cost
$tax = $subtotal * 0.18; // 18% GST
$total = $subtotal + $shipping + $tax;

// Handle form submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process order
    $billingName = trim($_POST['billing_name']);
    $billingEmail = trim($_POST['billing_email']);
    $billingPhone = trim($_POST['billing_phone']);
    $billingAddress = trim($_POST['billing_address']);
    $billingCity = trim($_POST['billing_city']);
    $billingState = trim($_POST['billing_state']);
    $billingPincode = trim($_POST['billing_pincode']);
    
    $shippingName = trim($_POST['shipping_name']);
    $shippingPhone = trim($_POST['shipping_phone']);
    $shippingAddress = trim($_POST['shipping_address']);
    $shippingCity = trim($_POST['shipping_city']);
    $shippingState = trim($_POST['shipping_state']);
    $shippingPincode = trim($_POST['shipping_pincode']);
    
    $paymentMethod = $_POST['payment_method'];
    
    // Validation
    if (empty($billingName) || empty($billingEmail) || empty($billingPhone) || empty($billingAddress)) {
        $message = "Please fill in all required billing fields.";
        $messageType = "danger";
    } elseif (empty($shippingName) || empty($shippingPhone) || empty($shippingAddress)) {
        $message = "Please fill in all required shipping fields.";
        $messageType = "danger";
    } elseif (!filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "danger";
    } elseif (strlen($billingPhone) < 10) {
        $message = "Please enter a valid phone number.";
        $messageType = "danger";
    } else {
        // Create order
        $conn->begin_transaction();
        
        try {
            // Insert order
            $orderStmt = $conn->prepare("
                INSERT INTO orders (user_id, total, shipping_cost, tax_amount, status, 
                                  billing_name, billing_email, billing_phone, billing_address, 
                                  billing_city, billing_state, billing_pincode,
                                  shipping_name, shipping_phone, shipping_address,
                                  shipping_city, shipping_state, shipping_pincode, payment_method)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $orderStmt->bind_param("idddssssssssssssss", 
                $userId, $total, $shipping, $tax,
                $billingName, $billingEmail, $billingPhone, $billingAddress,
                $billingCity, $billingState, $billingPincode,
                $shippingName, $shippingPhone, $shippingAddress,
                $shippingCity, $shippingState, $shippingPincode, $paymentMethod
            );
            $orderStmt->execute();
            $orderId = $conn->insert_id;
            $orderStmt->close();
            
            // Insert order items
            $cartItems->data_seek(0);
            while ($item = $cartItems->fetch_assoc()) {
                $itemStmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $itemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $itemStmt->execute();
                $itemStmt->close();
            }
            
            // Clear cart
            $clearCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCartStmt->bind_param("i", $userId);
            $clearCartStmt->execute();
            $clearCartStmt->close();
            
            $conn->commit();
            
            // Redirect to order confirmation
            header("Location: order_success.php?order_id=" . $orderId);
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Order processing failed. Please try again.";
            $messageType = "danger";
        }
    }
}

$shipping = $subtotal > 500 ? 0 : 50; // Free shipping over ₹500
$tax = $subtotal * 0.18; // 18% GST
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Medicare Pharma</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/checkout.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container checkout-container mt-4">
        <div class="checkout-header animate__animated animate__fadeInDown">
            <h1><i class="fas fa-shopping-cart me-3"></i>Checkout</h1>
            <p>Complete your order securely</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="checkout-form">
            <div class="row">
                <!-- Left Column: Billing & Shipping -->
                <div class="col-lg-8">
                    <!-- Billing Information -->
                    <div class="checkout-section animate__animated animate__fadeInLeft">
                        <h3><i class="fas fa-user me-2"></i>Billing Information</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="billing_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="billing_name" name="billing_name" 
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="billing_email" name="billing_email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="billing_phone" name="billing_phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="billing_city" name="billing_city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="billing_state" name="billing_state">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_pincode" class="form-label">Pincode</label>
                                <input type="text" class="form-control" id="billing_pincode" name="billing_pincode">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="billing_address" class="form-label">Address *</label>
                                <textarea class="form-control" id="billing_address" name="billing_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="checkout-section animate__animated animate__fadeInLeft animate__delay-1s">
                        <h3><i class="fas fa-truck me-2"></i>Shipping Information</h3>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sameAsBilling" checked>
                            <label class="form-check-label" for="sameAsBilling">
                                Same as billing address
                            </label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="shipping_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="shipping_name" name="shipping_name" 
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="shipping_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="shipping_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="shipping_city" name="shipping_city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="shipping_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="shipping_state" name="shipping_state">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="shipping_pincode" class="form-label">Pincode</label>
                                <input type="text" class="form-control" id="shipping_pincode" name="shipping_pincode">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="shipping_address" class="form-label">Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section animate__animated animate__fadeInLeft animate__delay-2s">
                        <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                        <div class="payment-options">
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                <label class="form-check-label" for="cod">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    <div>
                                        <strong>Cash on Delivery</strong>
                                        <small class="text-muted d-block">Pay when you receive your order</small>
                                    </div>
                                </label>
                            </div>
                            <div class="form-check payment-option">
                                <input class="form-check-input" type="radio" name="payment_method" id="online" value="online" disabled>
                                <label class="form-check-label" for="online">
                                    <i class="fas fa-credit-card me-2"></i>
                                    <div>
                                        <strong>Online Payment</strong>
                                        <small class="text-muted d-block">Coming Soon</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary animate__animated animate__fadeInRight">
                        <h3><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                        
                        <div class="order-items">
                            <?php 
                            $cartItems->data_seek(0);
                            while ($item = $cartItems->fetch_assoc()): 
                            ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="admin/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="item-details">
                                        <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="quantity">Qty: <?php echo $item['quantity']; ?></p>
                                        <p class="price">&#8377;<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal (<?php echo $itemCount; ?> items)</span>
                                <span>&#8377;<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping</span>
                                <span class="<?php echo $shipping === 0 ? 'free-shipping' : ''; ?>">
                                    <?php if ($shipping === 0): ?>
                                        <strong>FREE</strong>
                                    <?php else: ?>
                                        &#8377;<?php echo number_format($shipping, 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($shipping === 0): ?>
                                <div class="free-shipping-note">
                                    <i class="fas fa-truck me-1"></i> Free shipping on orders over &#8377;500
                                </div>
                            <?php endif; ?>
                            <div class="total-row">
                                <span>Tax (GST 18%)</span>
                                <span>&#8377;<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="total-row total">
                                <strong>Total</strong>
                                <strong>&#8377;<?php echo number_format($total, 2); ?></strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 place-order-btn">
                            <i class="fas fa-check me-2"></i>Place Order
                        </button>

                        <div class="order-notice">
                            <i class="fas fa-shield-alt me-2"></i>
                            <small>Secure checkout with SSL encryption</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Same as billing checkbox functionality
        document.getElementById('sameAsBilling').addEventListener('change', function() {
            const shippingFields = ['shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_pincode'];
            
            if (this.checked) {
                shippingFields.forEach(field => {
                    document.getElementById(field).value = document.getElementById('billing_' + field.split('_')[1]).value;
                });
            } else {
                shippingFields.forEach(field => {
                    document.getElementById(field).value = '';
                });
            }
        });

        // Auto-fill shipping when billing changes (if same as billing is checked)
        document.querySelectorAll('[id^="billing_"]').forEach(field => {
            field.addEventListener('input', function() {
                if (document.getElementById('sameAsBilling').checked) {
                    const shippingField = document.getElementById('shipping_' + this.id.split('_')[1]);
                    if (shippingField) {
                        shippingField.value = this.value;
                    }
                }
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>