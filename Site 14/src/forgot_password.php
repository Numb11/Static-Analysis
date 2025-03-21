<?php
session_start();
require_once '../includes/config.php';  // Adjust path as needed

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../includes/src/Exception.php';
require '../includes/src/PHPMailer.php';
require '../includes/src/SMTP.php';

// Set rate limit parameters
$rateLimitSeconds = 60;   // Time window: 60 seconds
$maxRequests = 3;         // Maximum allowed requests per window

// Initialize the rate limit tracker if not set
if (!isset($_SESSION['forgot_request_timestamps'])) {
    $_SESSION['forgot_request_timestamps'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = time();
    
    // Remove timestamps older than the rate limit window
    $_SESSION['forgot_request_timestamps'] = array_filter(
        $_SESSION['forgot_request_timestamps'],
        function($timestamp) use ($now, $rateLimitSeconds) {
            return ($now - $timestamp) < $rateLimitSeconds;
        }
    );
    
    // Check if the number of requests in the last minute exceeds or equals the max allowed
    if (count($_SESSION['forgot_request_timestamps']) >= $maxRequests) {
        // Instead of showing an error, display a success message with the rate limit notice
        $success = "Too many tries. Please wait a moment before trying again.";
    } else {
        // Record the current request timestamp
        $_SESSION['forgot_request_timestamps'][] = $now;
    
        $email = $_POST['email'] ?? '';
    
        // Basic validation
        if (empty($email)) {
            $error = "Please enter your email.";
        } else {
            // Check if email exists in the users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
    
            if ($user) {
                // Generate a secure random token
                $token = bin2hex(random_bytes(16));
                // Set expiration for e.g. 1 hour from now
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Insert or update the token in password_resets table
                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (:user_id, :token, :expires)
                    ON DUPLICATE KEY UPDATE token = :token_update, expires_at = :expires_update
                ");
                $stmt->execute([
                    ':user_id'        => $user['id'],
                    ':token'          => $token,
                    ':expires'        => $expires,
                    ':token_update'   => $token,
                    ':expires_update' => $expires
                ]);
    
                // Construct the reset URL (adjust domain/path)
                $resetLink = "http://localhost/your-project-folder/src/reset_password.php?token=$token";
    
                // Send the reset link to the user's email using PHPMailer
                $mail = new PHPMailer(true);
    
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'elqayserelqayser@gmail.com';
                    $mail->Password   = 'osum psvt jbkp pbca';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
    
                    // Recipients
                    $mail->setFrom('no-reply@yourdomain.com', 'Web Secure Development');
                    $mail->addAddress($email);
    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body    = "Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>";
                    $mail->AltBody = "Click the link below to reset your password:\n$resetLink";
    
                    $mail->send();
                    $success = "If that email is registered, a reset link has been sent.";
                } catch (Exception $e) {
                    $error = "Email could not be sent. Error: {$mail->ErrorInfo}";
                }
            } else {
                // For security, do NOT reveal if email doesn't exist
                $success = "If that email is registered, a reset link has been sent.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body { 
            background: #f4f4f4;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        
        .auth-box {
            background: white;
            width: 350px;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin: 0 0 1.5rem 0;
            text-align: center;
        }
        
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        input[type="email"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background: #28a745;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        button:hover {
            background: #218838;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 1rem;
        }
        
        .auth-links a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>Forgot Password</h2>
        
        <?php if (!empty($error)) : ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Instructions</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Return to Login</a>
        </div>
    </div>
</body>
</html>

