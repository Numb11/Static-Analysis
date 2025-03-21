<?php
// Start the session to store user info
session_start();

// Include the database connection configuration
require '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // No sanitization of input
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Vulnerable query that directly concatenates user input
    $query = "SELECT id, username, password_hash FROM users WHERE username = '$username' AND password_hash = '$password'";
    
    // Execute the vulnerable query
    $result = $pdo->query($query);
    
    // Check if a user exists
    if ($result && $result->rowCount() > 0) {
        // Fetch the result (user record)
        $user = $result->fetch();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to the review page or dashboard
        header("Location: review.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(199, 199, 199);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgb(0, 0, 0);
            border-radius: 8px;
            width: 350px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .login-container input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-container button {
            width: 86%;
            padding: 10px;
            background:rgb(84, 206, 112);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-container button:hover {
            background: #218838;
        }
        .forgot-password {
            display: block;
            margin-top: 10px;
            color: #007BFF;
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
        <a href="register.php" class="forgot-password">Create an account? Click here</a>
    </div>
</body>
</html>
