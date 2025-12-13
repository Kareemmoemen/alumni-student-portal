<?php 
// Include database class 
require_once 'config/database.php'; 
// Create database object 
$database = new Database(); 
$conn = $database->getConnection(); 
// Check if connection is successful 
if($conn) { 
echo "<h2>✅ Database Connection Successful!</h2>"; 
// Test query - count tables 
$query = "SHOW TABLES"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
echo "<h3>Tables in database:</h3>"; 
echo "<ul>"; 
while ($row = $stmt->fetch(PDO::FETCH_NUM)) { 
echo "<li>" . $row[0] . "</li>"; 
} 
echo "</ul>"; 
// Count users 
$query = "SELECT COUNT(*) as total FROM users"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$result = $stmt->fetch(PDO::FETCH_ASSOC); 
echo "<p><strong>Total users in database:</strong> " . $result['total'] . 
"</p>"; 
} else { 
echo "<h2>❌ Database Connection Failed!</h2>"; 
} 
?>