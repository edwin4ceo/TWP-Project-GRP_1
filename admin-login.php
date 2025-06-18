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
            --primary-color: #e67e22;
            --primary-hover: #d35400;
            --text-color: #5a3921;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-gray);
            color: #333;
        }
        
        .auth-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        
        .auth-container:hover {
            transform: translateY(-5px);
        }
        
        .form-group {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .form-group label {
            display: block;
            width: 120px;
            margin-bottom: 0;
            font-weight: 600;
            color: var(--text-color);
            text-align: right;
            margin-right: 15px;
        }
        
        .form-group input {
            flex: 1;
            padding: 12px !important;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
            outline: none;
        }
        
        .form-actions {
            margin-top: 35px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .form-actions .btn-primary {
            margin-bottom: 15px;
            width: 100%;
            padding: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .form-actions .forgot-password {
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .form-actions .forgot-password:hover {
            text-decoration: underline;
            color: var(--primary-hover);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .form-title {
            color: var(--text-color);
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
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        /* Password toggle styles */
        .password-wrapper {
            position: relative;
            flex: 1;
        }
        
        .password-wrapper input {
            width: 92%;
            padding-right: 6px;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-color);
            font-size: 18px;
            background: none;
            border: none;
            padding: 5px;
            transition: color 0.3s;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
            background-color: rgba(230, 126, 34, 0.1);
        }
        
        #page-heading {
            font-size: 2rem;
            margin-right: 43%;
        }
        
        /* Error message */
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .auth-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-group label {
                text-align: left;
                margin-bottom: 8px;
                width: auto;
                margin-right: 0;
            }
            
            .password-wrapper {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-bar">
            <!-- Logo and Title -->
            <div class="logo-left">
                <a href="index.php">
                    <img src="images/logo.png" alt="BakeEase Logo" class="logo-inline">
                </a>
                <h1 id="page-heading">Admin Login</h1>
            </div>
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