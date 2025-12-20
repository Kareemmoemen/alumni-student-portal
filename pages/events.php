<?php
// FILE: pages/events.php
// DESCRIPTION: Main events page - view all events, register, filter

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

$database = new Database();
$conn = $database->getConnection();

// Handle event registration
if (isset($_POST['register_event'])) {
    $event_id = (int) $_POST['event_id'];

    try {
        // Validation: Check if event exists and is in the future
        $query = "SELECT event_date, max_attendees FROM events WHERE event_id = :event_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $event_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event_info) {
            setError("Event not found.");
            header("Location: events.php");
            exit();
        }

        if (strtotime($event_info['event_date']) < time()) {
            setError("Cannot register for past events.");
            header("Location: events.php");
            exit();
        }

        // Check if already registered (any status)
        $query = "SELECT registration_id, attendance_status FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $existing_reg = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_reg && $existing_reg['attendance_status'] === 'registered') {
            setError("You are already registered for this event!");
        } else {
            // Check capacity (excluding cancelled)
            $query = "SELECT COUNT(*) as current_count FROM event_registrations WHERE event_id = :event_id AND attendance_status = 'registered'";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':event_id', $event_id);
            $stmt->execute();
            $count_res = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_count = $count_res['current_count'];
            $max_attendees = (int) $event_info['max_attendees'];

            // Treat max_attendees <= 0 as unlimited
            if ($max_attendees > 0 && $current_count >= $max_attendees) {
                setError("Sorry, this event is full!");
            } else {
                if ($existing_reg) {
                    // Re-register: Update existing record
                    $query = "UPDATE event_registrations SET attendance_status = 'registered', registration_date = NOW() 
                              WHERE registration_id = :reg_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':reg_id', $existing_reg['registration_id']);
                    $stmt->execute();
                } else {
                    // New registration
                    $query = "INSERT INTO event_registrations (event_id, user_id, registration_date, attendance_status) 
                             VALUES (:event_id, :user_id, NOW(), 'registered')";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':event_id', $event_id);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                }

                setSuccess("Successfully registered for the event!");
            }
        }
        header("Location: events.php");
        exit();
    } catch (PDOException $e) {
        setError("Registration failed: " . $e->getMessage());
    }
}

// Handle cancel registration
if (isset($_POST['cancel_registration'])) {
    $event_id = (int) $_POST['event_id'];

    $query = "UPDATE event_registrations SET attendance_status = 'cancelled' 
             WHERE event_id = :event_id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':event_id', $event_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    setSuccess("Registration cancelled successfully!");
    header("Location: events.php");
    exit();
}

// Handle filters
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$view = isset($_GET['view']) ? sanitizeInput($_GET['view']) : 'upcoming';

// Build query
$current_date = date('Y-m-d H:i:s');

if ($view === 'upcoming') {
    $query = "SELECT e.*, p.first_name, p.last_name,
             (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND attendance_status != 'cancelled') as registered_count
             FROM events e
             INNER JOIN profiles p ON e.created_by = p.user_id
             WHERE e.event_date >= :current_date";
} else {
    $query = "SELECT e.*, p.first_name, p.last_name,
             (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND attendance_status != 'cancelled') as registered_count
             FROM events e
             INNER JOIN profiles p ON e.created_by = p.user_id
             WHERE e.event_date < :current_date";
}

if (!empty($filter_type)) {
    $query .= " AND e.event_type = :event_type";
}

