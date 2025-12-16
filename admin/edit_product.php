<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$error = '';
$success = '';

// Fetch product data
if ($productId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        $error = "Product not found.";
    }
} else {
    $error = "Invalid product ID.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'];
        $category = $_POST['category'];
        $subcategory = $_POST['subcategory'] ?? null;
        $manufacturer = $_POST['manufacturer'] ?? null;
        $mrp = $_POST['mrp'] ?? null;
        $discount_percent = $_POST['discount_percent'] ?? null;
        $stock = $_POST['stock'];
        $features = json_encode(array_filter(explode("\n", $_POST['features'] ?? '')));
        $composition = $_POST['composition'] ?? null;
        $key_uses = $_POST['key_uses'] ?? null;
        $how_to_use = $_POST['how_to_use'] ?? null;
        $safety_information = $_POST['safety_information'] ?? null;
        $additional_information = $_POST['additional_information'] ?? null;
        $faq = json_encode(array_filter(explode("\n", $_POST['faq'] ?? '')));
        $certified_writer = $_POST['certified_writer'] ?? null;
        $certified_writer_title = $_POST['certified_writer_title'] ?? null;
        $certified_reviewer = $_POST['certified_reviewer'] ?? null;
        $certified_reviewer_title = $_POST['certified_reviewer_title'] ?? null;
        $manufacturer_details = $_POST['manufacturer_details'] ?? null;
        $disclaimer = $_POST['disclaimer'] ?? null;

        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, subcategory=?, manufacturer=?, mrp=?, discount_percent=?, stock=?, features=?, composition=?, key_uses=?, how_to_use=?, safety_information=?, additional_information=?, faq=?, certified_writer=?, certified_writer_title=?, certified_reviewer=?, certified_reviewer_title=?, manufacturer_details=?, disclaimer=? WHERE id=?");
        $stmt->bind_param("ssssssddisssssssssssssi", $name, $description, $price, $category, $subcategory, $manufacturer, $mrp, $discount_percent, $stock, $features, $composition, $key_uses, $how_to_use, $safety_information, $additional_information, $faq, $certified_writer, $certified_writer_title, $certified_reviewer, $certified_reviewer_title, $manufacturer_details, $disclaimer, $id);
        if ($stmt->execute()) {
            $success = "Product updated successfully!";

            // Handle new image uploads (append to existing)
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = 'images/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($id, '$fileName', 0)");
                    }
                }
            }
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/edit_product.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-edit me-3"></i>Edit Product</h1>
            <p>Update product information</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card edit-card animate__animated animate__zoomIn">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-box me-2"></i>Product Details</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Fetch current images
                            $images = [];
                            $result = $conn->query("SELECT image_url FROM product_images WHERE product_id = $productId ORDER BY is_main DESC");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $images[] = $row['image_url'];
                                }
                            }
                            ?>
                            <?php if (!empty($images)): ?>
                                <div class="current-images mb-4">
                                    <h6>Current Images</h6>
                                    <div class="row">
                                        <?php foreach ($images as $image): ?>
                                            <div class="col-md-3 mb-3">
                                                <img src="images/products/<?php echo htmlspecialchars($image); ?>" alt="Product image" class="img-fluid rounded">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">
                                            <i class="fas fa-dollar-sign me-1"></i>Price
                                        </label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">
                                            <i class="fas fa-list me-1"></i>Category
                                        </label>
                                        <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subcategory" class="form-label">
                                            <i class="fas fa-list-alt me-1"></i>Subcategory
                                        </label>
                                        <input type="text" class="form-control" id="subcategory" name="subcategory" value="<?php echo htmlspecialchars($product['subcategory'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="manufacturer" class="form-label">
                                            <i class="fas fa-industry me-1"></i>Manufacturer
                                        </label>
                                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="<?php echo htmlspecialchars($product['manufacturer'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="stock" class="form-label">
                                            <i class="fas fa-warehouse me-1"></i>Stock
                                        </label>
                                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="mrp" class="form-label">
                                            <i class="fas fa-money-bill me-1"></i>MRP
                                        </label>
                                        <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" value="<?php echo $product['mrp'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="discount_percent" class="form-label">
                                            <i class="fas fa-percent me-1"></i>Discount Percent
                                        </label>
                                        <input type="number" step="0.01" class="form-control" id="discount_percent" name="discount_percent" value="<?php echo $product['discount_percent'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="features" class="form-label">
                                        <i class="fas fa-star me-1"></i>Features (one per line)
                                    </label>
                                    <textarea class="form-control" id="features" name="features" rows="3"><?php 
                                        $features = json_decode($product['features'] ?? '[]', true);
                                        echo htmlspecialchars(implode("\n", $features));
                                    ?></textarea>
                                </div>
                                                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="composition" class="form-label">
                                        <i class="fas fa-flask me-1"></i>Composition
                                    </label>
                                    <textarea class="form-control" id="composition" name="composition" rows="2"><?php echo htmlspecialchars($product['composition'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="key_uses" class="form-label">
                                        <i class="fas fa-key me-1"></i>Key Uses
                                    </label>
                                    <textarea class="form-control" id="key_uses" name="key_uses" rows="2"><?php echo htmlspecialchars($product['key_uses'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="how_to_use" class="form-label">
                                        <i class="fas fa-question-circle me-1"></i>How to Use
                                    </label>
                                    <textarea class="form-control" id="how_to_use" name="how_to_use" rows="2"><?php echo htmlspecialchars($product['how_to_use'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="safety_information" class="form-label">
                                        <i class="fas fa-shield-alt me-1"></i>Safety Information
                                    </label>
                                    <textarea class="form-control" id="safety_information" name="safety_information" rows="2"><?php echo htmlspecialchars($product['safety_information'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="additional_information" class="form-label">
                                        <i class="fas fa-info-circle me-1"></i>Additional Information
                                    </label>
                                    <textarea class="form-control" id="additional_information" name="additional_information" rows="2"><?php echo htmlspecialchars($product['additional_information'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="faq" class="form-label">
                                        <i class="fas fa-question me-1"></i>FAQ (one per line)
                                    </label>
                                    <textarea class="form-control" id="faq" name="faq" rows="3"><?php 
                                        $faq = json_decode($product['faq'] ?? '[]', true);
                                        echo htmlspecialchars(implode("\n", $faq));
                                    ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="certified_writer" class="form-label">
                                            <i class="fas fa-user-edit me-1"></i>Certified Writer
                                        </label>
                                        <input type="text" class="form-control" id="certified_writer" name="certified_writer" value="<?php echo htmlspecialchars($product['certified_writer'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="certified_writer_title" class="form-label">
                                            <i class="fas fa-certificate me-1"></i>Writer Title
                                        </label>
                                        <input type="text" class="form-control" id="certified_writer_title" name="certified_writer_title" value="<?php echo htmlspecialchars($product['certified_writer_title'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="certified_reviewer" class="form-label">
                                            <i class="fas fa-user-check me-1"></i>Certified Reviewer
                                        </label>
                                        <input type="text" class="form-control" id="certified_reviewer" name="certified_reviewer" value="<?php echo htmlspecialchars($product['certified_reviewer'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="certified_reviewer_title" class="form-label">
                                            <i class="fas fa-award me-1"></i>Reviewer Title
                                        </label>
                                        <input type="text" class="form-control" id="certified_reviewer_title" name="certified_reviewer_title" value="<?php echo htmlspecialchars($product['certified_reviewer_title'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="manufacturer_details" class="form-label">
                                        <i class="fas fa-building me-1"></i>Manufacturer Details
                                    </label>
                                    <textarea class="form-control" id="manufacturer_details" name="manufacturer_details" rows="2"><?php echo htmlspecialchars($product['manufacturer_details'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="disclaimer" class="form-label">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Disclaimer
                                    </label>
                                    <textarea class="form-control" id="disclaimer" name="disclaimer" rows="2"><?php echo htmlspecialchars($product['disclaimer'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="images" class="form-label">
                                        <i class="fas fa-images me-1"></i>Add More Images (multiple allowed)
                                    </label>
                                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="manage_products.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                                    </a>
                                    <button type="submit" name="edit_product" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Product
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle fa-5x text-muted mb-3 animate__animated animate__bounceIn"></i>
                <h3>Product not found</h3>
                <p class="text-muted">The requested product could not be found.</p>
                <a href="manage_products.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>