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



                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit();
                    }

                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }

        } catch (PDOException $e) {
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
    <link rel="stylesheet" href="../assets/css/animations.css">
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">Alumni Portal</a>
            <button class="mobile-menu-toggle">☰</button>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>
    <div class="mobile-overlay"></div>

    <div class="container" style="margin-top: 40px;">
        <div class="card auth-card" style="max-width: 500px; margin: 0 auto;">
            <div class="card-header">
                <h1>Welcome Back</h1>
            </div>

            <div class="card-body">
                <?php
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        echo '<p class="alert alert-error">' . htmlspecialchars($error) . '</p>';
                    }
                }
                if ($success) {
                    echo '<p class="alert alert-success">' . htmlspecialchars($success) . '</p>';
                }
                ?>

                <form method="post" action="login.php">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>



                    <button type="submit" class="btn btn-primary btn-block">
                        Login
                    </button>

                    <p class="form-text text-center mt-3">
                        Don’t have an account?
                        <a href="register.php">Register here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Include JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
</body>

</html>