<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// admin/dashboard.php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Check if admin
if (!isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit();
}

// Get Admin Info
$user_id = getUserId();
$database = new Database();
$conn = $database->getConnection();

// Fetch Admin Name
$query = "SELECT first_name, last_name FROM profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin_profile ? $admin_profile['first_name'] : 'Admin';

// --- STATS ---

// Total Users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Students
$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Alumni
$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'alumni'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_alumni = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Mentorship Matches
$query = "SELECT COUNT(*) as total FROM mentorship_matches";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Alumni Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>

<body style="background: #f5f7fa;">

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">Alumni Portal <span
                    style="font-size:12px; opacity:0.7;">ADMIN</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="margin-top: 40px;">

        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?></p>
        </div>

        <!-- Stats -->
        <h2 style="margin-bottom: 20px; color: #2c3e50;">Platform Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #667eea;"><?php echo $total_students; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #764ba2;"><?php echo $total_alumni; ?></div>
                <div class="stat-label">Alumni</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #e67e22;"><?php echo $total_matches; ?></div>
                <div class="stat-label">Mentorships</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 20px; color: #2c3e50;">Management</h2>
        <div class="admin-actions">
            <a href="users.php" style="text-decoration: none; color: inherit;">
                <div class="action-card">
                    <span class="action-icon">👥</span>
                    <h3>Manage Users</h3>
                    <p style="color: #666;">View, search, and manage student and alumni accounts.</p>
                </div>
            </a>
            <a href="reports.php" style="text-decoration: none; color: inherit;">
                <div class="action-card">
                    <span class="action-icon">📊</span>
                    <h3>View Reports</h3>
                    <p style="color: #666;">Check flagged content and system logs.</p>
                </div>
            </a>
        </div>

    </div>

</body>

</html>