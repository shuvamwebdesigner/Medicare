<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Handle status update via GET (for quick actions)
if (isset($_GET['update_status']) && isset($_GET['id']) && isset($_GET['status'])) {
    $orderId = (int)$_GET['id'];
    $status = $_GET['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    if ($stmt->execute()) {
        $success = "Order status updated successfully.";
    } else {
        $error = "Error updating order: " . $conn->error;
    }
    $stmt->close();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id";
$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
    $types .= 'sss';
}

if ($statusFilter) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($where) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

// Get orders
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id";
if ($where) {
    $countQuery .= " WHERE " . implode(' AND ', $where);
}
$stmt = $conn->prepare($countQuery);
if ($params && count($params) > 2) { // Remove LIMIT and OFFSET params
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    $stmt->bind_param($countTypes, ...$countParams);
}
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalOrders / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/manage_orders.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-shopping-cart me-3"></i>Manage Orders</h1>
            <p>View and manage all customer orders</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate__animated animate__fadeIn"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="card filter-card animate__animated animate__fadeIn animate__delay-1s">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, email, or order ID" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="manage_orders.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card orders-card animate__animated animate__zoomIn animate__delay-2s">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Orders (<?php echo $totalOrders; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($order['user_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="update_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="Update">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?update_status=1&id=<?php echo $order['id']; ?>&status=processing">Mark Processing</a></li>
                                                        <li><a class="dropdown-item" href="?update_status=1&id=<?php echo $order['id']; ?>&status=shipped">Mark Shipped</a></li>
                                                        <li><a class="dropdown-item" href="?update_status=1&id=<?php echo $order['id']; ?>&status=delivered">Mark Delivered</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="?update_status=1&id=<?php echo $order['id']; ?>&status=cancelled">Cancel Order</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Orders pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                        <h4>No orders found</h4>
                        <p class="text-muted">There are no orders matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>