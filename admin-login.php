<?php
session_start();
require_once 'db_connection.php';

// Initialize variables
$email = '';
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, name, password, role FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            // Authentication successful
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_email'] = $email;
            session_regenerate_id(true);
            
            // Redirect to dashboard
            header('Location: admin-dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="images/png" href="images/logo.png">
    <title>Admin Login - BakeEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
    :root {
        --primary: #e67e22;
        --primary-dark: #d35400;
        --brown: #5a3921;
        --light: #f9f5f0;
        --white: #ffffff;
        --gray: #f0f0f0;
        --dark-gray: #777;
        --border-color: #ddd;
        --error-color: #e74c3c;
        --error-bg: #f8d7da;
        --error-border: #f5c6cb;
        --black: #000000;   
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--light);
        color: #333;
    }

    /* Header Styles */
    .header-bar {
        background-color: var(--white);
        padding: 15px 20px 15px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .logo-left {
        display: flex;
        align-items: center;
        gap: 15px; 
        flex: 1; /* Allow it to grow but not take all space */
    }

    .header-title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
    }

    .logo-left img {
        height: 75px;
        margin-left: -50px;
    }

    #page-heading {
        color: var(--brown);
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }

    /* Auth Container */
    .auth-container {
        max-width: 500px;
        margin: 60px auto;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        background-color: var(--white);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .auth-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    }

    .form-title {
        color: var(--brown);
        text-align: center;
        margin-bottom: 30px;
        font-size: 28px;
        font-weight: 700;
        position: relative;
        padding-bottom: 15px;
    }

    .form-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--primary);
        border-radius: 3px;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--brown);
        font-size: 15px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s, box-shadow 0.3s;
        background-color: var(--gray);
    }

    .form-group input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
        outline: none;
        background-color: var(--white);
    }

    /* Password Wrapper */
    .password-wrapper {
        position: relative;
    }

    .password-wrapper input {
        padding-right: 45px;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--dark-gray);
        font-size: 18px;
        background: none;
        border: none;
        padding: 5px;
        transition: color 0.3s;
    }

    .toggle-password:hover {
        color: var(--primary);
    }

    /* Form Actions */
    .form-actions {
        margin-top: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .btn-primary {
        background-color: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 8px;
        padding: 14px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .forgot-password {
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
        text-align: center;
        transition: color 0.3s;
    }

    .forgot-password:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    /* Error Message */
    .error-message {
        color: var(--error-color);
        background-color: var(--error-bg);
        border: 1px solid var(--error-border);
        border-radius: 5px;
        padding: 12px 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
    }

    /* Footer */
    footer {
        text-align: center;
        padding: 20px;
        background-color: var(--black);
        color: var(--white);
        font-size: 0.9rem;
        border-top: 1px solid var(--border-color);
        position: fixed;
        bottom: 0;
        width: 100%;
        margin-top: 0px;
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .auth-container {
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .header-bar {
            padding: 15px 20px;
        }

        .logo-left {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        #page-heading {
            font-size: 1.5rem;
            margin-right: 0;
        }

        .auth-container {
            margin: 40px 20px;
            padding: 30px 20px;
        }
    }

    @media (max-width: 480px) {
        .auth-container {
            padding: 25px 15px;
        }

        .form-title {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .btn-primary {
            padding: 12px;
            font-size: 15px;
        }
    }
</style>
</head>
<body>
    <header>
        <div class="header-bar">
            <!-- Logo and Title -->
            <div class="logo-left">
                <a href="admin-login.php">
                    <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline">
                </a>
            </div>
            <div class="header-title">
                <h1 id="page-heading">Admin Login</h1>
            </div>
            <div style="flex: 1;"></div>
        </div>
    </header>

    <main>
        <div class="auth-container">
            <h2 class="form-title">Hello Admin!</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="adminLoginForm" action="admin-login.php" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="admin@example.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Enter your password" minlength="8">
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">
                            <i class="far fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 BakeEase Bakery. All rights reserved.</p>
    </footer>

    <script>
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            const isHidden = password.getAttribute('type') === 'password';
            
            password.setAttribute('type', isHidden ? 'text' : 'password');
        
            eyeIcon.classList.toggle('fa-eye-slash', !isHidden);
            eyeIcon.classList.toggle('fa-eye', isHidden);
            
            this.setAttribute('aria-label', 
                isHidden ? 'Hide password' : 'Show password');
        });

        // Add animation to auth container on load
        document.addEventListener('DOMContentLoaded', () => {
            const authContainer = document.querySelector('.auth-container');
            authContainer.style.opacity = '0';
            authContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                authContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                authContainer.style.opacity = '1';
                authContainer.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>