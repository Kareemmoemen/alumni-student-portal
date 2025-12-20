<?php
// FILE: pages/event_details.php
// DESCRIPTION: View single event details, attendees, and registration status

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$database = new Database();
$conn = $database->getConnection();

$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch event details
$query = "SELECT e.*, p.first_name, p.last_name, p.profile_picture, p.major, p.graduation_year 
          FROM events e 
          INNER JOIN profiles p ON e.created_by = p.user_id 
          WHERE e.event_id = :event_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    setError("Event not found.");
    header("Location: events.php");
    exit();
}

// Fetch attendees (excluding cancelled)
$query = "SELECT p.first_name, p.last_name, p.profile_picture, p.major, p.user_id, er.registration_date 
          FROM event_registrations er 
          INNER JOIN profiles p ON er.user_id = p.user_id 
          WHERE er.event_id = :event_id AND er.attendance_status = 'registered' 
          ORDER BY er.registration_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();
$attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check user registration status
$query = "SELECT attendance_status FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $event_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$reg_data = $stmt->fetch(PDO::FETCH_ASSOC);
$is_registered = ($reg_data && $reg_data['attendance_status'] === 'registered');

// Logic for display
$current_count = count($attendees);
$max_attendees = (int) $event['max_attendees'];
$is_full = ($max_attendees > 0 && $current_count >= $max_attendees);
$spots_left = ($max_attendees > 0) ? ($max_attendees - $current_count) : 999;
if ($spots_left < 0)
    $spots_left = 0;

$event_date_ts = strtotime($event['event_date']);
$is_past = ($event_date_ts < time());

$capacity_percent = ($max_attendees > 0) ? min(100, round(($current_count / $max_attendees) * 100)) : 0;

// Icon mapping
$icon = match ($event['event_type']) {
    'networking' => '<i class="fas fa-handshake"></i>',
    'workshop' => '<i class="fas fa-tools"></i>',
    'seminar' => '<i class="fas fa-graduation-cap"></i>',
    'career-fair' => '<i class="fas fa-briefcase"></i>',
    'social' => '<i class="fas fa-glass-cheers"></i>',
    default => '<i class="fas fa-calendar-alt"></i>'
};

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Event Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .details-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }

        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .event-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .event-icon-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            flex-shrink: 0;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .event-title-block h1 {
            color: #2c3e50;
            font-size: 28px;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .event-meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .meta-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f8f9fa;
            border-radius: 20px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .badge-type {
            background: #e8f0fe;
            color: #1a73e8;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            margin-top: 0;
        }

        .event-description {
            line-height: 1.8;
            color: #555;
            font-size: 16px;
        }

        .attendees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .attendee-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .attendee-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .attendee-name {
            font-size: 12px;
            color: #666;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Sidebar styles */
        .sidebar {
            position: sticky;
            top: 20px;
        }

        .capacity-bar {
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            margin: 15px 0 10px;
            overflow: hidden;
        }

        .capacity-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 4px;
            transition: width 1s ease;
        }

        .capacity-text {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }

        .organizer-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .organizer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .action-btn-full {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-register-lg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-register-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel-lg {
            background: #fff;
            border: 2px solid #e74c3c;
            color: #e74c3c;
        }

        .btn-cancel-lg:hover {
            background: #ffeaea;
        }

        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand"><i class="fas fa-graduation-cap"></i> Alumni Portal</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="details-container">

        <!-- Back Link -->
        <a href="events.php"
            style="display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: 600;">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <div class="layout-grid">
            <!-- Main Content -->
            <div class="main-content">
                <div class="card animate-fade-in-up">
                    <div class="event-header">
                        <div class="event-icon-large">
                            <?php echo $icon; ?>
                        </div>
                        <div class="event-title-block">
                            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                            <div class="event-meta-tags">
                                <span class="meta-tag badge-type">
                                    <?php echo ucfirst(str_replace('-', ' ', $event['event_type'])); ?>
                                </span>
                                <span class="meta-tag">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', $event_date_ts); ?>
                                </span>
                                <span class="meta-tag">
                                    <i class="far fa-clock"></i> <?php echo date('g:i A', $event_date_ts); ?>
                                </span>
                                <span class="meta-tag">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h2 class="section-title">About This Event</h2>
                        <div class="event-description">
                            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Attendees Section -->
                <div class="card scroll-reveal">
                    <h2 class="section-title">Who's Going? (<?php echo $current_count; ?>)</h2>
                    <?php if ($current_count > 0): ?>
                        <div class="attendees-grid">
                            <?php foreach ($attendees as $att):
                                $initials = strtoupper(substr($att['first_name'], 0, 1) . substr($att['last_name'], 0, 1));
                                ?>
                                <a href="profile.php?id=<?php echo $att['user_id']; ?>" class="attendee-item"
                                    style="text-decoration: none;">
                                    <div class="attendee-avatar"
                                        style="background: <?php echo ($att['user_id'] == $event['created_by']) ? '#667eea' : '#e0e0e0'; ?>; 
                                                color: <?php echo ($att['user_id'] == $event['created_by']) ? '#fff' : '#555'; ?>;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <div class="attendee-name"><?php echo htmlspecialchars($att['first_name']); ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #888; font-style: italic;">Be the first to register!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="card animate-fade-in-up delay-100">
                    <h2 class="section-title">Registration</h2>

                    <div class="capacity-bar">
                        <div class="capacity-fill" style="width: <?php echo $capacity_percent; ?>%;"></div>
                    </div>
                    <div class="capacity-text">
                        <span><?php echo $current_count; ?> registered</span>
                        <span>
                            <?php echo ($max_attendees > 0) ? $max_attendees . ' max' : 'Unlimited'; ?>
                        </span>
                    </div>

                    <?php if ($is_past): ?>
                        <button class="action-btn-full btn-disabled" disabled>Event Has Ended</button>
                    <?php elseif ($is_registered): ?>
                        <form method="POST" action="events.php">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <button type="submit" name="cancel_registration"
                                class="action-btn-full btn-cancel-lg ripple-effect"
                                onclick="return confirm('Are you sure you want to cancel your registration?');">
                                Cancel Registration
                            </button>
                        </form>
                    <?php elseif ($is_full): ?>
                        <button class="action-btn-full btn-disabled" disabled>Event Full</button>
                    <?php else: ?>
                        <form method="POST" action="events.php">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <button type="submit" name="register_event"
                                class="action-btn-full btn-register-lg ripple-effect">
                                <?php echo ($spots_left <= 10 && $max_attendees > 0) ? "<i class='fas fa-fire'></i> Register ($spots_left left)" : "Register Now"; ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Organizer Info -->
                    <div class="organizer-profile">
                        <?php
                        $org_initials = strtoupper(substr($event['first_name'], 0, 1) . substr($event['last_name'], 0, 1));
                        ?>
                        <div class="organizer-avatar"><?php echo $org_initials; ?></div>
                        <div>
                            <div style="font-size: 12px; color: #999; text-transform: uppercase; font-weight: 600;">
                                Organizer</div>
                            <a href="profile.php?id=<?php echo $event['created_by']; ?>"
                                style="font-weight: 700; color: #2c3e50; text-decoration: none;">
                                <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
</body>

</html>