$query .= " ORDER BY e.event_date " . ($view === 'upcoming' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($query);
$stmt->bindParam(':current_date', $current_date);
if (!empty($filter_type)) {
    $stmt->bindParam(':event_type', $filter_type);
}
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's registrations
$query = "SELECT event_id, attendance_status FROM event_registrations WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$registered_events = array_column($my_registrations, 'attendance_status', 'event_id');

// Get statistics
$query = "SELECT COUNT(*) as total FROM events WHERE event_date >= :current_date";
$stmt = $conn->prepare($query);
$stmt->bindParam(':current_date', $current_date);
$stmt->execute();
$total_upcoming = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(DISTINCT event_id) as total FROM event_registrations 
         WHERE user_id = :user_id AND attendance_status = 'registered'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .events-container {
            max-width: 1400px;
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

        .header-content h1 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-info h3 {
            font-size: 24px;
            color: #2c3e50;
            margin: 0;
        }

        .stat-info p {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        .filters-section {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }

        .view-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .view-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .type-filters {
            display: flex;
            gap: 10px;
        }

        .filter-chip {
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #666;
        }

        .filter-chip:hover,
        .filter-chip.active {
            background: #667eea;
            color: white;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .event-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 64px;
            position: relative;
        }

        .event-type-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .event-date-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .date-day {
            font-size: 24px;
            color: #667eea;
            line-height: 1;
        }

        .date-month {
            font-size: 12px;
            color: #666;
        }

        .event-content {
            padding: 20px;
        }

        .event-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }

        .event-description {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .attendees-info {
            font-size: 13px;
            color: #666;
        }

        .spots-left {
            color: #e74c3c;
            font-weight: 600;
        }

        .event-actions {
            display: flex;
            gap: 10px;
        }

        .btn-register {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-registered {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-full {
            padding: 10px 20px;
            background: #e0e0e0;
            color: #999;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }

            .filters-section {
                flex-direction: column;
                gap: 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .stats-bar {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand"><i class="fas fa-graduation-cap"></i> Alumni Portal</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="matching.php">Matching</a></li>
                <li><a href="jobs.php">Jobs</a></li>
                <li><a href="events.php" class="active">Events</a></li>
                <li><span class="badge badge-secondary"><?php echo ucfirst($user_type); ?></span></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="events-container">
        <?php
        $success = getSuccess();
        $error = getError();
        if ($success):
            ?>
            <div class="alert alert-success animate-fade-in"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error animate-fade-in"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header animate-fade-in-up">
            <div class="header-content">
                <h1><i class="fas fa-calendar-alt"></i> Events</h1>
                <p>Discover and attend networking events, workshops, and seminars</p>
            </div>
            <?php if ($user_type === 'alumni' || $user_type === 'admin'): ?>
                <a href="create_event.php" class="btn btn-primary btn-modern ripple-effect">
                    <i class="fas fa-plus"></i> Create Event
                </a>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats-bar animate-fade-in-up delay-100">
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3 class="counter" data-target="<?php echo $total_upcoming; ?>">0</h3>
                    <p>Upcoming Events</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3 class="counter" data-target="<?php echo $my_events; ?>">0</h3>
                    <p>My Registrations</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($events) > 0 ? array_sum(array_column($events, 'registered_count')) : 0; ?>
                    </h3>
                    <p>Total Attendees</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section animate-fade-in-up delay-200">
            <div class="view-toggle">
                <a href="?view=upcoming" class="view-btn <?php echo $view === 'upcoming' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Upcoming
                </a>
                <a href="?view=past" class="view-btn <?php echo $view === 'past' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Past Events
                </a>
            </div>

            <div class="type-filters">
                <a href="events.php?view=<?php echo $view; ?>"
                    class="filter-chip <?php echo empty($filter_type) ? 'active' : ''; ?>">
                    All
                </a>
                <a href="?view=<?php echo $view; ?>&type=networking"
                    class="filter-chip <?php echo $filter_type === 'networking' ? 'active' : ''; ?>">
                    Networking
                </a>
                <a href="?view=<?php echo $view; ?>&type=workshop"
                    class="filter-chip <?php echo $filter_type === 'workshop' ? 'active' : ''; ?>">
                    Workshop
                </a>
                <a href="?view=<?php echo $view; ?>&type=seminar"
                    class="filter-chip <?php echo $filter_type === 'seminar' ? 'active' : ''; ?>">
                    Seminar
                </a>
                <a href="?view=<?php echo $view; ?>&type=career-fair"
                    class="filter-chip <?php echo $filter_type === 'career-fair' ? 'active' : ''; ?>">
                    Career Fair
                </a>
                <a href="?view=<?php echo $view; ?>&type=social"
                    class="filter-chip <?php echo $filter_type === 'social' ? 'active' : ''; ?>">
                    Social
                </a>
            </div>
        </div>

        <!-- Events Grid -->
        <?php if (count($events) > 0): ?>
            <div class="events-grid stagger-animation">
                <?php foreach ($events as $event):
                    $event_date = strtotime($event['event_date']);
                    $is_full = ($event['max_attendees'] > 0 && $event['registered_count'] >= $event['max_attendees']);
                    $is_registered = isset($registered_events[$event['event_id']]) && $registered_events[$event['event_id']] === 'registered';
                    $spots_left = $event['max_attendees'] - $event['registered_count'];
                    ?>
                    <div class="event-card scroll-reveal hover-lift">
                        <div class="event-image">
                            <?php
                            $icon = match ($event['event_type']) {
                                'networking' => '<i class="fas fa-handshake"></i>',
                                'workshop' => '<i class="fas fa-tools"></i>',
                                'seminar' => '<i class="fas fa-graduation-cap"></i>',
                                'career-fair' => '<i class="fas fa-briefcase"></i>',
                                'social' => '<i class="fas fa-glass-cheers"></i>',
                                default => '<i class="fas fa-calendar-alt"></i>'
                            };
                            echo $icon;
                            ?>
                            <span class="event-type-badge">
                                <?php echo ucfirst(str_replace('-', ' ', $event['event_type'])); ?>
                            </span>
                            <div class="event-date-badge">
                                <div class="date-day"><?php echo date('d', $event_date); ?></div>
                                <div class="date-month"><?php echo strtoupper(date('M', $event_date)); ?></div>
                            </div>
                        </div>

                        <div class="event-content">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>

                            <div class="event-meta">
                                <div class="meta-item">
                                    <span><i class="far fa-clock"></i></span>
                                    <span><?php echo date('g:i A', $event_date); ?> on
                                        <?php echo date('F j, Y', $event_date); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span><i class="fas fa-map-marker-alt"></i></span>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span><i class="fas fa-user"></i></span>
                                    <span>By
                                        <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></span>
                                </div>
                            </div>

                            <div class="event-description">
                                <?php
                                $desc = htmlspecialchars($event['description']);
                                $desc_short = (strlen($desc) > 120) ? substr($desc, 0, 120) . '...' : $desc;
                                echo nl2br($desc_short);
                                ?>
                            </div>

                            <div class="event-footer">
                                <div class="attendees-info">
                                    <i class="fas fa-users"></i>
                                    <?php echo $event['registered_count']; ?>/<?php echo ($event['max_attendees'] > 0 ? $event['max_attendees'] : '∞'); ?>
                                    attendees
                                    <?php if (!$is_full && $spots_left <= 10 && $spots_left > 0 && $view === 'upcoming'): ?>
                                        <br><span class="spots-left"><?php echo $spots_left; ?> spots left!</span>
                                    <?php endif; ?>
                                </div>

                                <div class="event-actions">
                                    <?php if ($view === 'upcoming'): ?>
                                        <?php if ($is_registered): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                <button type="submit" name="cancel_registration" class="btn-registered"
                                                    onclick="return confirm('Cancel registration?')">
                                                    <i class="fas fa-check"></i> Registered
                                                </button>
                                            </form>
                                        <?php elseif ($is_full): ?>
                                            <button class="btn-full" disabled>Full</button>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                <button type="submit" name="register_event" class="btn-register ripple-effect">
                                                    Register
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="event_details.php?id=<?php echo $event['event_id']; ?>"
                                        class="btn btn-secondary btn-sm">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-events">
                <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"><i class="fas fa-calendar-times"></i></div>
                <h3>No Events Found</h3>
                <p>
                    <?php if ($view === 'upcoming'): ?>
                        Check back later for upcoming events!
                    <?php else: ?>
                        No past events to display.
                    <?php endif; ?>
                </p>
                <?php if ($user_type === 'alumni' || $user_type === 'admin'): ?>
                    <a href="create_event.php" class="btn btn-primary" style="margin-top: 20px;">Create First Event</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
</body>

</html>