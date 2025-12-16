<?php
include 'config/db.php';
include 'includes/auth.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "Product not found.";
    exit;
}

// Fetch product images
$imageStmt = $conn->prepare("SELECT image_url, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
$imageStmt->bind_param("i", $product_id);
$imageStmt->execute();
$images = $imageStmt->get_result();
$imageStmt->close();

// Get main image (fallback to products.image if no images in table)
$mainImage = $product['image'];
$thumbnailImages = [];
while ($img = $images->fetch_assoc()) {
    if ($img['is_main']) {
        $mainImage = $img['image_url'];
    }
    $thumbnailImages[] = $img['image_url'];
}

// Parse JSON fields
$features = json_decode($product['features'] ?? '[]', true);
$faq = json_decode($product['faq'] ?? '[]', true);

// Fetch "Customers also bought" products (same category excluding current)
$relatedStmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 6");
$relatedStmt->bind_param("si", $product['category'], $product_id);
$relatedStmt->execute();
$relatedProducts = $relatedStmt->get_result();
$relatedStmt->close();

$descriptionItems = explode("\n", strip_tags($product['description']));
$keyUseItems = explode("\n", strip_tags($product['key_uses']));
$howToUseItems = explode("\n", strip_tags($product['how_to_use']));
$compositionItems = explode("\n", strip_tags($product['composition']));
$safetyInformationItems = explode("\n", strip_tags($product['safety_information']));
$addInformationItems = explode("\n", strip_tags($product['additional_information']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - Medicare Pharma</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/single_product.css">
    <link rel="stylesheet" href="assets/footer.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container single-product-container mt-4">
        <!-- Success/Error Messages -->
        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="breadcrumb-container mb-3">
            <ol class="breadcrumb">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Categories</a></li>
                <li><a href="products.php?category=<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars($product['category']); ?></a></li>
                <?php if ($product['subcategory']): ?>
                    <li class="active" aria-current="page"><?php echo htmlspecialchars($product['subcategory']); ?></li>
                <?php else: ?>
                    <li class="active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="row">
            <!-- Left: Images -->
            <div class="col-lg-5">
                <div class="sticky-image-area">
                    <div class="image-container">
                        <img id="mainImage" src="admin/images/products/<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-product-img mb-3" />
                        <div class="zoom-area" id="zoomArea"></div>
                    </div>
                    <div class="thumbnail-container">
                        <button class="thumbnail-nav prev" onclick="scrollThumbnails(-1)">&#10094;</button>
                        <div class="thumbnail-slider" id="thumbnailSlider">
                            <?php foreach ($thumbnailImages as $thumb): ?>
                                <img class="thumbnail <?php echo ($thumb === $mainImage) ? 'active' : ''; ?>" src="admin/images/products/<?php echo htmlspecialchars($thumb); ?>" alt="Thumbnail" onclick="changeImage(this.src)" />
                            <?php endforeach; ?>
                        </div>
                        <button class="thumbnail-nav next" onclick="scrollThumbnails(1)">&#10095;</button>
                    </div>
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="col-lg-7">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="manufacturer-by">By: <?php echo htmlspecialchars($product['manufacturer'] ?? 'Unknown Manufacturer'); ?></p>

                <div class="price-section d-flex">
                    <?php if (!empty($product['mrp'])): ?>
                      <p class="d-flex gap-2 mb-0">
                        <span class="mrp-title">MRP</span>
                        <span class="mrp">&#8377;<?php echo number_format($product['mrp'], 2); ?></span>
                      </p>
                    <?php endif; ?>
                    <span class="price">&#8377;<?php echo number_format($product['price'], 2); ?></span>
                    <?php if (!empty($product['discount_percent'])): ?>
                        <span class="discount">(<?php echo intval($product['discount_percent']); ?>% OFF)</span>
                    <?php endif; ?>
                    <p class="mrp-title mb-0">*MRP inclusive of all taxes</p>
                </div>

                <form method="POST" action="add_to_cart.php" class="d-flex align-items-center gap-3">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <input type="number" name="quantity" value="1" min="1" class="form-control quantity-input" style="width: 100px;" required>
                  <button type="submit" class="btn btn-success add-to-cart-btn">Add To Cart</button>
                </form>

                <div class="delivery-info mt-4">
                    <i class="bi bi-truck fs-4 text-primary me-2"></i>Delivery by Tomorrow 10 PM<br>
                    <i class="bi bi-geo-alt fs-4 text-primary me-2"></i>Mumbai, 400072
                </div>

                <div class="features mt-4">
                    <div class="feature-box d-flex">
                        <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="43" height="42" viewBox="0 0 43 42" fill="none"><circle cx="21.5" cy="21" r="21" fill="white"></circle><path fill-rule="evenodd" clip-rule="evenodd" d="M33.5638 10.8789C33.5638 9.78442 32.6795 8.89737 31.5884 8.89737C31.5884 8.89737 29.8731 8.89737 28.1934 8.89737C26.5038 8.89737 24.8517 8.39539 23.4453 7.4555L22.1488 6.58826C21.4851 6.14441 20.6212 6.14441 19.9574 6.58826L18.661 7.4555C17.2545 8.39539 15.6024 8.89737 13.9128 8.89737C12.2331 8.89737 10.5178 8.89737 10.5178 8.89737C9.42679 8.89737 8.54248 9.78442 8.54248 10.8789C8.54248 10.8789 8.54248 17.11 8.54248 21.4469C8.54248 32.3768 20.4144 36.532 20.4144 36.532C20.4157 36.532 20.4164 36.5327 20.4177 36.5333C20.8299 36.6734 21.2763 36.6734 21.6885 36.5333C21.6898 36.5327 21.6905 36.532 21.6918 36.532C21.6918 36.532 33.5638 32.3768 33.5638 21.4469V10.8789ZM32.2468 10.8789V21.4469C32.2468 31.4158 21.4587 35.215 21.2638 35.2823C21.1275 35.3292 20.9787 35.3292 20.8424 35.2823C20.6475 35.215 9.85939 31.4158 9.85939 21.4469V10.8789C9.85939 10.5143 10.1544 10.2184 10.5178 10.2184C10.5178 10.2184 12.2331 10.2184 13.9128 10.2184C15.8625 10.2184 17.7688 9.63911 19.3912 8.55457L20.6877 7.68733C20.9089 7.53938 21.1973 7.53938 21.4186 7.68733L22.715 8.55457C24.3375 9.63911 26.2437 10.2184 28.1934 10.2184H31.5884C31.9519 10.2184 32.2468 10.5143 32.2468 10.8789Z" fill="#1B69DE"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M15.2388 20.9972L18.7667 24.5471C19.0101 24.792 19.4122 24.7863 19.6641 24.5343L26.9672 17.2314C27.2191 16.9794 27.226 16.5761 26.9826 16.3312C26.7392 16.0863 26.3371 16.0919 26.0852 16.3439L19.2386 23.1904L16.1517 20.0843C15.9083 19.8394 15.5062 19.8451 15.2543 20.097C15.0023 20.349 14.9954 20.7523 15.2388 20.9972Z" fill="#22B573"></path></svg>
                        </div>
                        <p class="detail">100% genuine medicines</p>
                    </div>
                    <div class="feature-box d-flex">
                        <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="43" height="42" viewBox="0 0 43 42" fill="none"><circle cx="21.5" cy="21" r="21" fill="white"></circle><path d="M34.3051 18.3123V15.0781C34.3051 14.4827 33.8526 14 33.2944 14H6.51071C5.95251 14 5.5 14.4827 5.5 15.0781V29.0932C5.5 29.6886 5.95251 30.1713 6.5107 30.1713H24.9561" stroke="#1B69DE" stroke-linecap="round"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M37.8531 20.3793C37.8531 19.8263 37.4062 19.378 36.8548 19.378C36.8548 19.378 35.988 19.378 35.1391 19.378C34.2853 19.378 33.4504 19.1243 32.7397 18.6493L32.0845 18.2111C31.7491 17.9868 31.3125 17.9868 30.9771 18.2111L30.3219 18.6493C29.6111 19.1243 28.7763 19.378 27.9224 19.378C27.0736 19.378 26.2068 19.378 26.2068 19.378C25.6554 19.378 25.2085 19.8263 25.2085 20.3793C25.2085 20.3793 25.2085 23.5283 25.2085 25.7199C25.2085 31.2434 31.208 33.3432 31.208 33.3432C31.2087 33.3432 31.209 33.3436 31.2097 33.3439C31.418 33.4147 31.6436 33.4147 31.8519 33.3439C31.8525 33.3436 31.8529 33.3432 31.8535 33.3432C31.8535 33.3432 37.8531 31.2434 37.8531 25.7199V20.3793ZM37.1876 20.3793V25.7199C37.1876 30.7577 31.7358 32.6777 31.6373 32.7117C31.5684 32.7354 31.4932 32.7354 31.4243 32.7117C31.3258 32.6777 25.874 30.7577 25.874 25.7199V20.3793C25.874 20.1951 26.0231 20.0456 26.2068 20.0456C26.2068 20.0456 27.0736 20.0456 27.9224 20.0456C28.9077 20.0456 29.871 19.7528 30.6909 19.2048L31.3461 18.7665C31.4579 18.6917 31.6036 18.6917 31.7155 18.7665L32.3706 19.2048C33.1905 19.7528 34.1539 20.0456 35.1391 20.0456H36.8548C37.0385 20.0456 37.1876 20.1951 37.1876 20.3793Z" fill="#22B573"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M28.5925 25.4928L30.3753 27.2867C30.4983 27.4105 30.7015 27.4076 30.8288 27.2803L34.5194 23.5897C34.6467 23.4624 34.6502 23.2586 34.5272 23.1348C34.4042 23.011 34.201 23.0139 34.0737 23.1412L30.6138 26.6012L29.0538 25.0315C28.9308 24.9077 28.7276 24.9106 28.6003 25.0379C28.473 25.1652 28.4695 25.369 28.5925 25.4928Z" fill="#22B573"></path><path d="M5.75244 17.2848H34.0522" stroke="#1B69DE" stroke-width="0.6" stroke-linecap="round"></path><path d="M5.5 21.075L25.2088 21.075" stroke="#1B69DE" stroke-width="0.6" stroke-linecap="round"></path></svg>
                        </div>
                        <p class="detail">Safe & secure payments</p>
                    </div>
                    <div class="feature-box d-flex last">
                          <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42" fill="none"><circle cx="21" cy="21" r="21" fill="white"></circle><path d="M28.4364 13.4045H23.14H19.7559H14.4584C13.8779 13.4045 13.4043 13.8781 13.4043 14.4586V28.4356C13.4043 29.0172 13.8779 29.4896 14.4584 29.4896H28.4364C29.0169 29.4896 29.4894 29.0172 29.4894 28.4356V14.4586C29.4905 13.8781 29.018 13.4045 28.4364 13.4045ZM22.7512 14.1845V17.2119L21.7026 16.3082C21.629 16.2458 21.5377 16.2146 21.4474 16.2146C21.3571 16.2146 21.2658 16.2458 21.1922 16.3094L20.1459 17.2119V14.1845H22.7512ZM28.7116 28.4356C28.7116 28.5871 28.589 28.7108 28.4375 28.7108H14.4584C14.3069 28.7108 14.1843 28.5871 14.1843 28.4356V14.4586C14.1843 14.3071 14.3069 14.1845 14.4584 14.1845H19.367V18.0632C19.367 18.2158 19.455 18.354 19.5943 18.4175C19.6467 18.4409 19.7013 18.4531 19.757 18.4531C19.8484 18.4531 19.9397 18.4208 20.0122 18.3584L21.4485 17.1194L22.8871 18.3584C23.003 18.4576 23.1679 18.4788 23.3039 18.4175C23.4431 18.354 23.5312 18.2147 23.5312 18.0632V14.1845H28.4375C28.589 14.1845 28.7116 14.3071 28.7116 14.4586V28.4356Z" fill="#22B573"></path><path d="M21.0404 24.1279 16.085 24.3135 16.085 24.5416V27.2887C16.085 27.5168 16.2672 27.7024 16.4912 27.7024H21.0404C21.2644 27.7024 21.4467 27.518 21.4467 27.2887V24.5416C21.4467 24.3135 21.2656 24.1279 21.0404 24.1279Z" stroke="#22B573"></path><path d="M30.1088 33.1675C32.7062 31.2875 34.6422 28.6376 35.6389 25.5979C36.6357 22.5582 36.642 19.2848 35.6571 16.2474C34.6721 13.21 32.7464 10.5646 30.1563 8.69056C27.5661 6.81655 24.4446 5.81027 21.2394 5.81606L24.012 3.80854C24.1226 3.72688 24.1965 3.60511 24.2176 3.46971C24.2387 3.3343 24.2054 3.19622 24.1249 3.08549C24.0856 3.03047 24.0357 2.98385 23.9781 2.94836C23.9205 2.91287 23.8563 2.88924 23.7893 2.87884C23.7223 2.86844 23.6539 2.87149 23.588 2.88781C23.5221 2.90412 23.4601 2.93338 23.4057 2.97386L19.2358 5.9907C19.1465 6.03932 19.0735 6.11314 19.0261 6.20288C18.9786 6.29261 18.9587 6.39425 18.969 6.49501L18.9836 6.55827C19.0078 6.67392 19.0726 6.77705 19.1664 6.84909L23.2729 10.0396C23.3254 10.0823 23.3861 10.114 23.4513 10.1327C23.5165 10.1514 23.5848 10.1568 23.6522 10.1485C23.7196 10.1402 23.7847 10.1184 23.8435 10.0844C23.9023 10.0504 23.9537 10.005 23.9945 9.95073C24.0783 9.84345 24.1162 9.7076 24.0999 9.57287C24.0836 9.43814 24.0144 9.31548 23.9076 9.23168L20.8461 6.8527C24.0658 6.75707 27.2185 7.75898 29.7835 9.69294C32.3484 11.6269 34.1722 14.3772 34.9535 17.4896C35.7348 20.6019 35.4269 23.8901 34.0806 26.811C32.7344 29.7319 30.4303 32.1107 27.549 33.5546C24.6678 34.9984 21.3817 35.4209 18.2335 34.7523C15.0854 34.0836 12.2636 32.3638 10.2342 29.8769C8.20475 27.39 7.08917 24.2848 7.0717 21.0742C7.05422 17.8637 8.1359 14.7398 10.1381 12.2187C10.222 12.1103 10.2598 11.9734 10.2433 11.8378C10.2267 11.7021 10.1572 11.5785 10.0498 11.4939C9.99624 11.4497 9.93422 11.4168 9.86748 11.3972C9.80074 11.3776 9.73066 11.3716 9.66148 11.3798C9.59229 11.3879 9.52544 11.41 9.46496 11.4446C9.40447 11.4791 9.3516 11.5256 9.30955 11.581C7.92446 13.3332 6.94022 15.3654 6.42602 17.5347C5.71899 20.5407 5.95018 23.6875 7.0886 26.5531C8.22702 29.4187 10.2182 31.8662 12.7952 33.5672C15.3723 35.2683 18.4118 36.1416 21.5064 36.0702C24.601 35.9987 27.6026 34.9859 30.1088 33.1675Z" fill="#1B69DE"></path></svg>
                        </div>
                        <p class="detail">15 days Easy returns</p>
                    </div>
                </div>

                <!-- Product Highlights -->
                <section class="product-highlights mt-5">
                    <div class="d-flex">
                        <img src="admin/images/composition.svg" alt="" width="28" height="28">
                        <h4 class="ps-3">Product Highlights</h4>
                    </div>
                    <ul>
                        <?php if (!empty($features)): ?>
                            <?php foreach ($features as $feature): ?>
                                <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>Gently cleanses the skin</li>
                            <li>Moisturizes and nourishes</li>
                            <li>Suitable for all skin types</li>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>
        </div>

        <!-- Customers Also Bought Carousel -->
        <section class="also-bought-carousel-section mt-5 p-4 rounded-3">
            <h4 class="mb-4">Customers Also Bought</h4>
            <div class="carousel-container d-flex gap-4 overflow-auto pb-2">
                <?php while ($rprod = $relatedProducts->fetch_assoc()): ?>
                    <div class="carousel-item-card bg-white">
                        <a href="single_product.php?id=<?php echo $rprod['id']; ?>" class="text-decoration-none text-dark d-block">
                            <?php if (!empty($rprod['discount_percent'])): ?>
                                <div class="discount-badge">
                                    <?= intval($rprod['discount_percent']); ?>% OFF
                                </div>
                            <?php endif; ?>
                            <img src="admin/images/products/<?php echo htmlspecialchars($rprod['image']); ?>" alt="<?php echo htmlspecialchars($rprod['name']); ?>" class="img-fluid mt-3 mx-auto d-block product-img">
                            <div class="p-3 text-truncate-container">
                                <p class="product-name mb-2" title="<?php echo htmlspecialchars($rprod['name']); ?>">
                                    <?php 
                                    echo htmlspecialchars(mb_strimwidth($rprod['name'], 0, 25, '...')); 
                                    ?>
                                </p>
                                <div class="product-price-add d-flex align-items-center justify-content-between mt-2">
                                    <div class="mrp-section">
                                        <span class="label">MRP</span>
                                        <?php if (!empty($rprod['mrp'])): ?>
                                            <span class="mrp m-0"><s>&#8377;<?php echo number_format($rprod['mrp'], 2); ?></s></span><br>
                                            <p class="m-0 price-discount">&#8377;<?php echo number_format($rprod['price'], 2); ?></p>
                                        <?php else: ?>
                                            <span class="price-discount m-0">&#8377;<?php echo number_format($rprod['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" action="add_to_cart.php" class="ms-3">
                                        <input type="hidden" name="product_id" value="<?php echo $rprod['id']; ?>">
                                        <button type="submit" class="btn add-btn">Add</button>
                                    </form>
                                </div>                  
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Tabs for Description, Ingredients, etc. -->
        <section class="product-tabs mt-5">
            <ul class="tabs-nav">
                <li class="active" data-tab="description">Product Description</li>
                <li data-tab="ingredients">Ingredients</li>
                <li data-tab="key-uses">Key Uses</li>
                <li data-tab="how-to">How To Use</li>
                <li data-tab="safety-info">Safety Information</li>
                <li data-tab="additional-info">Additional Information</li>
            </ul>

            <div class="tabs-content">
                <div class="tab-pane active" id="description">
                    <div class="d-flex">
                        <img src="admin/images/description.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">Product Description of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <ul>
                        <?php foreach($descriptionItems as $item): 
                            $trimmed = trim($item);
                            if (!empty($trimmed)):
                        ?>
                            <li><?php echo htmlspecialchars($trimmed); ?></li>
                        <?php 
                            endif;
                        endforeach; ?>
                    </ul>
                </div>
                <div class="tab-pane" id="ingredients">
                    <div class="d-flex">
                        <img src="admin/images/Ingredient.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">Composition of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <ul>
                        <?php foreach($compositionItems as $composition): 
                            if (!empty($composition)):
                        ?>
                            <li><?php echo htmlspecialchars($composition); ?></li>
                        <?php 
                            endif;
                        endforeach; ?>
                    </ul>
                </div>
                <div class="tab-pane" id="key-uses">
                    <div class="d-flex">
                        <img src="admin/images/key-uses.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">Key Uses of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <ul>
                        <?php foreach($keyUseItems as $keyUse): 
                            $parts = explode(':', $keyUse, 2);
                            $before = htmlspecialchars(trim($parts[0] ?? ''));
                            $after = htmlspecialchars(trim($parts[1] ?? ''));
                        ?>
                            <li><strong><?php echo $before; ?>:</strong><?php echo $after; ?></li>
                        <?php 
                        endforeach; ?>
                    </ul>
                </div>
                <div class="tab-pane" id="how-to">
                    <div class="d-flex">
                        <img src="admin/images/how-to-use.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">How To Use of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <ul>
                        <?php foreach($howToUseItems as $howTo): 
                            if (!empty($howTo)):
                        ?>
                            <li><?php echo htmlspecialchars($howTo); ?></li>
                        <?php 
                            endif;
                        endforeach; ?>
                    </ul>
                </div>
                <div class="tab-pane" id="safety-info">
                    <div class="d-flex">
                        <img src="admin/images/safety.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">Safety Information of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <ul>
                        <?php foreach($safetyInformationItems as $safety): 
                            if (!empty($safety)):
                        ?>
                            <li><?php echo htmlspecialchars($safety); ?></li>
                        <?php 
                            endif;
                        endforeach; ?>
                    </ul>
                </div>
                <div class="tab-pane" id="additional-info">
                    <div class="d-flex">
                        <img src="admin/images/additional-info.svg" alt="" width="40" height="40">
                        <h2 class="header ps-3 mb-0">Additional Information of <?php echo nl2br(htmlspecialchars($product['name'])); ?></h2>
                    </div>
                    <?php foreach($addInformationItems as $addtionalInfo): 
                        $addInfo = explode(':', $addtionalInfo, 2);
                        $head = htmlspecialchars(trim($addInfo[0] ?? ''));
                        $body = htmlspecialchars(trim($addInfo[1] ?? ''));
                        $sentences = preg_split('/\;\s*/', trim($body), -1, PREG_SPLIT_NO_EMPTY);
                    ?>
                        <h3 class="add-info-btn"><?php echo $head; ?></h3>
                        <ul>
                            <?php foreach ($sentences as $sentence): ?>
                                <li><?php echo htmlspecialchars(trim($sentence)) . '.'; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php 
                    endforeach; ?>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section mt-5">
            <h4>Frequently Asked Questions</h4>
            <?php
            $faqData = [];
            try {
                $faqData = json_decode($product['faq'] ?? '[]', true);
                if (!is_array($faqData)) {
                    $faqData = [];  // Reset if not an array
                }
            } catch (Exception $e) {
                $faqData = [];  // Fallback on error
            }
                        if (empty($faqData)) {
                // Default FAQ if none in DB
                $faqData = [
                    'Can this product be used on sensitive skin?:Yes, it is suitable for sensitive skin.',
                    'How often should I use this product?:Use it twice daily for best results.'
                ];
            }
            ?>
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqData as $index => $item): ?>
                    <?php
                    // Parse the string "Question?:Answer" into question and answer
                    $parts = explode('?:', $item, 2);  // Split only on the first "?:"
                    $question = trim($parts[0] ?? 'Question');
                    $answer = trim($parts[1] ?? 'Answer');
                    
                    // Ensure question ends with "?"
                    if (!str_ends_with($question, '?')) {
                        $question .= '?';
                    }
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                            <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                <?php echo htmlspecialchars($question); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($answer)); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Certified Content -->
        <section class="certified-content mt-5 d-flex gap-4 align-items-center">
            <div class="expert">
                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="<?php echo htmlspecialchars($product['certified_writer'] ?? 'Writer'); ?>" class="certified-image" />
                <div>
                    <strong><?php echo htmlspecialchars($product['certified_writer'] ?? 'Dr. Divya Mandal'); ?></strong><br />
                    <?php echo htmlspecialchars($product['certified_writer_title'] ?? 'Medical Science Writer | PhD in Chemistry'); ?>
                </div>
            </div>
            <div class="reviewer">
                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="<?php echo htmlspecialchars($product['certified_reviewer'] ?? 'Reviewer'); ?>" class="certified-image" />
                <div>
                    <strong><?php echo htmlspecialchars($product['certified_reviewer'] ?? 'Dr. Mandeep Chadha'); ?></strong><br />
                    <?php echo htmlspecialchars($product['certified_reviewer_title'] ?? 'Medical Content Reviewer | M.B.B.S., DNB'); ?>
                </div>
            </div>
        </section>

        <!-- Manufacturer & Disclaimer -->
        <section class="manufacturer mt-5">
            <h4>Manufacturer Details</h4>
            <p><?php echo nl2br(htmlspecialchars($product['manufacturer_details'] ?? 'Lotus Corporate Park, Wind Unit 801&802 ... Mumbai, Maharashtra 400063')); ?></p>
            <h4>Disclaimer</h4>
            <p><?php echo nl2br(htmlspecialchars($product['disclaimer'] ?? 'This product is not intended for diagnosis, treatment, cure, or prevention of any disease...')); ?></p>
        </section>
    </div>

    <script src="assets/single_product.js"></script>
    <script>
        // Tabs navigation
        document.querySelectorAll('.tabs-nav li').forEach((tab, idx) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tabs-nav li').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.querySelectorAll('.tab-pane')[idx].classList.add('active');
            });
        });

        // FAQ toggle
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const ans = q.nextElementSibling;
                if (ans.style.display === 'block') {
                    ans.style.display = 'none';
                } else {
                    ans.style.display = 'block';
                }
                q.classList.toggle('expanded');
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>