<?php
session_start();
require_once '../includes/config.php';


$error = '';
$success = '';

if (!isset($_GET['token'])) {
    $error = "No token provided.";
} else {
    $token = $_GET['token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($token)) {
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch();

    if (!$tokenData) {
        $error = "Invalid token.";
    } elseif (strtotime($tokenData['expires_at']) < time()) {
        $error = "Token has expired. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_GET['token'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();

        if (!$tokenData) {
            $error = "Invalid token.";
        } elseif (strtotime($tokenData['expires_at']) < time()) {
            $error = "Token has expired. Please request a new one.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $tokenData['user_id']]);

            // Clean up used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $success = "Password reset successfully! You can now <a href='login.php'>login</a>.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .auth-box {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        .auth-box h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .auth-box input {
            width: 100%;
            padding: 10px;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .auth-box button {
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        .auth-box button:hover {
            background: #218838;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .auth-links {
            margin-top: 1rem;
        }
        .auth-links a {
            color: #007bff;
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
            <form method="POST">
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>