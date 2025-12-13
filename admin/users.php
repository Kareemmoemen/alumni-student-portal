<?php
// admin/users.php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
if (!isAdmin()) {
    header('Location: ../pages/dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Fetch all users with profile info
$query = "SELECT u.user_id, u.email, u.user_type, u.status, u.registration_date, 
                 p.first_name, p.last_name 
          FROM users u 
          LEFT JOIN profiles p ON u.user_id = p.user_id 
          ORDER BY u.registration_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .user-table th,
        .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .user-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .badge-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-student {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-alumni {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-admin {
            background: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>

<body style="background: #f5f7fa;">
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">Alumni Portal <span
                    style="font-size:12px; opacity:0.7;">ADMIN</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Manage Users</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container" style="margin-top: 40px;">
        <h2 style="margin-bottom: 20px;">Manage Users</h2>
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge-type badge-<?php echo $user['user_type']; ?>">
                                <?php echo ucfirst($user['user_type']); ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst($user['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>