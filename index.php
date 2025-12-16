<?php
include 'config/db.php';
include 'includes/auth.php';

$featured = $conn->query("SELECT * FROM products");
$personalCare = $conn->query("SELECT * FROM products WHERE category = 'Personal Care'");

$bannerImages = [
    "admin/images/Banner1.png",
    "admin/images/Banner2.png",
    "admin/images/Banner3.png",
    "admin/images/Banner4.png",
    "admin/images/Banner5.png",
    "admin/images/Banner6.png",
    "admin/images/Banner7.png",
    "admin/images/Banner8.png",
    "admin/images/Banner9.png"
    // Add your actual banner image URLs here
];

$groupedImages = [];
$totalImages = count($bannerImages);
for ($i = 0; $i < $totalImages - 1; $i++) {
    $groupedImages[] = [$bannerImages[$i], $bannerImages[$i + 1]];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medicare Pharma - Your Trusted Online Pharmacy</title>
    <link rel="icon" href="admin/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/footer.css">
    <link rel="stylesheet" href="assets/index.css"> <!-- Page-specific styles -->
    <link rel="stylesheet" href="assets/catagories.css"> <!-- Page-specific styles -->
    <link rel="stylesheet" href="assets/banner.css"> <!-- Page-specific styles -->
    <link rel="stylesheet" href="assets/featured_products.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">        
            <h1>your health our priority, Your friendly neighborhood pharmacy</h1>
            <p>Order medicines online with cash on delivery. Fast, safe, and reliable.</p>
            <a href="products.php" class="btn btn-success mt-3">Shop Now</a>
        </div>
    </section>

<!-- Banner Slider Section -->
<section class="banner-slider-section">
    <div class="slider-wrapper">
        <button class="arrow arrow-left" onclick="prevSlide()">&#10094;</button>
        <button class="arrow arrow-right" onclick="nextSlide()">&#10095;</button>
        <div class="slider-container" id="sliderContainer">
            <?php foreach ($groupedImages as $group): ?>
                <div class="slide">
                    <a href="products.php" class="slide-link">
                        <div class="slide-images">
                            <?php foreach ($group as $img): ?>
                                <div class="banner-item">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Banner Image" />
                                    <button class="banner-btn">Shop Now</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="dots-container" id="dotsContainer"></div>
</section>

<!-- Shop by Categories Section -->
    <section class="shop-by-categories">
        <div class="container">
            <div class="header">
                <h3>Shop by categories</h3>
                <a href="products.php" class="view-all">View All</a>
            </div>
            <div class="categories-wrapper">
                <!-- Sidebar -->
                <nav class="category-menu" id="categoryMenu">
                    <ul>
                        <li class="active" data-category="Personal Care">
                            <img src="admin/images/categories/personal-care.png" alt="Personal Care" />
                            <span>Personal Care</span>
                        </li>
                        <li data-category="Health Conditions">
                            <img src="admin/images/categories/health-conditions.png" alt="Health Conditions" />
                            <span>Health Conditions</span>
                        </li>
                        <li data-category="Vitamins & Supplements">
                            <img src="admin/images/categories/vitamins.png" alt="Vitamins & Supplements" />
                            <span>Vitamins & Supplements</span>
                        </li>
                        <li data-category="Diabetes Care">
                            <img src="admin/images/categories/diabetes-care.png" alt="Diabetes Care" />
                            <span>Diabetes Care</span>
                        </li>
                        <li data-category="Healthcare Devices">
                            <img src="admin/images/categories/healthcare-devices.png" alt="Healthcare Devices" />
                            <span>Healthcare Devices</span>
                        </li>
                        <li data-category="Homeopathic Medicine">
                            <img src="admin/images/categories/homeopathic-medicine.png" alt="Homeopathic Medicine" />
                            <span>Homeopathic Medicine</span>
                        </li>
                    </ul>
                </nav>

                <!-- Subcategories (Dynamic) -->
                <div class="right-panel">
                    <div class="category-items" id="categoryItems">
                        <div class="loading">Loading subcategories...</div>
                    </div>
                </div>
            </div>
            <!-- <div class="footer-link">
                <a href="#" id="viewAllLink">View all personal care products &raquo;</a>
            </div> -->
        </div>
    </section>

    <!-- Featured Products Section -->
    <?php
// Function to render a product section dynamically
function renderProductSection($title, $products, $carouselId) {
    ?>
    <section class="featured py-5">
        <div class="container">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <div class="featured-carousel-container">
                <button class="carousel-nav prev">&#9664;</button>
                
                <div class="featured-carousel d-flex overflow-auto py-2" id="<?php echo $carouselId; ?>">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="featured-card flex-shrink-0">
                            <div class="card position-relative h-100">
                                <?php if (!empty($product['discount_percent'])): ?>
                                <div class="discount-badge">
                                    <?php echo intval($product['discount_percent']); ?>% OFF
                                </div>
                                <?php endif; ?>

                                <a class="text-decoration-none text-dark d-block" href="single_product.php?id=<?php echo $product['id']; ?>">
                                    <img src="admin/images/products/<?php echo htmlspecialchars($product['image']); ?>" class="img-fluid product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body p-3">
                                        <h6 class="card-title text-truncate"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <div class="sc-a966d261-4 byjgJY"></div>
                                        <div class="product-price-add d-flex align-items-center justify-content-between mt-2">
                                            <div class="mrp-section">
                                                <span class="label">MRP</span>
                                                <?php if (!empty($product['mrp'])): ?>
                                                    <span class="mrp m-0"><s>&#8377;<?php echo number_format($product['mrp'], 2); ?></s></span><br>
                                                    <p class="m-0 price-discount">&#8377;<?php echo number_format($product['price'], 2); ?></p>
                                                <?php else: ?>
                                                    <span class="price-discount m-0">&#8377;<?php echo number_format($product['price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <form method="POST" action="add_to_cart.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn add-btn">Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <button class="carousel-nav next">&#9654;</button>
            </div>
        </div>
    </section>
    <?php
}

// Usage examples (assuming $featured and $personalCare are your result sets)
renderProductSection("Featured Products", $featured, "featuredCarousel");
renderProductSection("Personal Care Products", $personalCare, "personalCareCarousel");
?>

    
    <!-- Why Choose Us Section -->
    <section class="why-choose-us">
        <div class="container">
            <h2 class="text-center mb-4">Why Choose Us?</h2>
            <div class="row">
                <div class="col-md-4 feature">
                    <div class="feature-icon">🚚</div>
                    <h5>Fast Delivery</h5>
                    <p>Get your medicines delivered within 24 hours.</p>
                </div>
                <div class="col-md-4 feature">
                    <div class="feature-icon">💰</div>
                    <h5>Cash on Delivery</h5>
                    <p>Pay only when you receive your order.</p>
                </div>
                <div class="col-md-4 feature">
                    <div class="feature-icon">🔒</div>
                    <h5>Secure & Trusted</h5>
                    <p>Safe online shopping with verified products.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2>What Our Customers Say</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="admin/images/customer1.jpg" alt="John Doe" class="testimonial-avatar">
                        <h5>John Doe</h5>
                        <p class="testimonial-quote">"Excellent service! My medicines arrived on time, and the quality is top-notch."</p>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="admin/images/customer2.jpg" alt="Jane Smith" class="testimonial-avatar">
                        <h5>Jane Smith</h5>
                        <p class="testimonial-quote">"Cash on delivery made it so convenient. Highly recommend for health needs."</p>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="admin/images/customer3.jpg" alt="Alex Brown" class="testimonial-avatar">
                        <h5>Alex Brown</h5>
                        <p class="testimonial-quote">"Fast and reliable. The website is easy to use, and support is great."</p>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="blog">
        <div class="container">
            <h2>Latest from Our Blog</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card blog-card">
                        <img src="admin/images/blog1.jpg" alt="Health Tips" class="card-img-top blog-img">
                        <div class="card-body">
                            <h5>10 Tips for Better Health</h5>
                            <p>Discover simple ways to maintain a healthy lifestyle with our expert advice.</p>
                            <a href="#" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card blog-card">
                        <img src="admin/images/blog2.jpg" alt="Medicine Guide" class="card-img-top blog-img">
                        <div class="card-body">
                            <h5>Understand Common Medicines</h5>
                            <p>Learn about popular medicines and how they work for your well-being.</p>
                            <a href="#" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card blog-card">
                        <img src="admin/images/blog3.jpg" alt="Nutrition" class="card-img-top blog-img">
                        <div class="card-body">
                            <h5>Nutrition for Immunity</h5>
                            <p>Boost your immune system with the right nutrients and daily habits.</p>
                            <a href="#" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/banner.js"></script>
    <script src="assets/featured_products.js"></script>

    <!-- JavaScript to switch right side products by clicking left menu -->
<!-- JavaScript for Dynamic Loading -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const menuItems = document.querySelectorAll("#categoryMenu li");
        const categoryItemsContainer = document.getElementById("categoryItems");
        const viewAllLink = document.getElementById("viewAllLink");

// Function to load subcategories
function loadSubcategories(category) {
    categoryItemsContainer.innerHTML = '<div class="loading">Loading subcategories...</div>';

    fetch(`get_subcategories.php?category=${encodeURIComponent(category)}`)
        .then(response => response.json())
        .then(subData => {
            let html = '';
            if (subData.length > 0) {
                subData.forEach(sub => {
                    const imagePath = `admin/images/subcategories/${sub.toLowerCase().replace(/ /g, '-')}.png`;
                    html += `
                        <div class="category-item">
                            <img src="${imagePath}" alt="${sub}" />
                            <h5>${sub}</h5>
                            <a href="products.php?category=${encodeURIComponent(category)}&subcategory=${encodeURIComponent(sub)}" class="btn btn-primary">View Products</a>
                        </div>
                    `;
                });
            } else {
                html += '<p>No subcategories found.</p>';
            }
            // Add "View All Products" button
            html += `
                <div class="category-item view-all-item">
                    <h5>View All ${category} Products</h5>
                    <a href="products.php?category=${encodeURIComponent(category)}" class="btn btn-success">View All</a>
                </div>
            `;
            categoryItemsContainer.innerHTML = html;
        })
        .catch(error => {
            categoryItemsContainer.innerHTML = '<div class="error">Error loading subcategories.</div>';
            console.error('Subcategories error:', error);
        });
}

        // Load default category on page load
        loadSubcategories('Personal Care');

        // Handle category clicks
        menuItems.forEach(item => {
            item.addEventListener("click", () => {
                menuItems.forEach(i => i.classList.remove("active"));
                item.classList.add("active");

                const selectedCategory = item.getAttribute("data-category");
                loadSubcategories(selectedCategory);

                // Update View All link
                const categoryName = item.querySelector('span').textContent.toLowerCase();
                viewAllLink.href = `products.php?category=${encodeURIComponent(selectedCategory)}`;
                viewAllLink.textContent = `View all ${categoryName} products »`;
            });
        });
    });
    
    </script>

</body>
</html>