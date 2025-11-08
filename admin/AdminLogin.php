<?php
session_start();
require_once "Admin.php";

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: AdminDashboard.php");
    exit;
}

$admin = new Admin();
$error = '';

// Check for error messages from auth_check
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $user = $admin->login($email, $password);

            if ($user) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['admin_session_created'] = time();

                // Check if there's a redirect page
                if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    $redirectPage = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);

                    // Make sure it's a valid admin page
                    if (strpos($redirectPage, '.php') !== false) {
                        header("Location: " . $redirectPage);
                    } else {
                        header("Location: AdminDashboard.php");
                    }
                } else {
                    header("Location: AdminDashboard.php");
                }
                exit;
            } else {
                $error = "The email or password you entered is incorrect. Please check your credentials and try again.";
            }
        } catch (Exception $e) {
            $error = "We're experiencing technical difficulties. Please try again later.";
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Smart Barangay System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }

        .auth-card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .auth-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .switch-login {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .switch-login a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .switch-login a:hover {
            text-decoration: underline;
        }

        .home-link {
            text-align: center;
            margin-top: 15px;
        }

        .home-link a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .home-link a:hover {
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <h2>🔐 Admin Login</h2>
        <p class="auth-subtitle">Administrator Access Only</p>

        <?php if (!empty($error)): ?>
            <div style="background: #ffe6e6; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <strong style="color: #dc3545;">⚠️ Login Failed</strong>
                <p style="color: #721c24; margin: 5px 0 0 0;"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Email Address
                <input type="email" name="email" placeholder="admin@gmail.com" required>
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Login as Admin</button>
        </form>

        <div class="switch-login">
            <p style="color: #666; margin-bottom: 10px;">Not an administrator?</p>
            <a href="../residents/Login.php">👤 Resident Login</a>
        </div>

        <div class="home-link">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>
</body>

</html>