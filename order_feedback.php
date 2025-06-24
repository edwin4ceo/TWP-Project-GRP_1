<?php
session_start();
require_once 'db_connection.php';

// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: index.php");
    exit();
}

// Check if user is logged in and owns this order
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Verify order belongs to current user
$query = "SELECT o.id, o.customer_name, o.total_amount, o.order_date, o.status 
          FROM orders o 
          WHERE o.id = ? AND o.customer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Fetch order items
$query = "SELECT oi.product_id, oi.quantity, oi.price, p.name, p.image 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $order_items[] = $row;
}
mysqli_stmt_close($stmt);

// Check if feedback already submitted for this order
$query = "SELECT id FROM order_feedback WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$feedback_exists = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Initialize variables
$errors = [];
$success_message = "";
$form_data = [
    'overall_rating' => isset($_POST['overall_rating']) ? (int)$_POST['overall_rating'] : 0,
    'delivery_rating' => isset($_POST['delivery_rating']) ? (int)$_POST['delivery_rating'] : 0,
    'product_quality_rating' => isset($_POST['product_quality_rating']) ? (int)$_POST['product_quality_rating'] : 0,
    'comments' => isset($_POST['comments']) ? trim($_POST['comments']) : '',
    'would_recommend' => isset($_POST['would_recommend']) ? $_POST['would_recommend'] : '',
    'improvement_suggestions' => isset($_POST['improvement_suggestions']) ? trim($_POST['improvement_suggestions']) : ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$feedback_exists) {
    // Validate inputs
    if ($form_data['overall_rating'] < 1 || $form_data['overall_rating'] > 5) {
        $errors[] = "Please select an overall rating between 1 and 5 stars.";
    }
    if ($form_data['delivery_rating'] < 1 || $form_data['delivery_rating'] > 5) {
        $errors[] = "Please rate our delivery service.";
    }
    if ($form_data['product_quality_rating'] < 1 || $form_data['product_quality_rating'] > 5) {
        $errors[] = "Please rate the product quality.";
    }
    if (empty($form_data['comments'])) {
        $errors[] = "Please share your experience with us.";
    } elseif (strlen($form_data['comments']) < 3) {
        $errors[] = "Please provide more detailed feedback (at least 3 characters).";
    }
    if (empty($form_data['would_recommend'])) {
        $errors[] = "Please let us know if you would recommend us to others.";
    }

    // If no errors, save the feedback
    if (empty($errors)) {
        $query = "INSERT INTO order_feedback (order_id, customer_id, overall_rating, delivery_rating, product_quality_rating, comments, would_recommend, improvement_suggestions, feedback_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiiissss", 
                $order_id, 
                $_SESSION['user_id'], 
                $form_data['overall_rating'], 
                $form_data['delivery_rating'], 
                $form_data['product_quality_rating'], 
                $form_data['comments'], 
                $form_data['would_recommend'], 
                $form_data['improvement_suggestions']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Thank you for your valuable feedback! Your review helps us improve our service.";
                $feedback_exists = true; // Prevent further submissions
            } else {
                $errors[] = "Failed to submit your feedback. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Feedback - BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="images/logo.png" type="image/png" />
    <style>
        .feedback-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .order-summary {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .order-summary h3 {
            color: #8B4513;
            margin-bottom: 1rem;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .order-detail {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .order-detail strong {
            display: block;
            color: #8B4513;
            margin-bottom: 0.5rem;
        }
        
        .order-items {
            margin-top: 1rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        
        .feedback-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .feedback-form h2 {
            color: #8B4513;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .rating-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #fafafa;
            border-radius: 6px;
        }
        
        .rating-section h4 {
            color: #8B4513;
            margin-bottom: 1rem;
        }
        
        .star-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            flex-direction: row-reverse;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFD700;
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
        
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            resize: vertical;
            min-height: 120px;
            box-sizing: border-box;
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .radio-group input {
            margin-right: 0.5rem;
        }
        
        .submit-btn {
            background: #8B4513;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            transition: background 0.3s;
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
            text-align: center;
        }
        
        .thank-you-message {
            text-align: center;
            padding: 2rem;
        }
        
        .thank-you-message h3 {
            color: #8B4513;
            margin-bottom: 1rem;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #8B4513;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .star-rating label {
                font-size: 1.8rem;
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
                        <input type="text" name="search" placeholder="Search our bakery..." />
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>
            <div class="header-right">
                <div class="nav-dropdown">
                    <span class="nav-toggle" id="navToggle">☰</span>
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
                        <a href="profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="feedback-container">
            <a href="index.php" class="back-link">← Back to Home</a>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order #<?= $order['id'] ?> Summary</h3>
                <div class="order-info">
                    <div class="order-detail">
                        <strong>Order Date</strong>
                        <?= date('M j, Y', strtotime($order['order_date'])) ?>
                    </div>
                    <div class="order-detail">
                        <strong>Total Amount</strong>
                        RM <?= number_format($order['total_amount'], 2) ?>
                    </div>
                    <div class="order-detail">
                        <strong>Status</strong>
                        <?= ucfirst($order['status']) ?>
                    </div>
                    <div class="order-detail">
                        <strong>Customer</strong>
                        <?= htmlspecialchars($order['customer_name']) ?>
                    </div>
                </div>
                
                <div class="order-items">
                    <h4>Items Purchased:</h4>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                Quantity: <?= $item['quantity'] ?> | Price: RM <?= number_format($item['price'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($feedback_exists && empty($success_message)): ?>
                <div class="thank-you-message">
                    <h3>Thank You!</h3>
                    <p>You have already submitted feedback for this order.</p>
                    <p>We appreciate your time and valuable input!</p>
                    <a href="index.php" class="back-link">← Return to Home</a>
                </div>
            <?php else: ?>
                <!-- Feedback Form -->
                <div class="feedback-form">
                    <h2>How was your experience?</h2>
                    <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                        Your feedback helps us improve our products and service quality.
                    </p>
                    
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
                            <a href="index.php" class="back-link">← Return to Home</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <!-- Overall Rating -->
                            <div class="rating-section">
                                <h4>Overall Experience</h4>
                                <p>How would you rate your overall experience with BakeEase Bakery?</p>
                                <div class="star-rating">
                                    <input type="radio" name="overall_rating" value="5" id="overall5" <?= $form_data['overall_rating'] == 5 ? 'checked' : '' ?>>
                                    <label for="overall5">★</label>
                                    <input type="radio" name="overall_rating" value="4" id="overall4" <?= $form_data['overall_rating'] == 4 ? 'checked' : '' ?>>
                                    <label for="overall4">★</label>
                                    <input type="radio" name="overall_rating" value="3" id="overall3" <?= $form_data['overall_rating'] == 3 ? 'checked' : '' ?>>
                                    <label for="overall3">★</label>
                                    <input type="radio" name="overall_rating" value="2" id="overall2" <?= $form_data['overall_rating'] == 2 ? 'checked' : '' ?>>
                                    <label for="overall2">★</label>
                                    <input type="radio" name="overall_rating" value="1" id="overall1" <?= $form_data['overall_rating'] == 1 ? 'checked' : '' ?>>
                                    <label for="overall1">★</label>
                                </div>
                            </div>

                            <!-- Delivery Rating -->
                            <div class="rating-section">
                                <h4>Delivery Service</h4>
                                <p>How satisfied were you with our delivery service?</p>
                                <div class="star-rating">
                                    <input type="radio" name="delivery_rating" value="5" id="delivery5" <?= $form_data['delivery_rating'] == 5 ? 'checked' : '' ?>>
                                    <label for="delivery5">★</label>
                                    <input type="radio" name="delivery_rating" value="4" id="delivery4" <?= $form_data['delivery_rating'] == 4 ? 'checked' : '' ?>>
                                    <label for="delivery4">★</label>
                                    <input type="radio" name="delivery_rating" value="3" id="delivery3" <?= $form_data['delivery_rating'] == 3 ? 'checked' : '' ?>>
                                    <label for="delivery3">★</label>
                                    <input type="radio" name="delivery_rating" value="2" id="delivery2" <?= $form_data['delivery_rating'] == 2 ? 'checked' : '' ?>>
                                    <label for="delivery2">★</label>
                                    <input type="radio" name="delivery_rating" value="1" id="delivery1" <?= $form_data['delivery_rating'] == 1 ? 'checked' : '' ?>>
                                    <label for="delivery1">★</label>
                                </div>
                            </div>

                            <!-- Product Quality Rating -->
                            <div class="rating-section">
                                <h4>Product Quality</h4>
                                <p>How would you rate the quality of our baked goods?</p>
                                <div class="star-rating">
                                    <input type="radio" name="product_quality_rating" value="5" id="quality5" <?= $form_data['product_quality_rating'] == 5 ? 'checked' : '' ?>>
                                    <label for="quality5">★</label>
                                    <input type="radio" name="product_quality_rating" value="4" id="quality4" <?= $form_data['product_quality_rating'] == 4 ? 'checked' : '' ?>>
                                    <label for="quality4">★</label>
                                    <input type="radio" name="product_quality_rating" value="3" id="quality3" <?= $form_data['product_quality_rating'] == 3 ? 'checked' : '' ?>>
                                    <label for="quality3">★</label>
                                    <input type="radio" name="product_quality_rating" value="2" id="quality2" <?= $form_data['product_quality_rating'] == 2 ? 'checked' : '' ?>>
                                    <label for="quality2">★</label>
                                    <input type="radio" name="product_quality_rating" value="1" id="quality1" <?= $form_data['product_quality_rating'] == 1 ? 'checked' : '' ?>>
                                    <label for="quality1">★</label>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="form-group">
                                <label for="comments">Tell us about your experience*</label>
                                <textarea id="comments" name="comments" placeholder="Please share details about your order, delivery experience, product quality, or any other feedback you'd like to give us..." required><?= htmlspecialchars($form_data['comments']) ?></textarea>
                            </div>

                            <!-- Recommendation -->
                            <div class="form-group">
                                <label>Would you recommend BakeEase Bakery to friends and family?*</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="would_recommend" value="definitely" <?= $form_data['would_recommend'] === 'definitely' ? 'checked' : '' ?>>
                                        Definitely Yes
                                    </label>
                                    <label>
                                        <input type="radio" name="would_recommend" value="probably" <?= $form_data['would_recommend'] === 'probably' ? 'checked' : '' ?>>
                                        Probably Yes
                                    </label>
                                    <label>
                                        <input type="radio" name="would_recommend" value="not_sure" <?= $form_data['would_recommend'] === 'not_sure' ? 'checked' : '' ?>>
                                        Not Sure
                                    </label>
                                    <label>
                                        <input type="radio" name="would_recommend" value="probably_not" <?= $form_data['would_recommend'] === 'probably_not' ? 'checked' : '' ?>>
                                        Probably Not
                                    </label>
                                    <label>
                                        <input type="radio" name="would_recommend" value="definitely_not" <?= $form_data['would_recommend'] === 'definitely_not' ? 'checked' : '' ?>>
                                        Definitely Not
                                    </label>
                                </div>
                            </div>

                            <!-- Improvement Suggestions -->
                            <div class="form-group">
                                <label for="improvement_suggestions">How can we improve? (Optional)</label>
                                <textarea id="improvement_suggestions" name="improvement_suggestions" placeholder="Any suggestions for how we can make your next experience even better?"><?= htmlspecialchars($form_data['improvement_suggestions']) ?></textarea>
                            </div>

                            <button type="submit" class="submit-btn">Submit Feedback</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <script>
        // Dropdown functionality
        const profileToggle = document.getElementById("profileToggle");
        const profileMenu = document.getElementById("profileMenu");
        const cartToggle = document.getElementById("cartToggle");
        const cartMenu = document.getElementById("cartMenu");
        const navToggle = document.getElementById("navToggle");
        const navMenu = document.getElementById("navMenu");

        profileToggle.addEventListener("click", () => {
            profileMenu.style.display = (profileMenu.style.display === "block") ? "none" : "block";
            cartMenu.style.display = "none";
        });

        cartToggle.addEventListener("click", () => {
            cartMenu.style.display = (cartMenu.style.display === "block") ? "none" : "block";
            profileMenu.style.display = "none";
        });

        navToggle.addEventListener("click", () => {
            navMenu.style.display = (navMenu.style.display === "block") ? "none" : "block";
        });

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
        const starRatings = document.querySelectorAll('.star-rating');
        
        starRatings.forEach(rating => {
            const inputs = rating.querySelectorAll('input');
            const labels = rating.querySelectorAll('label');
            
            inputs.forEach((input, index) => {
                input.addEventListener('change', () => {
                    labels.forEach((label, labelIndex) => {
                        if (labelIndex <= index) {
                            label.style.color = '#FFD700';
                        } else {
                            label.style.color = '#ddd';
                        }
                    });
                });
            });
            
            // Initialize stars based on checked inputs
            const checkedInput = rating.querySelector('input:checked');
            if (checkedInput) {
                const checkedIndex = Array.from(inputs).indexOf(checkedInput);
                labels.forEach((label, labelIndex) => {
                    if (labelIndex <= checkedIndex) {
                        label.style.color = '#FFD700';
                    } else {
                        label.style.color = '#ddd';
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>