<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Fetch dynamic stats from database using mysqli
// Total Products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
if ($result) {
    $totalProducts = $result->fetch_assoc()['total'];
} else {
    $totalProducts = 'N/A';
    error_log("Database error: " . $conn->error);
}

// Pending Orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
if ($result) {
    $pendingOrders = $result->fetch_assoc()['total'];
} else {
    $pendingOrders = 'N/A';
    error_log("Database error: " . $conn->error);
}

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $totalUsers = $result->fetch_assoc()['total'];
} else {
    $totalUsers = 'N/A';
    error_log("Database error: " . $conn->error);
}

// Total Revenue (assuming 'completed' orders have a 'total' column)
$result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered'");
if ($result) {
    $revenue = $result->fetch_assoc()['revenue'] ?? 0;
    $revenue = '$' . number_format($revenue, 2);
} else {
    $revenue = 'N/A';
    error_log("Database error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/admin_styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1>Welcome to the Admin Panel</h1>
            <p>Manage your products and orders efficiently</p>
        </div>

        <div class="stats-section">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="stat-card animate__animated animate__fadeInLeft animate__delay-1s">
                        <i class="fas fa-box card-icon"></i>
                        <div class="stat-number"><?php echo $totalProducts; ?></div>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-card animate__animated animate__fadeInLeft animate__delay-2s">
                        <i class="fas fa-shopping-cart card-icon"></i>
                        <div class="stat-number"><?php echo $pendingOrders; ?></div>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-card animate__animated animate__fadeInRight animate__delay-3s">
                        <i class="fas fa-users card-icon"></i>
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-card animate__animated animate__fadeInRight animate__delay-4s">
                        <i class="fas fa-dollar-sign card-icon"></i>
                        <div class="stat-number"><?php echo $revenue; ?></div>
                        <p>Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card animate__animated animate__zoomIn animate__delay-5s">
                    <div class="card-body text-center">
                        <i class="fas fa-box card-icon"></i>
                        <h5 class="card-title">Products</h5>
                        <p class="card-text">Add, edit, or delete products in your inventory.</p>
                        <a href="manage_products.php" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Manage Products</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card animate__animated animate__zoomIn animate__delay-6s">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart card-icon"></i>
                        <h5 class="card-title">Orders</h5>
                        <p class="card-text">View and update order statuses seamlessly.</p>
                        <a href="manage_orders.php" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Manage Orders</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card animate__animated animate__zoomIn animate__delay-7s">
                    <div class="card-body text-center">
                        <i class="fas fa-users card-icon"></i>
                        <h5 class="card-title">Users</h5>
                        <p class="card-text">Manage user accounts and permissions.</p>
                        <a href="manage_users.php" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Manage Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card animate__animated animate__zoomIn animate__delay-8s">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line card-icon"></i>
                        <h5 class="card-title">Analytics</h5>
                        <p class="card-text">View reports and analytics for your store.</p>
                        <a href="analytics.php" class="btn btn-primary"><i class="fas fa-arrow-right"></i> View Analytics</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>