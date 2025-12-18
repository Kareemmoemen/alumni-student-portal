<?php
// pages/edit_profile.php

require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$user_id = getUserId();

// Database connection
$database = new Database();
$conn = $database->getConnection();

// Load current profile
$query = "SELECT * FROM profiles WHERE user_id = :user_id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values
$first_name = $profile['first_name'] ?? '';
$last_name = $profile['last_name'] ?? '';

$major = $profile['major'] ?? '';
$graduation_year = $profile['graduation_year'] ?? '';
// Duplicate removal - empty string since we are deleting specific lines or just relying on context
// Actually, better to just rewrite the blocks correctly.

$current_position = $profile['current_position'] ?? '';
$company = $profile['company'] ?? '';
$location = $profile['location'] ?? '';
$bio = $profile['bio'] ?? '';

$errors = [];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $errors[] = 'Invalid form token. Please try again.';
    }

    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');

    $major = sanitizeInput($_POST['major'] ?? '');
    $graduation_year = sanitizeInput($_POST['graduation_year'] ?? '');
    $current_position = sanitizeInput($_POST['current_position'] ?? '');
    $company = sanitizeInput($_POST['company'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');

    if ($first_name === '')
        $errors[] = 'First name is required.';
    if ($last_name === '')
        $errors[] = 'Last name is required.';

    if ($graduation_year !== '' && !preg_match('/^\d{4}$/', $graduation_year)) {
        $errors[] = 'Graduation year must be a 4-digit number.';
    }



    if (empty($errors)) {

        $query = "UPDATE profiles SET
                    first_name = :first_name,
                    last_name = :last_name,

                    major = :major,
                    graduation_year = :graduation_year,
                    current_position = :current_position,
                    company = :company,
                    location = :location,
                    bio = :bio
                  WHERE user_id = :user_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);

        $stmt->bindParam(':major', $major);

        if ($graduation_year === '') {
            $stmt->bindValue(':graduation_year', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':graduation_year', (int) $graduation_year, PDO::PARAM_INT);
        }

        $stmt->bindParam(':current_position', $current_position);
        $stmt->bindParam(':company', $company);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':bio', $bio);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            setSuccess('Profile updated successfully!');
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = 'Failed to update profile, please try again.';
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Alumni Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Main Styling -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Page Specific CSS -->
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3::before {
            content: '';
            width: 4px;
            height: 24px;
            background: #667eea;
            border-radius: 2px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }



        @media(max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <!-- 🔥 Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- 🔥 Navbar -->
    <?php
    $current_page = 'profile.php';
    require_once '../includes/navbar.php';
    ?>

    <div class="mobile-overlay"></div>

    <!-- 🔥 Page Content -->
    <div class="edit-profile-container">
        <div class="form-card">

            <div class="form-header">
                <h2>Edit Profile</h2>
                <p>Update your academic and professional information.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit_profile.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">



                <!-- Basic Info -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Academic Section -->
                <div class="form-section">
                    <h3>Academic & Professional</h3>
                    <div class="form-group">
                        <label for="major">Major</label>
                        <input type="text" id="major" name="major" class="form-control"
                            value="<?php echo htmlspecialchars($major); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="graduation_year">Graduation Year</label>
                            <input type="text" id="graduation_year" name="graduation_year" class="form-control"
                                placeholder="e.g. 2025" value="<?php echo htmlspecialchars($graduation_year); ?>">
                        </div>
                        <div class="form-group">
                            <label for="current_position">Current Role</label>
                            <input type="text" id="current_position" name="current_position" class="form-control"
                                value="<?php echo htmlspecialchars($current_position); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" id="company" name="company" class="form-control"
                            value="<?php echo htmlspecialchars($company); ?>">
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control"
                            placeholder="e.g. New York, NY" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                </div>

                <!-- About Section -->
                <div class="form-section">
                    <h3>About</h3>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4"
                            placeholder="Tell others about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <button type="submit" class="btn btn-primary btn-block">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Load Main JS -->
    <script src="../assets/js/main.js"></script>
</body>

</html>