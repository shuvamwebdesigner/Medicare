<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
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

        // Insert product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, subcategory, manufacturer, mrp, discount_percent, stock, features, composition, key_uses, how_to_use, safety_information, additional_information, faq, certified_writer, certified_writer_title, certified_reviewer, certified_reviewer_title, manufacturer_details, disclaimer, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '')");
        $stmt->bind_param("ssssssddisssssssssssss", $name, $description, $price, $category, $subcategory, $manufacturer, $mrp, $discount_percent, $stock, $features, $composition, $key_uses, $how_to_use, $safety_information, $additional_information, $faq, $certified_writer, $certified_writer_title, $certified_reviewer, $certified_reviewer_title, $manufacturer_details, $disclaimer);
        if ($stmt->execute()) {
            $productId = $conn->insert_id;
            $message = "Product added successfully!";

            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = 'images/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $mainImage = '';
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $isMain = ($key === 0) ? 1 : 0;
                        $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($productId, '$fileName', $isMain)");
                        if ($isMain) $mainImage = $fileName;
                    }
                }
                // Update main image in products table
                if ($mainImage) $conn->query("UPDATE products SET image = '$mainImage' WHERE id = $productId");
            }
        } else {
            $message = "Error: " . $conn->error;
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
    <title>Add Product</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../assets/add_product.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container dashboard-container">
        <div class="welcome-message animate__animated animate__fadeInUp">
            <h1><i class="fas fa-plus me-3"></i>Add New Product</h1>
            <p>Fill in the details to add a new product</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card add-card animate__animated animate__zoomIn">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Product Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Name
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">
                                        <i class="fas fa-dollar-sign me-1"></i>Price
                                    </label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">
                                        <i class="fas fa-list me-1"></i>Category
                                    </label>
                                    <input type="text" class="form-control" id="category" name="category">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subcategory" class="form-label">
                                        <i class="fas fa-list-alt me-1"></i>Subcategory
                                    </label>
                                    <input type="text" class="form-control" id="subcategory" name="subcategory">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="manufacturer" class="form-label">
                                        <i class="fas fa-industry me-1"></i>Manufacturer
                                    </label>
                                    <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stock" class="form-label">
                                        <i class="fas fa-warehouse me-1"></i>Stock
                                    </label>
                                    <input type="number" class="form-control" id="stock" name="stock" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mrp" class="form-label">
                                        <i class="fas fa-money-bill me-1"></i>MRP
                                    </label>
                                    <input type="number" step="0.01" class="form-control" id="mrp" name="mrp">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_percent" class="form-label">
                                        <i class="fas fa-percent me-1"></i>Discount Percent
                                    </label>
                                    <input type="number" step="0.01" class="form-control" id="discount_percent" name="discount_percent">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="features" class="form-label">
                                    <i class="fas fa-star me-1"></i>Features (one per line)
                                </label>
                                <textarea class="form-control" id="features" name="features" rows="3"></textarea>
                            </div>
                                                        
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="composition" class="form-label">
                                    <i class="fas fa-flask me-1"></i>Composition
                                </label>
                                <textarea class="form-control" id="composition" name="composition" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="key_uses" class="form-label">
                                    <i class="fas fa-key me-1"></i>Key Uses
                                </label>
                                <textarea class="form-control" id="key_uses" name="key_uses" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="how_to_use" class="form-label">
                                    <i class="fas fa-question-circle me-1"></i>How to Use
                                </label>
                                <textarea class="form-control" id="how_to_use" name="how_to_use" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="safety_information" class="form-label">
                                    <i class="fas fa-shield-alt me-1"></i>Safety Information
                                </label>
                                <textarea class="form-control" id="safety_information" name="safety_information" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="additional_information" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Additional Information (Header:body1;body2)
                                </label>
                                <textarea class="form-control" id="additional_information" name="additional_information" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="faq" class="form-label">
                                    <i class="fas fa-question me-1"></i>FAQ (Question:Answer)
                                </label>
                                <textarea class="form-control" id="faq" name="faq" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="certified_writer" class="form-label">
                                        <i class="fas fa-user-edit me-1"></i>Certified Writer
                                    </label>
                                    <input type="text" class="form-control" id="certified_writer" name="certified_writer">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="certified_writer_title" class="form-label">
                                        <i class="fas fa-certificate me-1"></i>Writer Title
                                    </label>
                                    <input type="text" class="form-control" id="certified_writer_title" name="certified_writer_title">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="certified_reviewer" class="form-label">
                                        <i class="fas fa-user-check me-1"></i>Certified Reviewer
                                    </label>
                                    <input type="text" class="form-control" id="certified_reviewer" name="certified_reviewer">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="certified_reviewer_title" class="form-label">
                                        <i class="fas fa-award me-1"></i>Reviewer Title
                                    </label>
                                    <input type="text" class="form-control" id="certified_reviewer_title" name="certified_reviewer_title">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="manufacturer_details" class="form-label">
                                    <i class="fas fa-building me-1"></i>Manufacturer Details
                                </label>
                                <textarea class="form-control" id="manufacturer_details" name="manufacturer_details" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="disclaimer" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Disclaimer
                                </label>
                                <textarea class="form-control" id="disclaimer" name="disclaimer" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="images" class="form-label">
                                    <i class="fas fa-images me-1"></i>Product Images (multiple allowed)
                                </label>
                                <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="manage_products.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                                </a>
                                <button type="submit" name="add_product" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>