<?php
// Start session if not already started 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in 
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
// Get current user's ID 
function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}
// Get current user's type (student, alumni, admin) 
function getUserType()
{
    return $_SESSION['user_type'] ?? null;
}
// Get current user's email 
function getUserEmail()
{
    return $_SESSION['email'] ?? null;
}

// Check if current user is admin
function isAdmin()
{
    return getUserType() === 'admin';
}

// Require login - redirect if not logged in 
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page";
        header("Location: login.php");
        exit();
    }
}

// Require specific user type 
function requireUserType($type)
{
    requireLogin();
    if (getUserType() !== $type) {
        $_SESSION['error'] = "Access denied. This page is for " . $type . " only.";
        header("Location: dashboard.php");
        exit();
    }
}

// Require admin access 
function requireAdmin()
{
    requireUserType('admin');
}

// Sanitize input data 
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


// Validate email format 
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password strength 
function validatePassword($password)
{
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number 
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[a-z]/", $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number";
    }
    return true;
}

// Generate CSRF token 
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token 
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

// Set success message 
function setSuccess($message)
{
    $_SESSION['success'] = $message;
}

// Set error message 
function setError($message)
{
    $_SESSION['error'] = $message;
}

// Get and clear success message 
function getSuccess()
{
    if (isset($_SESSION['success'])) {
        $message = $_SESSION['success'];
        unset($_SESSION['success']);
        return $message;
    }
    return null;
}

// Get and clear error message 
function getError()
{
    if (isset($_SESSION['error'])) {
        $message = $_SESSION['error'];
        unset($_SESSION['error']);
        return $message;
    }
    return null;
}

// Redirect with message 
function redirect($page, $message = null, $type = 'success')
{
    if ($message) {
        if ($type === 'success') {
            setSuccess($message);
        } else {
            setError($message);
        }
    }
    header("Location: " . $page);
    exit();
}

// Format date 
function formatDate($date)
{
    return date('F j, Y', strtotime($date));
}

// Format datetime 
function formatDateTime($datetime)
{
    return date('F j, Y g:i A', strtotime($datetime));
}

// Time ago function (e.g., "2 hours ago") 
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return "just now";
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return formatDate($datetime);
    }
}

// Upload file function 

?>