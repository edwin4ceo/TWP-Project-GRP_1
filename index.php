<?php
session_start();
include("db_connection.php");

// Fetch popular products (limiting to 3 as shown in the original HTML)
$query = "SELECT id, name, description, price, image FROM products WHERE category = 'Cake' LIMIT 3";
$result = mysqli_query($conn, $query);
$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BakeEase Bakery</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="icon" href="images/logo.png" type="image/png" />
    <style>
        /* Inline styles for quick implementation */
        <style>
    .intro {
        position: relative;
        height: 100vh; /* Full viewport height */
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        background: linear-gradient(45deg, #ff6f61, #6b48ff, #ffcc00); /* Modern gradient */
        background-size: 200% 200%;
        animation: gradientAnimation 10s ease infinite;
        background color: #f3f4f6; /* Light background for contrast */
    }

    .intro-overlay {
        position: relative;
        z-index: 2;
        padding: 20px;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5); /* Subtle shadow for readability */
    }

    .intro-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        line-height: 1.2;
        animation: fadeInUp 1s ease-out;
    }

    .intro-subtitle {
        color: #000;
        font-size: 1.25rem;
        max-width: 600px;
        margin: 0 auto 2rem;
        opacity: 0.9;
        animation: fadeInUp 1.5s ease-out;
    }

    .intro-cta {
        display: inline-block;
        padding: 15px 30px;
        background:rgb(217, 185, 6); /* Orange accent */
        color: white;
        text-decoration: none;
        border-radius: 50px; /* Rounded button for modern look */
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(217, 119, 6, 0.3);
        position: relative;
        overflow: hidden;
    }

    .intro-cta:hover {
        transform: translateY(-3px);
        background: #b45309;
        box-shadow: 0 8px 20px rgba(217, 119, 6, 0.5);
    }

    .intro-cta::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    .intro-cta:hover::after {
        width: 300px;
        height: 300px;
    }

    /* Animations */
    @keyframes gradientAnimation {
        0% { background-position: 0% 0%; }
        50% { background-position: 100% 100%; }
        100% { background-position: 0% 0%; }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .intro-title {
            font-size: 2.5rem;
        }
        .intro-subtitle {
            font-size: 1rem;
        }
        .intro-cta {
            padding: 12px 25px;
        }
    }

    @media (max-width: 480px) {
        .intro-title {
            font-size: 2rem;
        }
        .intro-subtitle {
            font-size: 0.9rem;
        }
        .intro-cta {
            padding: 10px 20px;
        }
    }

        .popular {
            padding: 40px 20px;
            background: #f9f9f9;
        }
        .carousel {
            position: relative;
            overflow: hidden;
        }
        .carousel-inner {
            display: flex;
            transition: transform 0.5s ease;
        }
        .product-card {
            flex: 0 0 100%;
            max-width: 100%;
            box-sizing: border-box;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 15px;
            margin-right: 20px;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 10;
        }
        .carousel-btn.prev {
            left: 10px;
        }
        .carousel-btn.next {
            right: 10px;
        }

        .why-choose {
            padding: 40px 20px;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            text-align: center;
        }
        .why-choose p {
            font-size: 1.1rem;
            color: #2c2c2c;
            transition: transform 0.3s ease;
        }
        .why-choose p:hover {
            transform: scale(1.1);
            color: #d97706;
        }

        footer {
            background: #2c2c2c;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .footer-content {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .social-icons a {
            color: white;
            margin: 0 10px;
            font-size: 1.5rem;
            text-decoration: none;
        }
        .newsletter input {
            padding: 8px;
            border: none;
            border-radius: 4px;
        }
        .newsletter button {
            padding: 8px 15px;
            background: #d97706;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-bar">
            <!-- Logo + Search Centered -->
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
            <!-- Right Side Icons: Menu, Cart, Profile -->
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
                            <a href="profile.php?logout=1">Logout</a>
                        <?php else: ?>
                            <a href="register.php">Sign Up</a>
                            <a href="login.php">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main>
        <section class="intro">
    <div class="intro-overlay">
        <h1 class="intro-title">Freshly Baked Goodness, Just a Click Away!</h1>
        <p class="intro-subtitle">At BakeEase, we deliver delicious breads, cakes, and pastries straight to your doorstep. Browse our menu and order your favorites today!</p>
        <a href="products.php" class="intro-cta">Explore Our Menu</a>
    </div>
</section>

        <section class="popular">
            <h2>Popular Picks</h2>
            <div class="carousel">
                <button class="carousel-btn prev" onclick="moveCarousel(-1)">&#10094;</button>
                <div class="carousel-inner" id="carouselInner">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="price">RM <?php echo number_format($product['price'], 2); ?></p>
                            <a href="product-detail.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="view-details">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-btn next" onclick="moveCarousel(1)">&#10095;</button>
            </div>
        </section>

        <section class="why-choose">
            <h2>Why Choose BakeEase?</h2>
            <p>✅ Baked fresh daily</p>
            <p>✅ Premium ingredients</p>
            <p>✅ Fast delivery in your area</p>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="social-icons">
                <a href="#" target="_blank">Facebook</a>
                <a href="#" target="_blank">Instagram</a>
                <a href="#" target="_blank">Twitter</a>
            </div>
            <div class="newsletter">
                <input type="email" placeholder="Subscribe for updates" />
                <button type="submit">Subscribe</button>
            </div>
        </div>
        <p>© 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <!-- JavaScript for dropdowns and carousel -->
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

        // Carousel Logic
        let currentSlide = 0;
        const carouselInner = document.getElementById("carouselInner");
        const totalSlides = <?php echo count($products); ?>;

        function moveCarousel(direction) {
            currentSlide += direction;
            if (currentSlide < 0) currentSlide = totalSlides - 1;
            if (currentSlide >= totalSlides) currentSlide = 0;
            updateCarousel();
        }

        function updateCarousel() {
            const offset = -currentSlide * 100;
            carouselInner.style.transform = `translateX(${offset}%)`;
        }

        // Auto-slide every 3 seconds
        setInterval(() => moveCarousel(1), 3000);
    </script>
</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>