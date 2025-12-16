<?php
// Load required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user info
// Get current user info
$logged_in_user_id = getUserId();
$user_type = getUserType();

// Determine which profile to view
$view_user_id = isset($_GET['id']) ? (int) $_GET['id'] : $logged_in_user_id;

// Basic validation
if ($view_user_id <= 0) {
    $view_user_id = $logged_in_user_id;
}

$is_own_profile = ($logged_in_user_id === $view_user_id);

// Create DB connection
$database = new Database();
$conn = $database->getConnection();

// Get user profile + account info
$query = "SELECT p.user_id, p.first_name, p.last_name, p.major, p.graduation_year, p.current_position, p.company, p.location, p.bio, u.email, u.registration_date
          FROM profiles p
          INNER JOIN users u ON p.user_id = u.user_id
          WHERE p.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);


// Get skills
$query = "SELECT * FROM skills WHERE user_id = :user_id ORDER BY skill_name";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $view_user_id, PDO::PARAM_INT);
$stmt->execute();
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for badge style
function getSkillBadgeClass($level)
{
    return match (strtolower($level)) {
        'intermediate' => 'skill-intermediate',
        'advanced' => 'skill-advanced',
        default => 'skill-beginner'
    };
}

// Simple fallback if profile is missing
if (!$profile) {
    $profile = [
        'first_name' => 'Unknown',
        'last_name' => '',
        'email' => getUserEmail(),
        'major' => '',

        'graduation_year' => '',
        'current_position' => '',
        'company' => '',
        'bio' => '',
        'registration_date' => date('Y-m-d'),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            color: white;
        }

        .profile-banner {
            height: 150px;
            /* background removed */
            position: relative;
        }

        .profile-main {
            padding: 0 40px 40px;
            margin-top: -60px;
            position: relative;
        }



        .profile-info {
            margin-top: 70px;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .profile-title {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Custom button overrides for dark background */
        .profile-actions .btn {
            background: white;
            color: #667eea;
            border: none;
        }

        .profile-actions .btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .info-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .skill-beginner {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .skill-intermediate {
            background: #cfe2ff;
            color: #004085;
            border: 2px solid #3498db;
        }

        .skill-advanced {
            background: #d1e7dd;
            color: #0f5132;
            border: 2px solid #27ae60;
        }

        .skill-level-icon {
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-main {
                padding: 0 20px 20px;
            }

            .profile-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <?php
    $current_page = 'profile.php';
    include '../includes/navbar.php';
    ?>

    <div class="profile-container">

        <!-- Header card -->
        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-main">
                <div style="display:flex; gap:20px; align-items:center;">

                    <div class="profile-info">
                        <div class="profile-name">
                            <?php echo htmlspecialchars(ucwords(($profile['first_name'] ?? 'Unknown') . ' ' . ($profile['last_name'] ?? ''))); ?>
                        </div>
                        <div class="profile-title">
                            <?php
                            $titleParts = [];
                            if (!empty($profile['current_position'])) {
                                $titleParts[] = ucwords($profile['current_position']);
                            }
                            if (!empty($profile['company'])) {
                                $titleParts[] = ucwords($profile['company']);
                            }
                            echo htmlspecialchars(implode(' at ', $titleParts));
                            ?>
                        </div>
                        <div class="profile-title">
                            <?php
                            $sub = [];
                            if (!empty($profile['major'])) {
                                $sub[] = ucwords($profile['major']);
                            }

                            if (!empty($profile['graduation_year'])) {
                                $sub[] = 'Class of ' . $profile['graduation_year'];
                            }
                            echo htmlspecialchars(implode(' • ', $sub));
                            ?>
                        </div>
                        <?php if ($is_own_profile): ?>
                            <div class="profile-actions">
                                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                                <a href="manage_skills.php" class="btn btn-outline">Manage Skills</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Basic info -->
        <div class="info-section">
            <div class="section-title">
                <span>Contact & Account</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Member Since</div>
                    <div class="info-value">
                        <?php echo function_exists('formatDate') ? formatDate($profile['registration_date']) : htmlspecialchars($profile['registration_date']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic / professional -->
        <div class="info-section">
            <div class="section-title">
                <span>Academic & Professional</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Major</div>
                    <div class="info-value"><?php echo htmlspecialchars(ucwords($profile['major'])); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Graduation Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['graduation_year']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Role</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars(ucwords(trim($profile['current_position'] . ' ' . $profile['company']))); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($profile['location'] ?? 'Not specified'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio -->
        <div class="info-section">
            <div class="section-title">
                <span>About</span>
            </div>
            <p class="info-value">
                <?php
                echo !empty($profile['bio'])
                    ? nl2br(htmlspecialchars($profile['bio']))
                    : 'No bio added yet.';
                ?>
            </p>
        </div>

        <!-- Skills placeholder -->
        <div class="info-section">
            <div class="section-title">
                <span>Skills</span>
                <?php if ($is_own_profile): ?>
                    <a href="manage_skills.php"
                        style="font-size:14px; color:var(--primary-color); text-decoration:none;">Add New +</a>
                <?php endif; ?>
            </div>
            <div class="skills-grid">
                <?php if (count($skills) > 0): ?>
                    <?php foreach ($skills as $skill): ?>
                        <?php
                        $level = $skill['proficiency_level'] ?? 'beginner';
                        $badgeClass = getSkillBadgeClass($level);
                        $dots = match (strtolower($level)) {
                            'advanced' => '●●●',
                            'intermediate' => '●●',
                            default => '●'
                        };
                        ?>
                        <span class="skill-badge <?php echo htmlspecialchars($badgeClass); ?>">
                            <span class="skill-level-icon"><?php echo $dots; ?></span>
                            <?php echo htmlspecialchars(ucwords($skill['skill_name'])); ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted" style="color:#999; font-style:italic;">
                        No skills added yet. Click "Manage Skills" to add some!
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JS at bottom so DOM is ready -->
    <script src="../assets/js/main.js"></script>
</body>

</html>