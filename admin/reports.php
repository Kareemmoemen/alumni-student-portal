<?php
// admin/reports.php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body style="background: #f5f7fa;">
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">Alumni Portal <span
                    style="font-size:12px; opacity:0.7;">ADMIN</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="reports.php" class="active">Reports</a></li>
                <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container" style="margin-top: 40px; text-align: center; padding: 50px;">
        <div style="font-size: 60px; margin-bottom: 20px;">✅</div>
        <h2>System Reports</h2>
        <p style="color: #666;">No flagged content or system issues reported at this time.</p>
    </div>
</body>

</html>