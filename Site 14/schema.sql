-- --------------------------------------------------------
-- Enhanced Database Schema for Secure Web Application
-- --------------------------------------------------------

-- Users Table (Core)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Always store hashed passwords
    is_verified BOOLEAN DEFAULT 0, -- Email verification status
    is_locked BOOLEAN DEFAULT 0, -- Account lock for too many failed attempts
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    last_password_change DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Reset Requests
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT 0, -- Prevent token reuse
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email Verification (For account activation)
CREATE TABLE email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login Attempts (Prevent brute-force attacks)
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_successful BOOLEAN DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password History (Enforce password rotation)
CREATE TABLE password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Sessions
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs (Track sensitive actions)
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- e.g., "password_reset", "email_change"
    action_details TEXT,
    performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for Faster Queries
CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_email_verifications_token ON email_verifications(token);
CREATE INDEX idx_sessions_token ON sessions(session_token);

-- Movies Table 
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews Table 
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    review TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



INSERT INTO users (id, username, email, password_hash, is_verified, is_locked, last_login, last_password_change) 
VALUES
(1, 'john_doe', 'john.doe@example.com', 'hashedpassword1', 1, 0, NOW(), NOW()),
(2, 'jane_smith', 'jane.smith@example.com', 'hashedpassword2', 1, 0, NOW(), NOW()),
(3, 'mark_taylor', 'mark.taylor@example.com', 'hashedpassword3', 1, 0, NOW(), NOW()),
(4, 'lucy_brown', 'lucy.brown@example.com', 'hashedpassword4', 1, 0, NOW(), NOW()),
(5, 'alice_jones', 'alice.jones@example.com', 'hashedpassword5', 1, 0, NOW(), NOW());


INSERT INTO movies (id, title, description, image_path) 
VALUES
(1, 'Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a CEO.', 'inception.jpg'),
(2, 'The Dark Knight', 'When the menace known as The Joker emerges from his mysterious past, he wreaks havoc and chaos on the people of Gotham.', 'dark_knight.jpg'),
(3, 'Forrest Gump', 'The presidencies of Kennedy and Johnson, the events of Vietnam, the Watergate scandal and other historical events unfold from the perspective of an Alabama man with an extraordinary all-American life.', 'forrest_gump.jpg'),
(4, 'The Matrix', 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.', 'matrix.jpg'),
(5, 'The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 'shawshank_redemption.jpg');


INSERT INTO reviews (user_id, movie_id, rating, review, created_at) 
VALUES
(1, 1, 5, 'An incredible mind-bending experience. Inception will make you question what is real!', NOW()),
(2, 2, 4, 'The Dark Knight is one of the best superhero films of all time with an unforgettable performance by Heath Ledger.', NOW()),
(3, 3, 5, 'Forrest Gump is a timeless classic that brings joy and tears in equal measure. A true masterpiece!', NOW()),
(4, 4, 5, 'The Matrix changed the sci-fi genre forever. A thrilling ride full of action and thought-provoking themes.', NOW()),
(5, 5, 5, 'The Shawshank Redemption is a beautifully crafted story about friendship, hope, and redemption. Highly recommend.', NOW());

INSERT INTO users (id, username, email, password_hash, is_verified, is_locked, last_login, last_password_change) 
VALUES
(6, 'steve_williams', 'steve.williams@example.com', 'hashedpassword6', 1, 0, NOW(), NOW());

INSERT INTO reviews (user_id, movie_id, rating, review, created_at) 
VALUES
(6, 1, 4, 'Inception is an amazing film, but the concept is a bit hard to grasp at first. Overall, very thought-provoking and visually stunning.', NOW());
