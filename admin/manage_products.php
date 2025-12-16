<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productId = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    if ($stmt->execute()) {
        $success = "Product deleted successfully.";
    } else {
        $error = "Error deleting product: " . $conn->error;
    }
}

// Fetch all products
$result = $conn->query("SELECT id, name, description, price, image, stock, created_at FROM products ORDER BY created_at DESC");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $error = "Error fetching products: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/manage_products.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-box me-3"></i>Manage Products</h1>
            <p>View, add, edit, or delete products in your inventory</p>
        </div>

        <div class="mb-4">
            <a href="add_product.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Add New Product
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success animate__animated animate__fadeIn"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card animate__animated animate__zoomIn">
                        <div class="card-img-container">
                            <img src="images/products/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                            <div class="product-details">
                                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                <span class="stock">Stock: <?php echo $product['stock']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center mt-5">
                <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
                <h3>No products found</h3>
                <p class="text-muted">There are no products in your inventory yet.</p>
                <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>