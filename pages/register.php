<?php 
// Include required files 
require_once '../config/database.php'; 
require_once '../includes/functions.php'; 
 
// Initialize variables 
$errors = []; 
$success = ''; 
 
// Check if form is submitted 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
     
    // Get form data 
    $email = sanitizeInput($_POST['email']); 
    $password = $_POST['password']; 
    $confirm_password = $_POST['confirm_password']; 
    $user_type = sanitizeInput($_POST['user_type']); 
    $first_name = sanitizeInput($_POST['first_name']); 
    $last_name = sanitizeInput($_POST['last_name']); 
     
    // Validate email 
    if (empty($email)) { 
        $errors[] = "Email is required"; 
    } elseif (!validateEmail($email)) { 
        $errors[] = "Invalid email format"; 
    } 
     
    // Validate password 
    if (empty($password)) { 
        $errors[] = "Password is required"; 
    } else { 
        $passwordValidation = validatePassword($password); 
        if ($passwordValidation !== true) { 
            $errors[] = $passwordValidation; 
        } 
    } 
     
    // Validate confirm password 
    if ($password !== $confirm_password) { 
        $errors[] = "Passwords do not match"; 
    } 
     
    // Validate user type 
    if (empty($user_type) || !in_array($user_type, ['student', 'alumni'])) { 
        $errors[] = "Please select a valid user type"; 
    } 
     
    // Validate names 
    if (empty($first_name)) { 
        $errors[] = "First name is required"; 
    } 
    if (empty($last_name)) { 
        $errors[] = "Last name is required"; 
    } 
     
    // If no errors, proceed with registration 
    if (empty($errors)) { 
        try { 
            // Create database connection 
            $database = new Database(); 
            $conn = $database->getConnection(); 
             
            // Check if email already exists 
            $query = "SELECT user_id FROM users WHERE email = :email"; 
            $stmt = $conn->prepare($query); 
            $stmt->bindParam(':email', $email); 
            $stmt->execute(); 
             
            if ($stmt->rowCount() > 0) { 
                $errors[] = "Email already exists. Please use a different email or login."; 
            } else { 
                // Hash password 
                $hashed_password = password_hash($password, 
PASSWORD_DEFAULT); 
                 
                // Begin transaction 
                $conn->beginTransaction(); 
                 
                // Insert into users table 
                $query = "INSERT INTO users (email, password, user_type, is_verified, 
status)  
                         VALUES (:email, :password, :user_type, FALSE, 'active')"; 
                $stmt = $conn->prepare($query); 
                $stmt->bindParam(':email', $email); 
                $stmt->bindParam(':password', $hashed_password); 
                $stmt->bindParam(':user_type', $user_type); 
                $stmt->execute(); 
                 
                // Get the inserted user_id 
                $user_id = $conn->lastInsertId(); 
                 
                // Insert into profiles table 
                $query = "INSERT INTO profiles (user_id, first_name, last_name)  
                         VALUES (:user_id, :first_name, :last_name)"; 
                $stmt = $conn->prepare($query); 
                $stmt->bindParam(':user_id', $user_id); 
                $stmt->bindParam(':first_name', $first_name); 
                $stmt->bindParam(':last_name', $last_name); 
                $stmt->execute(); 
                 
                // Commit transaction 
                $conn->commit(); 
                 
                // Set success message and redirect 
                $_SESSION['success'] = "Registration successful! Please login."; 
                header("Location: login.php"); 
                exit(); 
            } 
             
        } catch(PDOException $e) { 
            // Rollback transaction on error 
            if ($conn->inTransaction()) { 
                $conn->rollBack(); 
            } 
            $errors[] = "Registration failed: " . $e->getMessage(); 
        } 
    } 
} 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Register - Alumni Portal</title> 
    <link rel="stylesheet" href="../assets/css/style.css">
</head> 
<body> 
    <body class="gradient-bg">
    <div class="container">
        <div class="card auth-card">
            <div class="card-header">
                <h1>Create Account</h1>
            </div>

            <div class="card-body">
                <?php
                // show validation / success messages if you have them
                if (isset($error)) {
                    echo '<p class="alert alert-error">' . htmlspecialchars($error) . '</p>';
                }
                if (isset($success)) {
                    echo '<p class="alert alert-success">' . htmlspecialchars($success) . '</p>';
                }
                ?>

                <form method="post" action="register.php">
                    <div class="form-group">
                        <label for="user_type">I am a:</label>
                        <select id="user_type" name="user_type" class="form-control" required>
                            <option value="">Select...</option>
                            <option value="student">Student</option>
                            <option value="alumni">Alumni</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name"
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name"
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email"
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password"
                               class="form-control" required>
                        <small class="form-text">
                            At least 8 characters, 1 uppercase, 1 lowercase, 1 number
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Register
                    </button>

                    <p class="form-text">
                        Already have an account?
                        <a href="login.php">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>

</body> 
</html> 