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
    <style> 
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        } 
         
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px; 
        } 
         
        .login-container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); 
            width: 100%; 
            max-width: 450px; 
        } 
         
        .login-container h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 30px; 
        } 
         
        .form-group { 
            margin-bottom: 20px; 
        } 
         
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            color: #555; 
            font-weight: 500; 
        } 
         
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
            transition: border-color 0.3s; 
        } 
         
        .form-group input:focus { 
            outline: none; 
            border-color: #667eea; 
        } 
         
        .remember-me { 
            display: flex; 
            align-items: center; 
            margin-bottom: 20px; 
        } 
         
        .remember-me input { 
            width: auto; 
            margin-right: 8px; 
        } 
         
        .remember-me label { 
            margin: 0; 
            font-weight: normal; 
            color: #666; 
        } 
         
        .error { 
            background: #fee; 
            color: #c33; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border-left: 4px solid #c33; 
        } 
         
        .error ul { 
            margin-left: 20px; 
        } 
         
        .success { 
            background: #efe; 
            color: #3c3; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3c3; 
        } 
         
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: transform 0.2s; 
        } 
         
        .btn:hover { 
            transform: translateY(-2px); 
        } 
         
        .register-link { 
            text-align: center; 
            margin-top: 20px; 
            color: #666; 
        } 
         
        .register-link a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600; 
        } 
         
        .register-link a:hover { 
            text-decoration: underline; 
        } 
    </style> 
</head> 
<body> 
    <div class="login-container"> 
        <h2>Welcome Back</h2> 
         
        <?php if (!empty($errors)): ?> 
            <div class="error"> 
                <ul> 
                    <?php foreach ($errors as $error): ?> 
                        <li><?php echo $error; ?></li> 
                    <?php endforeach; ?> 
                </ul> 
            </div> 
        <?php endif; ?> 
         
        <?php if ($success): ?> 
            <div class="success"><?php echo $success; ?></div> 
        <?php endif; ?> 
         
        <form method="POST" action=""> 
            <div class="form-group"> 
                <label for="email">Email:</label> 
                <input type="email" id="email" name="email"  
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 
(isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''); ?>"  
                       required> 
            </div> 
             
            <div class="form-group"> 
                <label for="password">Password:</label> 
                <input type="password" id="password" name="password" required> 
            </div> 
             
            <div class="remember-me"> 
                <input type="checkbox" id="remember_me" name="remember_me"  
                       <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>> 
                <label for="remember_me">Remember me</label> 
            </div> 
             
            <button type="submit" class="btn">Login</button> 
        </form> 
         
        <div class="register-link"> 
            Don't have an account? <a href="register.php">Register here</a> 
        </div> 
    </div> 
</body> 
</html>