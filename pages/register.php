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
         
        .register-container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); 
            width: 100%; 
            max-width: 500px; 
        } 
         
        .register-container h2 { 
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
         
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px; 
            transition: border-color 0.3s; 
        } 
         
        .form-group input:focus, 
        .form-group select:focus { 
            outline: none; 
            border-color: #667eea; 
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
         
        .login-link { 
            text-align: center; 
            margin-top: 20px; 
            color: #666; 
        } 
         
        .login-link a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600; 
        } 
         
        .login-link a:hover { 
            text-decoration: underline; 
        } 
    </style> 
</head> 
<body> 
    <div class="register-container"> 
        <h2>Create Account</h2> 
         
        <?php if (!empty($errors)): ?> 
            <div class="error"> 
                <strong>Please fix the following errors:</strong> 
                <ul> 
                    <?php foreach ($errors as $error): ?> 
                        <li><?php echo $error; ?></li> 
                    <?php endforeach; ?> 
                </ul> 
            </div> 
        <?php endif; ?> 
         
        <?php if (!empty($success)): ?> 
            <div class="success"><?php echo $success; ?></div> 
        <?php endif; ?> 
         
        <form method="POST" action=""> 
            <div class="form-group"> 
                <label for="user_type">I am a:</label> 
                <select name="user_type" id="user_type" required> 
                    <option value="">Select...</option> 
                    <option value="student" <?php echo (isset($_POST['user_type']) && 
$_POST['user_type'] === 'student') ? 'selected' : ''; ?>>Student</option> 
                    <option value="alumni" <?php echo (isset($_POST['user_type']) && 
$_POST['user_type'] === 'alumni') ? 'selected' : ''; ?>>Alumni</option> 
                </select> 
            </div> 
             
            <div class="form-group"> 
                <label for="first_name">First Name:</label> 
                <input type="text" id="first_name" name="first_name"  
                       value="<?php echo isset($_POST['first_name']) ? 
htmlspecialchars($_POST['first_name']) : ''; ?>"  
                       required> 
            </div> 
             
            <div class="form-group"> 
                <label for="last_name">Last Name:</label> 
                <input type="text" id="last_name" name="last_name"  
                       value="<?php echo isset($_POST['last_name']) ? 
htmlspecialchars($_POST['last_name']) : ''; ?>"  
                       required> 
            </div> 
             
            <div class="form-group"> 
                <label for="email">Email:</label> 
                <input type="email" id="email" name="email"  
                       value="<?php echo isset($_POST['email']) ? 
htmlspecialchars($_POST['email']) : ''; ?>"  
                       required> 
            </div> 
             
            <div class="form-group"> 
                <label for="password">Password:</label> 
                <input type="password" id="password" name="password" required> 
                <small style="color: #666; font-size: 12px;">At least 8 characters, 1 
uppercase, 1 lowercase, 1 number</small> 
            </div> 
             
            <div class="form-group"> 
                <label for="confirm_password">Confirm Password:</label> 
                <input type="password" id="confirm_password" 
name="confirm_password" required> 
            </div> 
             
            <button type="submit" class="btn">Register</button> 
        </form> 
         
        <div class="login-link"> 
            Already have an account? <a href="login.php">Login here</a> 
        </div> 
    </div> 
</body> 
</html> 