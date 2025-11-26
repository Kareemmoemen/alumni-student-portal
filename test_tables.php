<?php 
require_once 'config/database.php'; 
$database = new Database(); 
$conn = $database->getConnection(); 
// Get list of tables 
$query = "SHOW TABLES"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
echo "<h2>Database Tables:</h2><ul>"; 
while ($row = $stmt->fetch(PDO::FETCH_NUM)) { 
echo "<li>" . $row[0] . "</li>"; 
} 
echo "</ul>"; 
// Count records in users table 
$query = "SELECT COUNT(*) as total FROM users"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$result = $stmt->fetch(PDO::FETCH_ASSOC); 
echo "<p>Total users: " . $result['total'] . "</p>"; 
?> 