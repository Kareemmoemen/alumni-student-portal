<?php
// FILE: pages/create_event.php
// DESCRIPTION: Create new events (alumni/admin only)

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Only alumni and admin can create events
if (getUserType() !== 'alumni' && getUserType() !== 'admin') {
    setError("Only alumni and administrators can create events.");
    header("Location: events.php");
    exit();
}

$user_id = getUserId();
$database = new Database();
$conn = $database->getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $event_date = sanitizeInput($_POST['event_date']);
    $event_time = sanitizeInput($_POST['event_time']);
    $location = sanitizeInput($_POST['location']);
    $event_type = sanitizeInput($_POST['event_type']);
    $max_attendees = (int)$_POST['max_attendees'];
    
    // Combine date and time
    $event_datetime = $event_date . ' ' . $event_time;
    
    // Validate
    if (empty($title)) $errors[] = "Event title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($event_date)) $errors[] = "Event date is required";
    if (empty($event_time)) $errors[] = "Event time is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($event_type)) $errors[] = "Event type is required";
    if ($max_attendees < 1) $errors[] = "Maximum attendees must be at least 1";
    
    // Validate datetime is in future
    if (strtotime($event_datetime) < time()) {
        $errors[] = "Event must be scheduled for a future date and time";
    }
    
    if (empty($errors)) {
        try {
            $query = "INSERT INTO events (created_by, title, description, event_date, location, event_type, max_attendees, created_at) 
                     VALUES (:created_by, :title, :description, :event_date, :location, :event_type, :max_attendees, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':created_by', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':event_date', $event_datetime);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_type', $event_type);
            $stmt->bindParam(':max_attendees', $max_attendees);
            $stmt->execute();
            
            setSuccess("Event created successfully!");
            header("Location: events.php");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Error creating event: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .create-event-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
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
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .event-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .type-option {
            position: relative;
        }
        
        .type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .type-option input[type="radio"]:checked + .type-label {
            background: #e8f0fe;
            border-color: #667eea;
        }
        
        .type-icon {
            font-size: 32px;
        }
        
        .type-name {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .event-type-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <li><a href="events.php">Events</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="create-event-container">
        <div class="form-card animate-fade-in-up">
            <div class="form-header">
                <h1><i class="fas fa-sparkles"></i> Create New Event</h1>
                <p>Organize networking events, workshops, and more for the community</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-clipboard-list"></i> Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="title">Event Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control input-focus-glow" 
                               placeholder="e.g., Career Development Workshop" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-control input-focus-glow" 
                                  placeholder="Describe what attendees can expect from this event..."
                                  required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Event Type -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-bullseye"></i> Event Type</h3>
                    
                    <div class="event-type-grid">
                        <div class="type-option">
                            <input type="radio" id="networking" name="event_type" value="networking" 
                                   <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'networking') ? 'checked' : ''; ?> required>
                            <label for="networking" class="type-label">
                                <div class="type-icon"><i class="fas fa-handshake"></i></div>
                                <div class="type-name">Networking</div>
                            </label>
                        </div>
                        
                        <div class="type-option">
                            <input type="radio" id="workshop" name="event_type" value="workshop"
                                   <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'workshop') ? 'checked' : ''; ?>>
                            <label for="workshop" class="type-label">
                                <div class="type-icon"><i class="fas fa-tools"></i></div>
                                <div class="type-name">Workshop</div>
                            </label>
                        </div>
                        
                        <div class="type-option">
                            <input type="radio" id="seminar" name="event_type" value="seminar"
                                   <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'seminar') ? 'checked' : ''; ?>>
                            <label for="seminar" class="type-label">
                                <div class="type-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div class="type-name">Seminar</div>
                            </label>
                        </div>
                        
                        <div class="type-option">
                            <input type="radio" id="career-fair" name="event_type" value="career-fair"
                                   <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'career-fair') ? 'checked' : ''; ?>>
                            <label for="career-fair" class="type-label">
                                <div class="type-icon"><i class="fas fa-briefcase"></i></div>
                                <div class="type-name">Career Fair</div>
                            </label>
                        </div>
                        
                        <div class="type-option">
                            <input type="radio" id="social" name="event_type" value="social"
                                   <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'social') ? 'checked' : ''; ?>>
                            <label for="social" class="type-label">
                                <div class="type-icon"><i class="fas fa-glass-cheers"></i></div>
                                <div class="type-name">Social</div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Date & Location -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Date & Location</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Event Date <span class="required">*</span></label>
                            <input type="date" id="event_date" name="event_date" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="event_time">Event Time <span class="required">*</span></label>
                            <input type="time" id="event_time" name="event_time" class="form-control" 
                                   value="<?php echo isset($_POST['event_time']) ? htmlspecialchars($_POST['event_time']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" class="form-control input-focus-glow" 
                               placeholder="e.g., University Auditorium, Cairo" 
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_attendees">Maximum Attendees <span class="required">*</span></label>
                        <input type="number" id="max_attendees" name="max_attendees" class="form-control input-focus-glow" 
                               min="1" max="1000" 
                               placeholder="e.g., 100" 
                               value="<?php echo isset($_POST['max_attendees']) ? htmlspecialchars($_POST['max_attendees']) : '50'; ?>" 
                               required>
                        <small class="form-help">Set the capacity limit for this event</small>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg btn-modern ripple-effect hover-glow" style="flex: 1;">
                        <i class="fas fa-plus-circle"></i> Create Event
                    </button>
                    <a href="events.php" class="btn btn-secondary btn-lg" style="flex: 0.3;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Validate datetime is in future
        document.querySelector('form').addEventListener('submit', function(e) {
            const dateInput = document.getElementById('event_date').value;
            const timeInput = document.getElementById('event_time').value;
            const eventDateTime = new Date(dateInput + ' ' + timeInput);
            const now = new Date();
            
            if (eventDateTime <= now) {
                e.preventDefault();
                alert('Event must be scheduled for a future date and time!');
                return false;
            }
        });
    </script>
</body>
</html>
