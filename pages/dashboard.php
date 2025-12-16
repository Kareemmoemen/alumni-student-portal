<?php
// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get user information
$user_id = getUserId();
$user_type = getUserType();
$user_email = getUserEmail();

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Get user profile information
$query = "SELECT p.*, u.email, u.registration_date 
          FROM profiles p 
          INNER JOIN users u ON p.user_id = u.user_id 
          WHERE p.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get platform statistics
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'alumni' AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_alumni = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM mentorship_matches WHERE status IN ('active', 'pending')";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get user-specific data based on user type
if ($user_type === 'student') {
    // Get student's mentorship requests
    $query = "SELECT COUNT(*) as total FROM mentorship_matches 
              WHERE student_id = :user_id AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pending_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get suggested mentors (alumni with similar major)
    $query = "SELECT p.*, u.user_id 
              FROM profiles p
              INNER JOIN users u ON p.user_id = u.user_id
              WHERE u.user_type = 'alumni' 
                AND u.status = 'active'
                AND p.major = :major
                AND u.user_id != :user_id
              LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':major', $profile['major']);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $suggested_mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($user_type === 'alumni') {
    // Get mentorship requests for alumni
    $query = "SELECT COUNT(*) as total FROM mentorship_matches 
              WHERE alumni_id = :user_id AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pending_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get jobs posted by this alumni
    $query = "SELECT COUNT(*) as total FROM jobs 
              WHERE posted_by = :user_id AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $my_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Get recent forum posts
$query = "SELECT fp.*, p.first_name, p.last_name, u.user_type,
          (SELECT COUNT(*) FROM forum_replies WHERE post_id = fp.post_id) as reply_count
          FROM forum_posts fp
          INNER JOIN users u ON fp.user_id = u.user_id
          INNER JOIN profiles p ON fp.user_id = p.user_id
          ORDER BY fp.created_at DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming events
$query = "SELECT e.*, p.first_name, p.last_name
          FROM events e
          INNER JOIN profiles p ON e.created_by = p.user_id
          WHERE e.event_date >= NOW()
          ORDER BY e.event_date ASC
          LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .welcome-card h2 {
            color: white;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: rgba(255, 255, 255, 0.9);
        }

        .stats-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-mini-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-mini-card h3 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-mini-card p {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .activity-content p {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .activity-meta {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .action-card {
            padding: 20px;
            background: white;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .action-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .action-card .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .action-card h4 {
            font-size: 14px;
            margin: 0;
            color: #2c3e50;
        }

        .event-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .event-item h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .event-meta {
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 968px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-mini-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

    <!-- Skip link for keyboard users (PDF Step 5) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php
    $current_page = 'dashboard.php';
    include '../includes/navbar.php';
    ?>

    <!-- Main content wrapper for accessibility -->
    <main id="main-content">
        <div class="container" style="margin-top: 30px;">
            <?php
            $success = getSuccess();
            if ($success):
                ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($profile['first_name']); ?>!</h2>
                <p>Member since <?php echo formatDate($profile['registration_date']); ?></p>
            </div>

            <!-- Platform Stats -->
            <div class="stats-mini-grid" style="margin-top: 20px;">
                <div class="stat-mini-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Members</p>
                </div>
                <div class="stat-mini-card">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Students</p>
                </div>
                <div class="stat-mini-card">
                    <h3><?php echo $total_alumni; ?></h3>
                    <p>Alumni</p>
                </div>
                <div class="stat-mini-card">
                    <h3><?php echo $total_matches; ?></h3>
                    <p>Connections</p>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="action-grid">
                                <a href="profile.php" class="action-card">
                                    <div class="icon"><i class="fas fa-user-circle"></i></div>
                                    <h4>My Profile</h4>
                                </a>
                                <a href="matching.php" class="action-card">
                                    <div class="icon"><i class="fas fa-handshake"></i></div>
                                    <h4>Find Mentors</h4>
                                </a>
                                <a href="forum.php" class="action-card">
                                    <div class="icon"><i class="fas fa-comments"></i></div>
                                    <h4>Forum</h4>
                                </a>
                                <a href="jobs.php" class="action-card">
                                    <div class="icon"><i class="fas fa-briefcase"></i></div>
                                    <h4>Job Board</h4>
                                </a>
                                <a href="events.php" class="action-card">
                                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                                    <h4>Events</h4>
                                </a>
                                <a href="manage_skills.php" class="action-card">
                                    <div class="icon"><i class="fas fa-star"></i></div>
                                    <h4>Skills</h4>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Forum Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Forum Activity</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_posts) > 0): ?>
                                <?php foreach ($recent_posts as $post): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon"></div>
                                        <div class="activity-content">
                                            <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                            <p><?php echo substr(htmlspecialchars($post['content']), 0, 100); ?>...</p>
                                            <div class="activity-meta">
                                                By
                                                <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                                                • <?php echo timeAgo($post['created_at']); ?>
                                                • <?php echo $post['reply_count']; ?> replies
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No forum activity yet.</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="forum.php" class="btn btn-outline">View All Posts</a>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- User-specific Card -->
                    <?php if ($user_type === 'student'): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Your Stats</h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-mini-card" style="margin-bottom: 15px;">
                                    <h3><?php echo $pending_requests; ?></h3>
                                    <p>Pending Requests</p>
                                </div>
                                <a href="matching.php" class="btn btn-primary btn-block">Find Mentors</a>
                            </div>
                        </div>

                        <?php if (count($suggested_mentors) > 0): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3>Suggested Mentors</h3>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($suggested_mentors as $mentor): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon"></div>
                                            <div class="activity-content">
                                                <h4><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                                </h4>
                                                <p><?php echo htmlspecialchars($mentor['major']); ?></p>
                                                <?php if (!empty($mentor['company'])): ?>
                                                    <div class="activity-meta">
                                                        <?php echo htmlspecialchars($mentor['company']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="matching.php" class="btn btn-outline btn-block">View All</a>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($user_type === 'alumni'): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Your Activity</h3>
                            </div>
                            <div class="card-body">
                                <div class="stats-mini-grid" style="grid-template-columns: 1fr;">
                                    <div class="stat-mini-card" style="margin-bottom: 10px;">
                                        <h3><?php echo $pending_requests; ?></h3>
                                        <p>Mentorship Requests</p>
                                    </div>
                                    <div class="stat-mini-card">
                                        <h3><?php echo $my_jobs; ?></h3>
                                        <p>Active Job Posts</p>
                                    </div>
                                </div>
                                <a href="jobs.php?action=create" class="btn btn-primary btn-block"
                                    style="margin-top: 15px;">
                                    Post a Job
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Upcoming Events -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Upcoming Events</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($upcoming_events) > 0): ?>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="event-item">
                                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                        <div class="event-meta">
                                            <?php echo formatDate($event['event_date']); ?><br>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No upcoming events.</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="events.php" class="btn btn-outline btn-block">View All Events</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include JavaScript at the very end -->
    <script src="../assets/js/main.js"></script>
</body>

</html>