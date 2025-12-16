<?php
// FILE: pages/my_mentorships.php
// DESCRIPTION: View and manage mentorship connections (pending, active, rejected)

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

$database = new Database();
$conn = $database->getConnection();

// Handle accept/reject actions
if (isset($_POST['action']) && isset($_POST['match_id'])) {

    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        redirect('my_mentorships.php', 'Invalid session token.', 'error');
    }

    $match_id = (int) $_POST['match_id'];
    $action = $_POST['action'];
    $query = null;

    if ($action === 'accept' || $action === 'reject') {
        // Only alumni can accept/reject
        if ($user_type !== 'alumni') {
            redirect('my_mentorships.php', 'Unauthorized action.', 'error');
        }
        $status = ($action === 'accept') ? 'active' : 'rejected';
        $query = "UPDATE mentorship_matches SET status = :status WHERE match_id = :match_id AND alumni_id = :user_id";
    } elseif ($action === 'end') {
        // Both can end a mentorship
        $status = 'completed';
        if ($user_type === 'student') {
            $query = "UPDATE mentorship_matches SET status = :status WHERE match_id = :match_id AND student_id = :user_id";
        } else {
            $query = "UPDATE mentorship_matches SET status = :status WHERE match_id = :match_id AND alumni_id = :user_id";
        }
    }

    if ($query) {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':match_id', $match_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $msg = match ($action) {
                    'accept' => 'Mentorship request accepted!',
                    'reject' => 'Request rejected.',
                    'end' => 'Mentorship ended and moved to past connections.',
                    default => 'Action completed.'
                };
                setSuccess($msg);
            } else {
                setError('Request not found or you are not authorized.');
            }
        } else {
            setError('Database error occurred. Please try again.');
        }

        header("Location: my_mentorships.php");
        exit();
    }
}

