<?php
include 'config/db.php';
include 'includes/auth.php';

// Get filters from URL
$categoryFilter = $_GET['category'] ?? null;
$subCategoryFilter = $_GET['subcategory'] ?? null;
$searchTerm = trim($_GET['q'] ?? '');

// Handle wishlist actions
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_action'])) {
    if (!isLoggedIn()) {
        $message = "Please login to manage your wishlist.";
        $messageType = "warning";
    } else {
        $productId = (int)$_POST['product_id'];
        $userId = $_SESSION['user_id'];
        
        // Create wishlist table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
        
        if ($_POST['wishlist_action'] === 'add') {
            $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Product added to wishlist!";
                $messageType = "success";
            }
        } elseif ($_POST['wishlist_action'] === 'remove') {
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $userId, $productId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Product removed from wishlist!";
                $messageType = "success";
            }
        }
        $stmt->close();
    }
}

// Function to check if product is in user's wishlist
function isInWishlist($productId, $conn) {
    if (!isLoggedIn()) return false;
    
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Fetch all distinct categories
$categories = [];
$res = $conn->query("SELECT DISTINCT category FROM products");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Fetch subcategories for selected category
$subcategories = [];
if ($categoryFilter) {
    $stmt = $conn->prepare("SELECT DISTINCT subcategory FROM products WHERE category=? AND subcategory IS NOT NULL");
    $stmt->bind_param('s', $categoryFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['subcategory']) $subcategories[] = $row['subcategory'];
    }
}

// Build product query
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$paramTypes = "";

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
    $paramTypes .= "s";
}
if ($subCategoryFilter) {
    $sql .= " AND subcategory = ?";
    $params[] = $subCategoryFilter;
    $paramTypes .= "s";
}
if ($searchTerm !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$searchTerm%";
    $paramTypes .= "s";
}
$sql .= " ORDER BY name";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - Medicare Pharma</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/products.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-4 products-page">
    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="breadcrumb-container">
        <ol class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Categories</a></li>
            <?php if($categoryFilter): ?>
                <li><a href="products.php?category=<?php echo urlencode($categoryFilter); ?>"><?php echo htmlspecialchars($categoryFilter); ?></a></li>
            <?php endif; ?>
            <?php if($subCategoryFilter): ?>
                <li class="active" aria-current="page"><?php echo htmlspecialchars($subCategoryFilter); ?></li>
            <?php endif; ?>
            <?php if ($searchTerm): ?>
                <li class="active" aria-current="page">Search: <?php echo htmlspecialchars($searchTerm); ?></li>
            <?php endif; ?>
        </ol>
    </nav>

        <!-- Filters and Products Headers in Same Line -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-3 col-md-4">
            <div class="filters-main-header">
                <h3><i class="fas fa-filter me-2"></i>Filters</h3>
                <a href="products.php" class="clear-all-btn">Clear All</a>
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <h2 class="products-main-header mb-0">
                <?php
                if ($searchTerm !== '') {
                    echo 'Search Results';
                } elseif ($subCategoryFilter) {
                    echo htmlspecialchars($subCategoryFilter) . " Products";
                } elseif ($categoryFilter) {
                    echo htmlspecialchars($categoryFilter) . " Products";
                } else {
                    echo 'All Products';
                }
                ?>
            </h2>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Filters -->
        <aside class="col-lg-3 col-md-4 sidebar-filters">
            <!-- Category Filter Box -->
            <div class="filter-box category-box">
                <div class="filter-group">
                    <div class="filter-header">
                        <span>Category</span>
                        <a href="products.php" class="clear-btn">Clear</a>
                    </div>
                    <div class="filter-options scrollable">
                        <?php foreach($categories as $cat): ?>
                            <label class="filter-label">
                                <input type="radio" name="category" value="<?php echo htmlspecialchars($cat); ?>" 
                                       <?php if($cat == $categoryFilter) echo 'checked'; ?> 
                                       onclick="filterByCategory('<?php echo htmlspecialchars($cat); ?>')">
                                <?php echo htmlspecialchars($cat); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Gap between boxes -->
            <div class="filter-gap"></div>

            <!-- Subcategory Filter Box -->
            <?php if(!empty($subcategories)): ?>
                <div class="filter-box subcategory-box">
                    <div class="filter-group">
                        <div class="filter-header">
                            <span>Sub-category</span>
                            <a href="products.php?category=<?php echo urlencode($categoryFilter); ?>" class="clear-btn">Clear</a>
                        </div>
                        <div class="filter-options scrollable">
                            <?php foreach($subcategories as $subcat): ?>
                            <label class="filter-label">
                                <input type="radio" name="subcategory" value="<?php echo htmlspecialchars($subcat); ?>"
                                       <?php if($subcat == $subCategoryFilter) echo 'checked'; ?>
                                       onclick="filterBySubcategory('<?php echo htmlspecialchars($subcat); ?>')">
                                <?php echo htmlspecialchars($subcat); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <!-- Products Grid -->
        <section class="col-lg-9 col-md-8 products-list">
            <div class="row">
                <?php if($products && $products->num_rows > 0): ?>
                    <?php while($prod = $products->fetch_assoc()): ?>
                        <?php $inWishlist = isInWishlist($prod['id'], $conn); ?>
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                            <div class="card product-card h-100">
                                <?php if (!empty($prod['discount_percent'])): ?>
                                    <div class="discount-badge">
                                        <?= intval($prod['discount_percent']); ?>% OFF
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Wishlist Button -->
                                <div class="wishlist-btn-container">
                                    <?php if (isLoggedIn()): ?>
                                        <form method="POST" class="wishlist-form">
                                            <input type="hidden" name="product_id" value="<?= $prod['id']; ?>">
                                            <input type="hidden" name="wishlist_action" value="<?= $inWishlist ? 'remove' : 'add'; ?>">
                                            <button type="submit" class="wishlist-btn <?= $inWishlist ? 'in-wishlist' : ''; ?>" 
                                                    title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="wishlist-btn" onclick="window.location.href='login.php'" title="Login to add to wishlist">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="single_product.php?id=<?= $prod['id'] ?>" class="product-link">
                                    <img src="admin/images/products/<?= htmlspecialchars($prod['image']); ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($prod['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title product-name"><?= htmlspecialchars(substr($prod['name'], 0, 30)); ?>...</h5>
                                    </div>
                                </a>
                                <div class="hr-line"></div>
                                <div class="card-body pt-0">
                                    <div class="price-cart-row">
                                        <p class="price-section mb-0">
                                            <?php if(!empty($prod['mrp'])): ?>
                                                <span class="mrp">MRP</span>
                                                <span class="original-price">&#8377;<?= number_format($prod['mrp'], 2); ?></span>
                                            <?php endif; ?><br>
                                            <span class="discounted-price">&#8377;<?= number_format($prod['price'], 2); ?></span>
                                        </p>
                                        <form method="POST" action="add_to_cart.php" class="cart-form">
                                            <input type="hidden" name="product_id" value="<?= $prod['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm cart-btn">Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center no-products">
                        <i class="fas fa-search fa-5x text-muted mb-3"></i>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-th-large me-2"></i>Browse All Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
function filterByCategory(cat) {
    window.location.href = 'products.php?category=' + encodeURIComponent(cat);
}
function filterBySubcategory(subcat) {
    const urlParams = new URLSearchParams(window.location.search);
    const cat = urlParams.get('category');
    if(cat) {
        window.location.href = 'products.php?category=' + encodeURIComponent(cat) + '&subcategory=' + encodeURIComponent(subcat);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>