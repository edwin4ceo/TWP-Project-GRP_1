<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = intval($_GET['id']);
$query = "SELECT id, name, description, price, image FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Product not found.");
}

$product = mysqli_fetch_assoc($result);

// Rating and Comments System
$errors = [];
$success_message = "";
$form_data = [
    'rating' => isset($_POST['rating']) ? (int)$_POST['rating'] : 0,
    'comment' => isset($_POST['comment']) ? trim($_POST['comment']) : '',
    'customer_name' => isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '',
    'customer_email' => isset($_POST['customer_email']) ? trim($_POST['customer_email']) : ''
];

// Pre-fill data for logged-in customers
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer') {
    $customer_query = "SELECT name, email FROM customers WHERE id = ?";
    $stmt = mysqli_prepare($conn, $customer_query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $customer_result = mysqli_stmt_get_result($stmt);
    $customer_data = mysqli_fetch_assoc($customer_result);
    
    if ($customer_data && empty($_POST)) {
        $form_data['customer_name'] = $customer_data['name'];
        $form_data['customer_email'] = $customer_data['email'];
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    // Validate inputs
    if ($form_data['rating'] < 1 || $form_data['rating'] > 5) {
        $errors[] = "Please select a rating between 1 and 5 stars.";
    }
    if (empty($form_data['comment'])) {
        $errors[] = "Comment is required.";
    } elseif (strlen($form_data['comment']) < 10) {
        $errors[] = "Comment must be at least 10 characters long.";
    }
    if (empty($form_data['customer_name'])) {
        $errors[] = "Your name is required.";
    }
    if (!filter_var($form_data['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Check if customer already reviewed this product
    $check_query = "SELECT id FROM product_reviews WHERE product_id = ? AND customer_email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "is", $product_id, $form_data['customer_email']);
    mysqli_stmt_execute($stmt);
    $existing_review = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($existing_review) > 0) {
        $errors[] = "You have already reviewed this product.";
    }
    mysqli_stmt_close($stmt);

    // If no errors, save the rating and comment
    if (empty($errors)) {
        $customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $query = "INSERT INTO product_reviews (product_id, customer_id, customer_name, customer_email, rating, comment, ip_address, review_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iississ", $product_id, $customer_id, $form_data['customer_name'], $form_data['customer_email'], $form_data['rating'], $form_data['comment'], $ip_address);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Thank you for your review! Your feedback has been submitted successfully.";
                // Reset form data after successful submission
                $form_data = ['rating' => 0, 'comment' => '', 'customer_name' => '', 'customer_email' => ''];
            } else {
                $errors[] = "Failed to submit your review. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database error. Please try again later.";
        }
    }
}

// Fetch existing reviews for this product
$query = "SELECT pr.customer_name, pr.rating, pr.comment, pr.review_date, pr.helpful_count, pr.is_verified_purchase 
          FROM product_reviews pr 
          WHERE pr.product_id = ? AND pr.is_approved = TRUE 
          ORDER BY pr.review_date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reviews = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reviews[] = $row;
}
mysqli_stmt_close($stmt);

