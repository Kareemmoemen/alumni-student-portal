<?php
// FILE: pages/all_connections.php
// DESCRIPTION: Admin view for all mentorship connections

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

// Authorization Check
if ($user_type !== 'admin') {
    redirect('dashboard.php', 'Unauthorized access.', 'error');
}

$database = new Database();
$conn = $database->getConnection();

// Fetch all connections
$query = "SELECT mm.match_id, mm.status, mm.match_date, 
          s.first_name as s_first, s.last_name as s_last, s.user_id as s_id, s.major as s_major,
          a.first_name as a_first, a.last_name as a_last, a.user_id as a_id, a.current_position as a_pos, a.company as a_company
          FROM mentorship_matches mm 
          INNER JOIN profiles s ON mm.student_id = s.user_id 
          INNER JOIN profiles a ON mm.alumni_id = a.user_id 
          ORDER BY mm.match_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Connections - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            color: #2c3e50;
        }

        .connections-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr:hover {
            background: #fbfbfb;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .user-info div {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-info small {
            color: #888;
            display: block;
            font-size: 12px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active,
        .status-accepted {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #e2e6ea;
            color: #495057;
        }

        .empty-state {
            padding: 50px;
            text-align: center;
            color: #999;
        }
    </style>
</head>

<body>

    <?php
    $current_page = 'all_connections.php';
    require_once '../includes/navbar.php';
    ?>

    <div class="container">
        <div class="page-header">
            <h1>All Mentorship Connections</h1>
            <span class="badge badge-secondary"><?php echo count($all_matches); ?> Total</span>
        </div>

        <div class="connections-card">
            <?php if (count($all_matches) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Mentor</th>
                                <th>Status</th>
                                <th>Date Initiated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_matches as $match): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar"
                                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <?php echo strtoupper(substr($match['s_first'], 0, 1) . substr($match['s_last'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <div><?php echo htmlspecialchars($match['s_first'] . ' ' . $match['s_last']); ?>
                                                </div>
                                                <small><?php echo htmlspecialchars($match['s_major']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar"
                                                style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);">
                                                <?php echo strtoupper(substr($match['a_first'], 0, 1) . substr($match['a_last'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <div><?php echo htmlspecialchars($match['a_first'] . ' ' . $match['a_last']); ?>
                                                </div>
                                                <small><?php echo htmlspecialchars($match['a_pos'] ? $match['a_pos'] : 'Alumni'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $match['status']; ?>">
                                            <?php echo ucfirst($match['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($match['match_date'])); ?></td>
                                    <td>
                                        <a href="profile.php?id=<?php echo $match['s_id']; ?>" title="View Student"
                                            style="color: #667eea; margin-right: 10px;">
                                            <i class="fas fa-user-graduate"></i>
                                        </a>
                                        <a href="profile.php?id=<?php echo $match['a_id']; ?>" title="View Mentor"
                                            style="color: #fda085;">
                                            <i class="fas fa-user-tie"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No connections found.</h3>
                    <p>When students match with alumni, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>