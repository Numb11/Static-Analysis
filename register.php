<?php
session_start();
require '../includes/config.php';

// Initialise variables for form data and error message
$username = $email = '';
$error = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitise input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    } else {
        try {
            // Check if username or email already exists
            $check_sql = "SELECT * FROM users WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($check_sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email
            ]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Insert new user - Storing password
                    $insert_sql = "INSERT INTO users (username, email, password_hash, last_password_change) VALUES (:username, :email, :password, NOW())";
                    $stmt = $pdo->prepare($insert_sql);
                    $result = $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $password // Storing the plain password without hashing
                    ]);
                    
                    if ($result) {
                        $user_id = $pdo->lastInsertId();
                        
                        // Store password in history - also plain text
                        $history_sql = "INSERT INTO password_history (user_id, password_hash) VALUES (:user_id, :password)";
                        $stmt = $pdo->prepare($history_sql);
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':password' => $password // Storing plain password in history
                        ]);
                        
                        // Create email verification token
                        $token = bin2hex(random_bytes(32));
                        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        $verify_sql = "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                        $stmt = $pdo->prepare($verify_sql);
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':token' => $token,
                            ':expires_at' => $expires_at
                        ]);
                        
                        // Log the registration in audit_logs
                        $audit_sql = "INSERT INTO audit_logs (user_id, action_type, action_details, ip_address) VALUES (:user_id, :action_type, :action_details, :ip_address)";
                        $stmt = $pdo->prepare($audit_sql);
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':action_type' => 'user_registration',
                            ':action_details' => 'User registered successfully',
                            ':ip_address' => $_SERVER['REMOTE_ADDR']
                        ]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        $_SESSION['register_success'] = "Registration successful! Please check your email to verify your account.";
                        header("Location: login.php");
                        exit;
                    } else {
                        throw new Exception("Registration failed.");
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Movie Review System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color:rgb(199, 199, 199);
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.99);
            width: 350px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 95%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background:rgb(87, 192, 111);
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .login-link {
            margin-top: 15px;
            font-size: 14px;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">
                    Password must be at least 8 characters long and include uppercase, lowercase, and numbers.
                </div>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            <p>Already a user? <a href="login.php">Click here</a> to login.</p>
        </div>
    </div>
</body>
</html>
