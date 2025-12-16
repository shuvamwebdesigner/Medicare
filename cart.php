<?php
include 'config/db.php';
include 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Check for URL messages
if (isset($_GET['success'])) {
    $message = urldecode($_GET['success']);
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $messageType = 'danger';
}


// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cartId = (int)$_POST['cart_id'];
        $quantity = max(1, (int)$_POST['quantity']);
        
        $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $updateStmt->bind_param("iii", $quantity, $cartId, $userId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $message = "Cart updated successfully!";
        $messageType = "success";
    } elseif (isset($_POST['remove_item'])) {
        $cartId = (int)$_POST['cart_id'];
        
        $deleteStmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $cartId, $userId);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        $message = "Item removed from cart!";
        $messageType = "success";
    } elseif (isset($_POST['clear_cart'])) {
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearStmt->bind_param("i", $userId);
        $clearStmt->execute();
        $clearStmt->close();
        
        $message = "Cart cleared!";
        $messageType = "success";
    }
}

// Fetch cart items
$cartStmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.image, p.mrp, p.stock
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartItems = $cartStmt->get_result();
$cartStmt->close();

// Calculate totals
$subtotal = 0;
$itemCount = 0;
$hasOutOfStock = false;

$cartItemsArray = [];
while ($item = $cartItems->fetch_assoc()) {
    $cartItemsArray[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
    
    if ($item['quantity'] > $item['stock']) {
        $hasOutOfStock = true;
    }
}
$cartItems->data_seek(0); // Reset pointer

$shipping = $subtotal > 500 ? 0 : 50; // Free shipping over ₹500
$tax = $subtotal * 0.18; // 18% GST
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Medicare Pharma</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/cart.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container cart-container mt-4">
        <div class="cart-header animate__animated animate__fadeInDown">
            <h1><i class="fas fa-shopping-cart me-3"></i>Shopping Cart</h1>
            <p><?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?> in your cart</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItemsArray)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart animate__animated animate__fadeIn">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-store me-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-items">
                        <?php foreach ($cartItemsArray as $index => $item): ?>
                            <div class="cart-item animate__animated animate__fadeInLeft" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <div class="item-image">
                                    <img src="admin/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php if ($item['quantity'] > $item['stock']): ?>
                                        <div class="out-of-stock-badge">Out of Stock</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h5 class="item-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    
                                    <div class="item-price">
                                        <?php if (!empty($item['mrp']) && $item['mrp'] > $item['price']): ?>
                                            <span class="original-price">&#8377;<?php echo number_format($item['mrp'], 2); ?></span>
                                        <?php endif; ?>
                                        <span class="current-price">&#8377;<?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                    
                                    <div class="item-stock">
                                        <?php if ($item['stock'] > 0): ?>
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                In Stock (<?php echo $item['stock']; ?> available)
                                            </small>
                                        <?php else: ?>
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Out of Stock
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="item-actions">
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">-</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" readonly>
                                            <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">+</button>
                                        </div>
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary update-btn">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                    
                                    <div class="item-total">
                                        <strong>&#8377;<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                    </div>
                                    
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger remove-btn" 
                                                onclick="return confirm('Remove this item from cart?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-actions animate__animated animate__fadeInUp">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="clear_cart" class="btn btn-outline-danger" 
                                    onclick="return confirm('Clear entire cart?')">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </form>
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary animate__animated animate__fadeInRight">
                        <h3><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal (<?php echo $itemCount; ?> items)</span>
                                <span>&#8377;<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            
                            <div class="summary-row">
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
                            
                            <div class="summary-row">
                                <span>Tax (GST 18%)</span>
                                <span>&#8377;<?php echo number_format($tax, 2); ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="summary-row total">
                                <strong>Total</strong>
                                <strong>&#8377;<?php echo number_format($total, 2); ?></strong>
                            </div>
                        </div>
                        
                        <?php if ($hasOutOfStock): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Some items in your cart are out of stock. Please adjust quantities.
                            </div>
                        <?php endif; ?>
                        
                        <div class="checkout-actions">
                            <?php if (!$hasOutOfStock && $itemCount > 0): ?>
                                <a href="checkout.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    <i class="fas fa-times-circle me-2"></i>Checkout Unavailable
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cart-benefits">
                            <div class="benefit">
                                <i class="fas fa-shield-alt"></i>
                                <span>Secure Checkout</span>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-truck"></i>
                                <span>Fast Delivery</span>
                            </div>
                            <div class="benefit">
                                <i class="fas fa-undo"></i>
                                <span>Easy Returns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function changeQuantity(button, change) {
            const form = button.closest('.quantity-form');
            const input = form.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.getAttribute('max')) || 999;
            
            let newValue = currentValue + change;
            newValue = Math.max(1, Math.min(maxValue, newValue));
            
            input.value = newValue;
            
            // Auto-submit the form
            const updateBtn = form.querySelector('.update-btn');
            updateBtn.style.display = 'inline-block';
            updateBtn.click();
        }
        
        // Hide update buttons initially
        document.querySelectorAll('.update-btn').forEach(btn => {
            btn.style.display = 'none';
        });
    </script>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>