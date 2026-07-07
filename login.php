<?php
// Manager Login Portal
// Almighty Driving School Management System
require_once 'db.php';
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['manager_id'])) {
    header("Location: index.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM manager WHERE username = ?");
            $stmt->execute([$username]);
            $manager = $stmt->fetch();

            if ($manager && password_verify($password, $manager['password'])) {
                // Success! Set session variables
                $_SESSION['manager_id'] = $manager['manager_id'];
                $_SESSION['manager_name'] = $manager['manager_name'];
                $_SESSION['manager_role'] = $manager['manager_role'];
                
                header("Location: index.php");
                exit;
            } else {
                $error_message = "Invalid username or password. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Almighty Driving School</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.4s ease;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
            margin-bottom: 16px;
        }
        .login-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .login-subtitle {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 6px;
        }
        .login-input-group {
            margin-bottom: 24px;
            position: relative;
        }
        .login-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
        }
        .login-input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition);
        }
        .login-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.2);
            transition: var(--transition);
            margin-top: 8px;
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.35);
        }
        .login-alert {
            background-color: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-icon">
                <i class="fa-solid fa-car-side"></i>
            </div>
            <h2 class="login-title">Almighty Driving</h2>
            <p class="login-subtitle">Database Portal Authentication</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="login-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="login-input-group">
                <i class="fa-solid fa-user login-input-icon"></i>
                <input type="text" name="username" class="login-input" placeholder="Username" required autofocus>
            </div>

            <div class="login-input-group">
                <i class="fa-solid fa-lock login-input-icon"></i>
                <input type="password" name="password" class="login-input" placeholder="Password" required>
            </div>

            <button type="submit" class="login-btn">Authenticate</button>
        </form>
    </div>
</body>
</html>
