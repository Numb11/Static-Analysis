<?php
session_start();
require '../includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : null;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $review = isset($_POST['review']) ? trim($_POST['review']) : '';

    // Validate CSRF token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Validate input
    if (!$movie_id || $rating < 1 || $rating > 5 || empty($review) || strlen($review) > 500) {
        die("Invalid input.");
    }

    // Check if movie ID is valid
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    if ($stmt->fetchColumn() == 0) {
        die("Invalid movie ID.");
    }

    // Prevent duplicate reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$user_id, $movie_id]);
    if ($stmt->fetchColumn() > 0) {
        die("You have already submitted a review for this movie.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, review) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $movie_id, $rating, $review]);

        header("Location: review.php");
        exit();
    }   catch (PDOException $e) {
        error_log($e->getMessage());
        die("An error occurred. Please try again.");
    }
}
?>