// Calculate average rating
$avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ? AND is_approved = TRUE";
$stmt = mysqli_prepare($conn, $avg_query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rating_data = mysqli_fetch_assoc($result);
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['total_reviews'];
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="product-detail-styles.css">
    <link rel="icon" href="images/logo.png" type="image/png" />
    
    <style>
        /* Rating System Styles */
        .rating-display {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 8px;
        }
        
        .stars {
            color: #FFD700;
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .rating-text {
            color: #666;
            font-weight: bold;
        }
        
        .reviews-section {
            margin-top: 3rem;
            max-width: 100%;
        }
        
        .review-form {
            background: #f9f9f9;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .review-form h3 {
            color: #8B4513;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .star-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
            flex-direction: row-reverse;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFD700;
        }
        
        .submit-btn {
            background: #8B4513;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #A0522D;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .existing-reviews {
            margin-top: 2rem;
        }
        
        .existing-reviews h3 {
            color: #8B4513;
            margin-bottom: 1.5rem;
        }
        
        .review-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        
        .verified-badge {
            background: #4CAF50;
            color: white;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-stars {
            color: #FFD700;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .review-comment {
            line-height: 1.6;
            color: #444;
            margin-bottom: 0.5rem;
        }
        
        .review-helpful {
            color: #666;
            font-size: 0.9rem;
        }
        
        .no-reviews {
            text-align: center;
            padding: 2rem;
            color: #666;
            background: #f8f8f8;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .star-rating label {
                font-size: 2rem;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-bar">
            <div class="logo-left">
                <a href="index.php">
                    <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline" />
                </a>
                <div class="search-center">
                    <form class="search-form" action="products.php" method="get">
                        <input type="text" name="search" placeholder="Welcome To BakeEase" />
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>
            <div class="header-right">
                <div class="nav-dropdown">
                    <span class="nav-toggle" id="navToggle" title="Navigation Menu">☰</span>
                    <div class="dropdown-menu" id="navMenu">
                        <a href="index.php">Home</a>
                        <a href="products.php">Products</a>
                        <a href="about.php">About Us</a>
                        <a href="contact.php">Contact</a>
                    </div>
                </div>
                <div class="cart-dropdown">
                    <span class="cart-icon" id="cartToggle">🛒</span>
                    <div class="dropdown-menu" id="cartMenu">
                        <a href="shoppingCart.php">View Cart</a>
                        <a href="checkout.php">Checkout</a>
                    </div>
                </div>
                <div class="profile-dropdown">
                    <span class="profile-icon" id="profileToggle">👤</span>
                    <div class="dropdown-menu" id="profileMenu">
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                            <a href="profile.php">Profile</a>
                            <a href="logout.php">Logout</a>
                        <?php else: ?>
                            <a href="register.php">Sign Up</a>
                            <a href="login.php">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="product-detail-container">
        <section class="product-detail">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
            <div class="product-info">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p><?= htmlspecialchars($product['description']) ?></p>
                <p><strong>RM <?= number_format($product['price'], 2) ?></strong></p>
                
                <!-- Rating Display -->
                <div class="rating-display">
                    <div class="stars">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $avg_rating ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <span class="rating-text"><?= $avg_rating ?>/5 (<?= $total_reviews ?> reviews)</span>
                </div>
                
                <ul>
                    <li>Weight: 1kg (default)</li>
                    <li>Freshly baked daily</li>
                    <li>Fast delivery available</li>
                </ul>
                <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="number" name="quantity" value="1" min="1" max="99" class="quantity-input">
                    <button type="submit" class="add-to-cart-button">Add to Cart</button>
                </form>
                <a href="products.php" class="back-button">← Back to Products</a>
            </div>
        </section>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>Customer Reviews</h2>
            
            <!-- Review Form -->
            <div class="review-form">
                <h3>Write a Review</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success">
                        <p><?= htmlspecialchars($success_message) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Rating*</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5" <?= $form_data['rating'] == 5 ? 'checked' : '' ?>>
                            <label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4" <?= $form_data['rating'] == 4 ? 'checked' : '' ?>>
                            <label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3" <?= $form_data['rating'] == 3 ? 'checked' : '' ?>>
                            <label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2" <?= $form_data['rating'] == 2 ? 'checked' : '' ?>>
                            <label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1" <?= $form_data['rating'] == 1 ? 'checked' : '' ?>>
                            <label for="star1">★</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_name">Your Name*</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($form_data['customer_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email">Your Email*</label>
                        <input type="email" id="customer_email" name="customer_email" value="<?= htmlspecialchars($form_data['customer_email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Your Review*</label>
                        <textarea id="comment" name="comment" placeholder="Share your experience with this product..." required><?= htmlspecialchars($form_data['comment']) ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
                </form>
            </div>
            
            <!-- Existing Reviews -->
            <?php if (!empty($reviews)): ?>
                <div class="existing-reviews">
                    <h3>Recent Reviews (<?= $total_reviews ?>)</h3>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <span class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></span>
                                    <?php if ($review['is_verified_purchase']): ?>
                                        <span class="verified-badge">Verified Purchase</span>
                                    <?php endif; ?>
                                </div>
                                <span class="review-date"><?= date('M j, Y', strtotime($review['review_date'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="review-comment">
                                <?= htmlspecialchars($review['comment']) ?>
                            </div>
                            <?php if ($review['helpful_count'] > 0): ?>
                                <div class="review-helpful">
                                    <?= $review['helpful_count'] ?> people found this helpful
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <!-- JavaScript for dropdowns and star rating -->
    <script>
        const profileToggle = document.getElementById("profileToggle");
        const profileMenu = document.getElementById("profileMenu");
        const cartToggle = document.getElementById("cartToggle");
        const cartMenu = document.getElementById("cartMenu");
        const navToggle = document.getElementById("navToggle");
        const navMenu = document.getElementById("navMenu");

        // Toggle Profile Menu
        profileToggle.addEventListener("click", () => {
            profileMenu.style.display = (profileMenu.style.display === "block") ? "none" : "block";
            cartMenu.style.display = "none";
        });

        // Toggle Cart Menu
        cartToggle.addEventListener("click", () => {
            cartMenu.style.display = (cartMenu.style.display === "block") ? "none" : "block";
            profileMenu.style.display = "none";
        });

        navToggle.addEventListener("click", () => {
            navMenu.style.display = (navMenu.style.display === "block") ? "none" : "block";
        });

        // Hide dropdowns when clicking outside
        document.addEventListener("click", (e) => {
            if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = "none";
            }
            if (!cartToggle.contains(e.target) && !cartMenu.contains(e.target)) {
                cartMenu.style.display = "none";
            }
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.style.display = "none";
            }
        });

        // Star rating functionality
        const starInputs = document.querySelectorAll('.star-rating input');
        const starLabels = document.querySelectorAll('.star-rating label');

        starInputs.forEach((input, index) => {
            input.addEventListener('change', () => {
                starLabels.forEach((label, labelIndex) => {
                    if (labelIndex <= index) {
                        label.style.color = '#FFD700';
                    } else {
                        label.style.color = '#ddd';
                    }
                });
            });
        });

        // Initialize star display based on current selection
        const checkedInput = document.querySelector('.star-rating input:checked');
        if (checkedInput) {
            const checkedIndex = Array.from(starInputs).indexOf(checkedInput);
            starLabels.forEach((label, labelIndex) => {
                if (labelIndex <= checkedIndex) {
                    label.style.color = '#FFD700';
                } else {
                    label.style.color = '#ddd';
                }
            });
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>