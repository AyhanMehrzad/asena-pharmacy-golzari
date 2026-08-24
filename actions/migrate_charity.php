<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Campaigns Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            goal_amount INT NOT NULL,
            current_amount INT DEFAULT 0,
            image_url VARCHAR(500) DEFAULT NULL,
            status ENUM('active', 'completed', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Donations Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS donations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            donor_name VARCHAR(255) NULL,
            campaign_id INT NULL,
            amount INT NOT NULL,
            status ENUM('pending', 'successful', 'failed') DEFAULT 'pending',
            payment_reference VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Charity migration completed successfully!\n";
} catch(PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
