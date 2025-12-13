<?php 
// Include functions 
require_once '../includes/functions.php'; 
// Destroy session 
session_start(); 
session_unset(); 
session_destroy(); 
// Clear remember me cookie 
if (isset($_COOKIE['user_email'])) { 
setcookie('user_email', '', time() - 3600, '/'); 
} 
// Redirect to login 
header("Location: login.php"); 
exit(); 
?> 
