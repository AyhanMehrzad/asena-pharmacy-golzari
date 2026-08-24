<?php
require '../includes/db.php';

try {
    // Create Tickets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mode ENUM('ai', 'admin') DEFAULT 'admin',
        status ENUM('open', 'closed', 'resolved') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create Ticket Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_type ENUM('user', 'ai', 'admin') NOT NULL,
        message TEXT,
        image_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
    )");

    echo "Tickets migration completed successfully!";
} catch(PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
