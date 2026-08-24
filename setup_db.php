<?php
require 'includes/db.php';

try {
    $sql = file_get_contents('petshop_db.sql');
    $pdo->exec($sql);
    echo "Database setup completed successfully using petshop_db.sql!";
} catch(PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
