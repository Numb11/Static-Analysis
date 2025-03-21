<?php
session_start();
require '../includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a random CSRF token
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Reviews</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px;
            background-color: #28a745;
            color: white;
            border-radius: 8px;
        }
        .review-container {
            background: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 20px;
        }
        .review-container h2 {
            text-align: center;
        }
        .review-container label, .review-container select, .review-container textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .review-container button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .review-container button:hover {
            background-color: #218838;
        }
        .review-item {
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .review-item .rating {
            color: #f39c12;
        }
        .review-item .timestamp {
            font-size: 0.85em;
            color: #999;
        }
        .review-item .review-text {
            margin-top: 10px;
        }
    </style>
</head>

<!-- Review Form -->
<body>
<div class="review-container">
    <h2>Submit Your Review</h2>
    <form action="submit_review.php" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label for="movie_id">Select Movie:</label>
        <select name="movie_id" id="movie_id" required>
            <?php
            // Fetch all movies to populate the select dropdown
            $stmt = $pdo->prepare("SELECT id, title FROM movies");
            $stmt->execute();
            $movies = $stmt->fetchAll();

            foreach ($movies as $row) {
                echo "<option value=\"" . $row['id'] . "\">" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</option>";
            }
            ?>
        </select>

        <label for="rating">Rating (1-5):</label>
        <select name="rating" id="rating" required>
            <option value="1">1 ⭐</option>
            <option value="2">2 ⭐⭐</option>
            <option value="3">3 ⭐⭐⭐</option>
            <option value="4">4 ⭐⭐⭐⭐</option>
            <option value="5">5 ⭐⭐⭐⭐⭐</option>
        </select>

        <label for="review">Your Review:</label>
        <textarea name="review" id="review" required></textarea>

        <button type="submit">Submit Review</button>
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <a href="logout.php">
            <button style="padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 5px;">
                Logout
            </button>
        </a>
    </div>
</div>

<!-- Display Reviews -->
<div class="review-container">
    <h2>Latest Reviews</h2>

    <?php
    try {
        // Fetch reviews from the database
        $stmt = $pdo->prepare("
            SELECT movies.title AS movie_title, users.username, 
                   reviews.rating, reviews.review, reviews.created_at
            FROM reviews
            JOIN users ON reviews.user_id = users.id
            JOIN movies ON reviews.movie_id = movies.id
            ORDER BY reviews.created_at DESC
        ");
        $stmt->execute();
        $reviews = $stmt->fetchAll();

        if ($reviews) {
            foreach ($reviews as $row) {
                echo "<div class='review-item'>";
                echo "<h3>" . htmlspecialchars($row['movie_title'], ENT_QUOTES, 'UTF-8') . "</h3>";
                echo "<strong>" . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . "</strong> ";
                echo "<span class='rating'>" . intval($row['rating']) . " ⭐</span><br>";
                echo "<p>" . nl2br(htmlspecialchars($row['review'], ENT_QUOTES, 'UTF-8')) . "</p>";
                echo "<div class='timestamp'>Posted on " . htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') . "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No reviews available yet.</p>";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo "<p>Error loading reviews.</p>";
    }
    ?>
</div>

</body>
</html>
