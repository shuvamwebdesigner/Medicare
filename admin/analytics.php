<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Fetch analytics data
// Total Revenue
$result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered'");
$revenue = $result ? $result->fetch_assoc()['revenue'] ?? 0 : 0;

// Total Orders
$result = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$totalOrders = $result ? $result->fetch_assoc()['total_orders'] : 0;

// Pending Orders
$result = $conn->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$pendingOrders = $result ? $result->fetch_assoc()['pending_orders'] : 0;

// Total Users
$result = $conn->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $result ? $result->fetch_assoc()['total_users'] : 0;

// Monthly Revenue (last 6 months)
$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered' AND DATE_FORMAT(created_at, '%Y-%m') = '$date'");
    $monthlyRevenue[] = $result ? $result->fetch_assoc()['revenue'] ?? 0 : 0;
}

// Top Products
$result = $conn->query("SELECT p.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");
$topProducts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/analytics.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-chart-line me-3"></i>Analytics Dashboard</h1>
            <p>Insights into your store's performance</p>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="metric-card animate__animated animate__fadeInLeft animate__delay-1s">
                    <div class="metric-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="metric-value">$<?php echo number_format($revenue, 2); ?></div>
                    <div class="metric-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="metric-card animate__animated animate__fadeInLeft animate__delay-2s">
                    <div class="metric-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="metric-value"><?php echo $totalOrders; ?></div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="metric-card animate__animated animate__fadeInRight animate__delay-3s">
                    <div class="metric-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="metric-value"><?php echo $pendingOrders; ?></div>
                    <div class="metric-label">Pending Orders</div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="metric-card animate__animated animate__fadeInRight animate__delay-4s">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-value"><?php echo $totalUsers; ?></div>
                    <div class="metric-label">Total Users</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card chart-card animate__animated animate__zoomIn animate__delay-5s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card chart-card animate__animated animate__zoomIn animate__delay-6s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Products</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topProducts)): ?>
                            <?php foreach ($topProducts as $product): ?>
                                <div class="product-item">
                                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="product-sales"><?php echo $product['total_sold']; ?> sold</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    '<?php echo date('M Y', strtotime('-5 months')); ?>',
                    '<?php echo date('M Y', strtotime('-4 months')); ?>',
                    '<?php echo date('M Y', strtotime('-3 months')); ?>',
                    '<?php echo date('M Y', strtotime('-2 months')); ?>',
                    '<?php echo date('M Y', strtotime('-1 month')); ?>',
                    '<?php echo date('M Y'); ?>'
                ],
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($monthlyRevenue); ?>,
                    borderColor: '#42a5f5',
                    backgroundColor: 'rgba(66, 165, 245, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>