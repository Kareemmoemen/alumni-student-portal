<?php
// Load required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user info
$user_id   = getUserId();
$user_type = getUserType();

// Create DB connection
$database = new Database();
$conn     = $database->getConnection();

// Get user profile + account info
$query = "SELECT p.*, u.email, u.registration_date
          FROM profiles p
          INNER JOIN users u ON p.user_id = u.user_id
          WHERE p.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Simple fallback if profile is missing
if (!$profile) {
    $profile = [
        'first_name'        => 'Unknown',
        'last_name'         => '',
        'email'             => getUserEmail(),
        'major'             => '',
        'university'        => '',
        'graduation_year'   => '',
        'current_position'  => '',
        'company'           => '',
        'bio'               => '',
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
            background: white;
            border-radius: 15px;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-banner {
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .profile-main {
            padding: 0 40px 40px;
            margin-top: -60px;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            border: 5px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            margin-top: 20px;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .profile-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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

    <!-- Navbar (same style as other pages) -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">Alumni Portal</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="matching.php">Matching</a></li>
                <li><a href="forum.php">Forum</a></li>
                <li><a href="jobs.php">Jobs</a></li>
                <li><a href="events.php">Events</a></li>
                <li>
                    <span class="badge badge-secondary">
                        <?php echo ucfirst($user_type); ?>
                    </span>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="profile-container">

        <!-- Header card -->
        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-main">
                <div style="display:flex; gap:20px; align-items:center;">
                    <div class="profile-avatar">
                        <?php
                        $initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
                        echo htmlspecialchars($initials);
                        ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name">
                            <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </div>
                        <div class="profile-title">
                            <?php
                            $titleParts = [];
                            if (!empty($profile['current_position'])) {
                                $titleParts[] = $profile['current_position'];
                            }
                            if (!empty($profile['company'])) {
                                $titleParts[] = $profile['company'];
                            }
                            echo htmlspecialchars(implode(' at ', $titleParts));
                            ?>
                        </div>
                        <div class="profile-title">
                            <?php
                            $sub = [];
                            if (!empty($profile['major'])) {
                                $sub[] = $profile['major'];
                            }
                            if (!empty($profile['university'])) {
                                $sub[] = $profile['university'];
                            }
                            if (!empty($profile['graduation_year'])) {
                                $sub[] = 'Class of ' . $profile['graduation_year'];
                            }
                            echo htmlspecialchars(implode(' • ', $sub));
                            ?>
                        </div>
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                            <a href="manage_skills.php" class="btn btn-outline">Manage Skills</a>
                        </div>
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
                    <div class="info-value"><?php echo htmlspecialchars($profile['major']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">University</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['university']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Graduation Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($profile['graduation_year']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Role</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars(trim($profile['current_position'] . ' ' . $profile['company'])); ?>
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

        <!-- Skills placeholder (you can hook into your skills table later) -->
        <div class="info-section">
            <div class="section-title">
                <span>Skills</span>
            </div>
            <div class="skills-grid">
                <!-- Example static skills; later replace with skills from DB -->
                <span class="skill-badge skill-beginner">
                    <span class="skill-level-icon">●</span> Sample Beginner Skill
                </span>
                <span class="skill-badge skill-intermediate">
                    <span class="skill-level-icon">●●</span> Sample Intermediate Skill
                </span>
                <span class="skill-badge skill-advanced">
                    <span class="skill-level-icon">●●●</span> Sample Advanced Skill
                </span>
            </div>
        </div>
    </div>

</body>
</html>
