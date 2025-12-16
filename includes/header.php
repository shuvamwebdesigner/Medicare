<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/db.php';

// Include auth functions if not already included
if (!function_exists('isLoggedIn')) {
    include 'includes/auth.php';
}

// Initialize user variable
$user = null;

// Get cart item count for logged-in users
$cartCount = 0;
if (isLoggedIn() && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $cartStmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
    $cartStmt->bind_param("i", $userId);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result()->fetch_assoc();
    $cartCount = $cartResult['total_items'] ?? 0;
    $cartStmt->close();
    
    // Fetch user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<nav class="navbar navbar-expand-lg navbar-light py-2 sticky-top">
    <div class="container d-flex align-items-center">
    
        <!-- Logo + Brand -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="admin/images/medicare logo.png" alt="Medicine Store Logo" height="60" style="margin-right: 5px;">
            <span class="fw-bold fs-4 text-primary">Medicare Pharma</span>
        </a>

        <!-- Search Form -->
        <form class="d-flex flex-grow-1 mx-3 position-relative" action="products.php" method="get" style="max-width: 700px;">
            <input class="form-control rounded-pill ps-4 search-input" type="search" placeholder="Search for medicines, vitamins, health products" aria-label="Search" name="q">
            <button class="btn btn-primary position-absolute top-50 end-0 translate-middle-y rounded-pill me-1 px-4 search-btn" type="submit">
                <i class="bi bi-search"></i> Search
            </button>
        </form>

        <!-- Right-side links -->
        <div class="d-flex align-items-center gap-3 fw-bold">
            <?php if (isLoggedIn() && $user): ?>
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn dropdown-toggle profile-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </button>
                    <ul class="dropdown-menu profile-dropdown" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header"><?php echo htmlspecialchars($user['name']); ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                        <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Wishlist</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
                
                <?php if (isAdmin()): ?>
                    <a class="nav-link admin-link" href="admin/index.php">
                        <i class="fas fa-cog me-1"></i>Admin
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a class="nav-link login-link" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
                <a class="nav-link register-link" href="register.php">
                    <i class="fas fa-user-plus me-1"></i>Register
                </a>
            <?php endif; ?>
            
            <a class="nav-link cart-link d-flex align-items-center position-relative fw-bold" href="cart.php">
                <i class="pe-2 bi bi-cart fs-5"></i>
                <?php 
                  if ($cartCount > 0) {
                      echo "<span class='position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge'>$cartCount</span>";
                  }
                ?>Cart
            </a>
        </div>

    </div>
</nav>

<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="assets/header.css">