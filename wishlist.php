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

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle remove from wishlist
if (isset($_POST['remove_from_wishlist']) && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    
    // Check if wishlist table exists, if not create it
    $conn->query("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    
    if ($stmt->execute()) {
        $message = "Product removed from wishlist successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to remove product from wishlist.";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle add to cart
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add to cart or increase quantity
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }
    
    $message = "Product added to cart successfully!";
    $messageType = "success";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get wishlist items with product details (removed stock_quantity reference)
$query = "SELECT w.*, p.name, p.price, p.image, p.description 
          FROM wishlist w 
          JOIN products p ON w.product_id = p.id 
          WHERE w.user_id = ? 
          ORDER BY w.created_at DESC 
          LIMIT ? OFFSET ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $userId, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$wishlistItems = [];
while ($row = $result->fetch_assoc()) {
    $wishlistItems[] = $row;
}
$stmt->close();

// Get total wishlist count for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalItems = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalItems / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/wishlist.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container wishlist-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-heart me-3"></i>My Wishlist</h1>
            <p>Your saved products for later</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> animate__animated animate__fadeIn">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($wishlistItems)): ?>
            <div class="row">
                <?php foreach ($wishlistItems as $index => $item): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="product-card animate__animated animate__zoomIn animate__delay-<?php echo ($index % 3 + 1); ?>s">
                            <div class="product-image">
                                <img src="admin/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="img-fluid">
                                <div class="product-overlay">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm me-2">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="remove_from_wishlist" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <h6 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="product-description"><?php echo htmlspecialchars(substr($item['description'], 0, 60)) . (strlen($item['description']) > 60 ? '...' : ''); ?></p>
                                <div class="product-price">
                                    <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                                </div>
                                <div class="product-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Added <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Wishlist pagination" class="mt-4">
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
            <div class="text-center no-wishlist animate__animated animate__fadeIn">
                <i class="fas fa-heart fa-5x text-muted mb-3"></i>
                <h3>Your wishlist is empty</h3>
                <p class="text-muted">Start adding products to your wishlist to keep track of items you're interested in!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
