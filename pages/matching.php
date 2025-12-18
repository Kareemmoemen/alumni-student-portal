<?php
// FILE: pages/matching.php
// DESCRIPTION: Main matching page - students find alumni mentors, alumni find students

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

$database = new Database();
$conn = $database->getConnection();

// Get current user's profile
$query = "SELECT * FROM profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle search filters
$search_major = isset($_GET['major']) ? sanitizeInput($_GET['major']) : '';
$search_location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$search_name = isset($_GET['name']) ? sanitizeInput($_GET['name']) : '';

// Build query based on user type
if ($user_type === 'student') {
    // Keep SELECT p.* but we won't use profile_picture
    $query = "SELECT DISTINCT p.*, u.user_id, u.email,
             (SELECT GROUP_CONCAT(skill_name SEPARATOR ', ') 
              FROM skills WHERE user_id = u.user_id) as skills
             FROM profiles p
             INNER JOIN users u ON p.user_id = u.user_id
             WHERE u.user_type = 'alumni' 
             AND u.status = 'active'
             AND u.user_id != :user_id";

    if (!empty($search_major)) {
        $query .= " AND p.major LIKE :major";
    }
    if (!empty($search_location)) {
        $query .= " AND p.location LIKE :location";
    }
    if (!empty($search_name)) {
        $query .= " AND (p.first_name LIKE :name OR p.last_name LIKE :name)";
    }

    $query .= " ORDER BY 
                CASE WHEN p.major = :my_major THEN 1 ELSE 2 END,
                p.first_name ASC
                LIMIT 50";
} else {
    $query = "SELECT DISTINCT p.*, u.user_id, u.email,
             (SELECT GROUP_CONCAT(skill_name SEPARATOR ', ') 
              FROM skills WHERE user_id = u.user_id) as skills
             FROM profiles p
             INNER JOIN users u ON p.user_id = u.user_id
             WHERE u.user_type = 'student' 
             AND u.status = 'active'
             AND u.user_id != :user_id";

    if (!empty($search_major)) {
        $query .= " AND p.major LIKE :major";
    }
    if (!empty($search_location)) {
        $query .= " AND p.location LIKE :location";
    }
    if (!empty($search_name)) {
        $query .= " AND (p.first_name LIKE :name OR p.last_name LIKE :name)";
    }

    $query .= " ORDER BY p.first_name ASC LIMIT 50";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
if ($user_type === 'student') {
    $stmt->bindParam(':my_major', $my_profile['major']);
}

if (!empty($search_major)) {
    $major_param = "%{$search_major}%";
    $stmt->bindParam(':major', $major_param);
}
if (!empty($search_location)) {
    $location_param = "%{$search_location}%";
    $stmt->bindParam(':location', $location_param);
}
if (!empty($search_name)) {
    $name_param = "%{$search_name}%";
    $stmt->bindParam(':name', $name_param);
}

$stmt->execute();
$potential_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing connections
$query = "SELECT alumni_id, student_id, status FROM mentorship_matches 
         WHERE (student_id = :user_id OR alumni_id = :user_id)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$existing_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$connections = [];
foreach ($existing_matches as $match) {
    $other_user = ($match['student_id'] == $user_id) ? $match['alumni_id'] : $match['student_id'];
    $connections[$other_user] = $match['status'];
}

// Get unique majors
$query = "SELECT DISTINCT major FROM profiles WHERE major IS NOT NULL AND major != '' ORDER BY major";
$stmt = $conn->prepare($query);
$stmt->execute();
$all_majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique locations
$query = "SELECT DISTINCT location FROM profiles WHERE location IS NOT NULL AND location != '' ORDER BY location";
$stmt = $conn->prepare($query);
$stmt->execute();
$all_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate match percentage
function calculateMatchPercentage($my_profile, $their_profile)
{
    $score = 0;

    if (!empty($my_profile['major']) && !empty($their_profile['major'])) {
        if (strtolower($my_profile['major']) === strtolower($their_profile['major'])) {
            $score += 40;
        }
    }

    if (!empty($my_profile['location']) && !empty($their_profile['location'])) {
        if (stripos($their_profile['location'], $my_profile['location']) !== false) {
            $score += 20;
        }
    }

    $completeness = 0;
    if (!empty($their_profile['bio']))
        $completeness += 5;
    if (!empty($their_profile['current_position']))
        $completeness += 5;
    if (!empty($their_profile['company']))
        $completeness += 5;
    if (!empty($their_profile['skills']))
        $completeness += 5;
    // Removed profile picture score addition (was +10)

    $score += $completeness;

    if (!empty($my_profile['graduation_year']) && !empty($their_profile['graduation_year'])) {
        $year_diff = abs($my_profile['graduation_year'] - $their_profile['graduation_year']);
        if ($year_diff <= 2)
            $score += 10;
        elseif ($year_diff <= 5)
            $score += 5;
    }

    return min($score, 100);
}

// Generate CSRF token for AJAX requests
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentors - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .matching-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .match-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
            border-color: #d0d0d0;
        }

        .match-header {
            padding: 20px;
            display: flex;
            gap: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .match-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            flex-shrink: 0;
            overflow: hidden;
        }

        /* Removed .match-avatar img CSS as images are no longer used */
        .match-info {
            flex: 1;
        }

        .match-name {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .match-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .match-percentage {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #edf2f7;
            color: #4a5568;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .match-body {
            padding: 20px;
            flex: 1;
            /* Pushes actions to the bottom of the card */
            display: flex;
            flex-direction: column;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #ccc;
        }

        .match-skills {
            margin-bottom: 15px;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .skill-tag {
            background: #e8f0fe;
            color: #1967d2;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        .match-actions {
            display: flex;
            gap: 12px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #f0f0f0;
            align-items: center;
            /* Prevents stretching */
        }

        .match-actions>* {
            flex: 1;
            /* Generic solution: All children share space equally */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .btn-request {
            /* flex: 1; - Removed, handled by .match-actions > * */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-view {
            background: white;
            color: #667eea;
            padding: 12px 20px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: #667eea;
            color: white;
        }

        /* Improved Form Controls */
        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .status-badge {
            /* flex: 1; - Removed, handled by .match-actions > * */
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: fit-content;
            line-height: normal;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d4edda;
            color: #155724;
        }

        .status-active {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #e2e6ea;
            color: #495057;
        }

        /* Added rejected status style */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .matches-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'matching.php';
    require_once '../includes/navbar.php';
    ?>

    <!-- Page Loader -->
    <div id="pageLoader"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); display: flex; align-items: center; justify-content: center; z-index: 99999; transition: opacity 0.3s ease;">
        <div style="text-align: center;">
            <div class="loading-spinner"
                style="width: 60px; height: 60px; border: 6px solid #f0f0f0; border-top-color: #667eea; border-radius: 50%; animation: rotate 1s linear infinite; margin: 0 auto 12px;">
            </div>
            <p style="color: #667eea; font-weight: 600;">Loading...</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            const loader = document.getElementById('pageLoader');
            if (!loader) return;
            loader.style.opacity = '0';
            setTimeout(() => loader.remove(), 300);
        });
    </script>

    <div class="matching-container">
        <?php if ($success = getSuccess()): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="page-header glass-white animate-fade-in-up">
            <h1 class="text-gradient"><?php echo $user_type === 'student' ? 'Find Mentors' : 'Find Students'; ?></h1>
            <p class="animate-fade-in-up delay-100">
                <?php echo $user_type === 'student' ? 'Connect with experienced alumni' : 'Share your expertise with students'; ?>
            </p>
        </div>

        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control input-focus-glow"
                            placeholder="Search by name..." value="<?php echo htmlspecialchars($search_name); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="major">Major</label>
                        <select id="major" name="major" class="form-control input-focus-glow">
                            <option value="">All Majors</option>
                            <?php foreach ($all_majors as $major): ?>
                                <option value="<?php echo htmlspecialchars($major['major']); ?>" <?php echo $search_major === $major['major'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($major['major']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="location">Location</label>
                        <select id="location" name="location" class="form-control input-focus-glow">
                            <option value="">All Locations</option>
                            <?php foreach ($all_locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location']); ?>" <?php echo $search_location === $location['location'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <a href="matching.php" class="btn btn-secondary btn-sm">Clear</a>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </div>
            </form>
        </div>

        <div class="results-header">
            <div class="results-count"><strong><?php echo count($potential_matches); ?></strong> found</div>
        </div>

        <?php if (count($potential_matches) > 0): ?>
            <div class="matches-grid">
                <?php foreach ($potential_matches as $match):
                    $match_percentage = calculateMatchPercentage($my_profile, $match);
                    $connection_status = isset($connections[$match['user_id']]) ? $connections[$match['user_id']] : null;
                    ?>
                    <div class="match-card scroll-reveal hover-lift card-tilt-3d" style="transition: all 0.3s ease;">
                        <div class="match-percentage animate-pulse">
                            <span style="font-size: 18px; font-weight: 700;"><?php echo $match_percentage; ?>%</span><br>
                            <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">Match</span>
                        </div>
                        <div class="match-header">
                            <div class="match-avatar">
                                <?php echo strtoupper(substr($match['first_name'], 0, 1) . substr($match['last_name'], 0, 1)); ?>
                            </div>
                            <div class="match-info">
                                <div class="match-name">
                                    <?php echo htmlspecialchars(ucwords($match['first_name'] . ' ' . $match['last_name'])); ?>
                                </div>
                                <?php if (!empty($match['current_position'])): ?>
                                    <div class="match-title">
                                        <?php echo htmlspecialchars(ucwords($match['current_position'])); ?>
                                        <?php if (!empty($match['company'])): ?> at
                                            <?php echo htmlspecialchars(ucwords($match['company'])); ?>             <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="match-body">
                            <?php if (!empty($match['major'])): ?>
                                <div class="detail-item"><?php echo htmlspecialchars(ucwords($match['major'])); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($match['location'])): ?>
                                <div class="detail-item"><?php echo htmlspecialchars(ucwords($match['location'])); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($match['skills'])): ?>
                                <div class="match-skills">
                                    <div class="skills-list">
                                        <?php
                                        $skills = array_slice(explode(', ', $match['skills']), 0, 4);
                                        foreach ($skills as $skill):
                                            ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="match-actions">
                            <?php if ($connection_status): ?>
                                <span
                                    class="status-badge status-<?php echo $connection_status; ?>"><?php echo ucfirst($connection_status); ?></span>
                            <?php elseif ($user_type === 'student'): ?>
                                <button class="btn-request btn-modern ripple-effect hover-glow"
                                    onclick="sendRequest(<?php echo $match['user_id']; ?>, this)">
                                    <span style="display: flex; align-items: center; gap: 8px;">
                                        <span>🤝</span>
                                        <span>Request Mentorship</span>
                                    </span>
                                </button>
                            <?php endif; ?>
                            <a href="profile.php?id=<?php echo $match['user_id']; ?>" class="btn-view">
                                <i class="fas fa-user-circle" style="margin-right:8px;"></i> View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>No matches found</h3>
                <p>Try adjusting your filters</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden CSRF Token -->
    <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <script src="../assets/js/main.js"></script>
    <script>
        function sendRequest(alumniId, btn) {
            if (!confirm('Send mentorship request?')) return;

            btn.disabled = true;
            btn.innerHTML = '<div class="loading-dots"><span></span><span></span><span></span></div>';

            const csrfToken = document.getElementById('csrf_token').value;

            fetch('send_mentorship_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'alumni_id=' + alumniId + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Mentorship request sent successfully!', 'success');
                        createConfetti();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                        btn.disabled = false;
                        btn.textContent = 'Request Mentorship';
                    }
                })
                .catch(error => {
                    showToast('An error occurred', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Request Mentorship';
                });
        }
    </script>
</body>

</html>
```