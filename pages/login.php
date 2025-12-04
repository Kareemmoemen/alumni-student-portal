<?php 
// Include required files 
require_once '../config/database.php'; 
require_once '../includes/functions.php'; 
 
// If already logged in, redirect to dashboard 
if (isLoggedIn()) { 
    header("Location: dashboard.php"); 
    exit(); 
} 
 
// Initialize variables 
$errors = []; 
 
// Check if form is submitted 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
     
    // Get form data 
    $email = sanitizeInput($_POST['email']); 
    $password = $_POST['password']; 
    $remember_me = isset($_POST['remember_me']); 
     
    // Validate input 
    if (empty($email)) { 
        $errors[] = "Email is required"; 
    } 
     
    if (empty($password)) { 
        $errors[] = "Password is required"; 
    } 
     
    // If no errors, proceed with login 
    if (empty($errors)) { 
        try { 
            // Create database connection 
            $database = new Database(); 
            $conn = $database->getConnection(); 
             
            // Query to get user by email 
            $query = "SELECT user_id, email, password, user_type, status  
                     FROM users  
                     WHERE email = :email"; 
            $stmt = $conn->prepare($query); 
            $stmt->bindParam(':email', $email); 
            $stmt->execute(); 
             
            // Check if user exists 
            if ($stmt->rowCount() > 0) { 
                $user = $stmt->fetch(PDO::FETCH_ASSOC); 
                 
                // Verify password 
                if (password_verify($password, $user['password'])) { 
                     
                    // Check if account is active 
                    if ($user['status'] !== 'active') { 
                        $errors[] = "Your account has been deactivated. Please contact admin."; 
                    } else { 
                        // Login successful - create session 
                        $_SESSION['user_id'] = $user['user_id']; 
                        $_SESSION['email'] = $user['email']; 
                        $_SESSION['user_type'] = $user['user_type']; 
                         
                        // Handle "Remember Me" 
                        if ($remember_me) { 
                            // Set cookie for 30 days 
                            setcookie('user_email', $email, time() + (86400 * 30), "/"); 
                        } 
                         
                        // Redirect based on user type 
                        if ($user['user_type'] === 'admin') { 
                            header("Location: ../admin/dashboard.php"); 
                        } else { 
                            header("Location: dashboard.php"); 
                        } 
                        exit(); 
                    } 
                     
                } else { 
                    $errors[] = "Invalid email or password"; 
                } 
            } else { 
                $errors[] = "Invalid email or password"; 
            } 
             
        } catch(PDOException $e) { 
            $errors[] = "Login failed: " . $e->getMessage(); 
        } 
    } 
} 
 
// Get success message from session (if redirected from registration) 
$success = getSuccess(); 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Login - Alumni Portal</title> 
   <link rel="stylesheet" href="../assets/css/style.css">

</head> 
<body> 
    <body class="gradient-bg">
    <div class="container">
        <div class="card auth-card">
            <div class="card-header">
                <h1>Welcome Back</h1>
            </div>

            <div class="card-body">
                <?php
                // keep any PHP messages you already have, e.g. errors
                if (isset($error)) {
                    echo '<p class="alert alert-error">' . htmlspecialchars($error) . '</p>';
                }
                ?>

                <form method="post" action="login.php">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember_me">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Login
                    </button>

                    <p class="form-text">
                        Don’t have an account?
                        <a href="register.php">Register here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>

</body> 
</html>