// Get all mentorships based on user type
if ($user_type === 'student') {
    // Student sees alumni info
    $query = "SELECT mm.*, 
             p.first_name, p.last_name, p.major, p.current_position, p.company, mm.alumni_id as other_user_id
             FROM mentorship_matches mm
             INNER JOIN profiles p ON mm.alumni_id = p.user_id
             WHERE mm.student_id = :user_id
             ORDER BY mm.match_date DESC";
} else {
    // Alumni sees student info
    $query = "SELECT mm.*, 
             p.first_name, p.last_name, p.major, mm.student_id as other_user_id
             FROM mentorship_matches mm
             INNER JOIN profiles p ON mm.student_id = p.user_id
             WHERE mm.alumni_id = :user_id
             ORDER BY mm.match_date DESC";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$mentorships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by status
$pending = array_filter($mentorships, fn($m) => $m['status'] === 'pending');
$active = array_filter($mentorships, fn($m) => $m['status'] === 'active' || $m['status'] === 'accepted');
$past = array_filter($mentorships, fn($m) => $m['status'] === 'rejected' || $m['status'] === 'completed');

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentorships - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .mentorships-container {
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
        }

        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .tabs {
            background: white;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tab {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .tab.active {
            background: white;
            color: #667eea;
            border-bottom: 3px solid #667eea;
            border-radius: 0;
            box-shadow: none;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .mentorship-card {
            background: white;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 24px;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .mentorship-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .mentorship-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .mentorship-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mentorship-info {
            flex: 1;
        }

        .mentorship-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .mentorship-meta {
            font-size: 14px;
            color: #666;
        }

        .mentorship-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-accept {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-accept:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #ccc;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
        }

        @media (max-width: 768px) {
            .mentorship-card {
                flex-direction: column;
                text-align: center;
            }

            .mentorship-actions {
                flex-direction: column;
                width: 100%;
            }

            .mentorship-actions button,
            .mentorship-actions a {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'my_mentorships.php';
    include '../includes/navbar.php';
    ?>

    <div class="mentorships-container">
        <?php if ($success = getSuccess()): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error = getError()): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h1>My Mentorship Connections</h1>
            <p>Manage your mentorship relationships</p>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('pending', this)">
                Pending (<?php echo count($pending); ?>)
            </button>
            <button class="tab" onclick="showTab('active', this)">
                Active (<?php echo count($active); ?>)
            </button>
            <button class="tab" onclick="showTab('past', this)">
                Past (<?php echo count($past); ?>)
            </button>
        </div>

        <!-- Pending Tab -->
        <div id="pending" class="tab-content active">
            <?php if (count($pending) > 0): ?>
                <?php foreach ($pending as $m): ?>
                    <div class="mentorship-card">
                        <div class="mentorship-avatar">
                            <?php echo strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)); ?>
                        </div>
                        <div class="mentorship-info">
                            <div class="mentorship-name">
                                <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                            </div>
                            <div class="mentorship-meta">
                                <?php echo htmlspecialchars($m['major']); ?> &bull;
                                Requested <?php echo timeAgo($m['match_date']); ?>
                            </div>
                        </div>
                        <div class="mentorship-actions">
                            <?php if ($user_type === 'alumni'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn-accept"
                                        onclick="return confirm('Accept this mentorship request?')">Accept</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn-reject"
                                        onclick="return confirm('Decline this request?')">Decline</button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-warning">Waiting for Response</span>
                            <?php endif; ?>
                            <a href="profile.php?id=<?php echo $m['other_user_id']; ?>" class="btn btn-primary btn-sm">View
                                Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No Pending Requests</h3>
                    <p><?php echo $user_type === 'student' ? 'Start by sending mentorship requests!' : 'No incoming requests at the moment.'; ?>
                    </p>
                    <?php if ($user_type === 'student'): ?>
                        <a href="matching.php" class="btn btn-primary" style="margin-top: 20px;">Find Mentors</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Tab -->
        <div id="active" class="tab-content">
            <?php if (count($active) > 0): ?>
                <?php foreach ($active as $m): ?>
                    <div class="mentorship-card">
                        <div class="mentorship-avatar">
                            <?php echo strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)); ?>
                        </div>
                        <div class="mentorship-info">
                            <div class="mentorship-name">
                                <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                            </div>
                            <div class="mentorship-meta">
                                <?php echo htmlspecialchars(ucwords($m['major'])); ?>
                                <?php if (!empty($m['current_position'])): ?>
                                    &bull; <?php echo htmlspecialchars(ucwords($m['current_position'])); ?>
                                <?php endif; ?>
                                <?php if (!empty($m['company'])): ?>
                                    @ <?php echo htmlspecialchars(ucwords($m['company'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mentorship-actions">
                            <div style="display:flex; flex-direction:column; gap:8px; width:100%; max-width:180px;">
                                <span class="badge badge-success"
                                    style="display:block; width:100%; text-align:center;">Active</span>
                                <a href="profile.php?id=<?php echo $m['other_user_id']; ?>" class="btn btn-primary btn-sm"
                                    style="width:100%; text-align:center;">View Profile</a>
                                <form method="POST"
                                    onsubmit="return confirm('Are you sure you want to end this mentorship? It will be moved to Past connections.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                                    <input type="hidden" name="action" value="end">
                                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">End
                                        Mentorship</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No Active Connections</h3>
                    <p>You don't have any active mentorship connections yet.</p>
                    <?php if ($user_type === 'student'): ?>
                        <a href="matching.php" class="btn btn-primary" style="margin-top: 20px;">Find Mentors</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Tab -->
        <div id="past" class="tab-content">
            <?php if (count($past) > 0): ?>
                <?php foreach ($past as $m): ?>
                    <div class="mentorship-card" style="opacity: 0.8;">
                        <div class="mentorship-avatar">
                            <?php echo strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)); ?>
                        </div>
                        <div class="mentorship-info">
                            <div class="mentorship-name">
                                <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                            </div>
                            <div class="mentorship-meta">
                                <?php echo htmlspecialchars($m['major']); ?> &bull;
                                <?php echo $m['status'] === 'completed' ? 'Completed ' . timeAgo($m['match_date']) : 'Declined ' . timeAgo($m['match_date']); ?>
                            </div>
                        </div>
                        <?php if ($m['status'] === 'completed'): ?>
                            <span class="badge badge-light">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Declined</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3>No Past Connections</h3>
                    <p>No completed or declined connections yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showTab(tabName, btn) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            btn.classList.add('active');
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>

</html>