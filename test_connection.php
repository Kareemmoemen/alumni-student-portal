<?php
// Include database configuration
require 'config/database.php';

// Create database object
$database = new Database();

// Try to connect
$conn = $database->getConnection();

// Check connection
if ($conn != null) {
    echo "<h2 style='color: green; text-align: center;'>✔ Database Connected Successfully!</h2>";
    echo "<p style='text-align: center;'>";
    echo "Database: alumni_portal<br>";
    echo "Host: localhost<br>";
    echo "Status: Active</p>";
} else {
    echo "<h2 style='color: red; text-align: center;'>✘ Database Connection Failed!</h2>";
}
?>
