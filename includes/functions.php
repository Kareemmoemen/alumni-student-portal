 <?php
 session_start();
 function isLoggedIn() {
 return isset($_SESSION['user_id']);
 }
 function getUserType() {
 return $_SESSION['user_type'] ?? null;
 }
 function requireLogin() {
 if (!isLoggedIn()) {
 header("Location: login.php");
 exit();
 }
 }
 ?>