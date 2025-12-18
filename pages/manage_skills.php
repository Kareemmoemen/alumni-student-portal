<?php
// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user id
$user_id = getUserId();

// Security: Check if 'id' param exists and ensure it matches logged in user
if (isset($_GET['id']) && (int) $_GET['id'] !== $user_id) {
    // User is trying to manage someone else's skills -> Block it
    redirect('profile.php', 'You can only manage your own skills.', 'error');
}

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Handle form submissions (add / delete skill)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // CSRF protection (uses your existing helper)
    if (!verifyCSRFToken($csrfToken)) {
        setError('Invalid request. Please try again.');
        header('Location: manage_skills.php');
        exit();
    }

    if ($action === 'add') {
        $skill_name = sanitizeInput($_POST['skill_name'] ?? '');
        // beginner / intermediate / advanced
        $skill_level = sanitizeInput($_POST['skill_level'] ?? 'beginner');

        if ($skill_name === '') {
            setError('Skill name is required.');
        } else {
            // IMPORTANT: column name must be proficiency_level (see DB schema)
            $query = "INSERT INTO skills (user_id, skill_name, proficiency_level) 
                      VALUES (:user_id, :skill_name, :proficiency_level)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':skill_name', $skill_name, PDO::PARAM_STR);
            $stmt->bindParam(':proficiency_level', $skill_level, PDO::PARAM_STR);

            if ($stmt->execute()) {
                setSuccess('Skill added successfully.');
            } else {
                setError('Could not add skill. Please try again.');
            }
        }

        header('Location: manage_skills.php');
        exit();
    }

    if ($action === 'delete') {
        $skill_id = (int) ($_POST['skill_id'] ?? 0);

        if ($skill_id > 0) {
            $query = "DELETE FROM skills 
                      WHERE skill_id = :skill_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                setSuccess('Skill removed successfully.');
            } else {
                setError('Could not remove skill. Please try again.');
            }
        }

        header('Location: manage_skills.php');
        exit();
    }
}

// Load current skills for this user
$query = "SELECT * FROM skills WHERE user_id = :user_id ORDER BY skill_name";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to map level to class
function getSkillLevelClass(array $skill): string
{
    // Read proficiency_level from DB row
    $level = strtolower($skill['proficiency_level'] ?? 'beginner');
    if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
        $level = 'beginner';
    }
    return $level;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Skills - Alumni Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Main design system -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Page-specific styles -->
    <style>
        .skills-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .add-skill-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .add-skill-card h2 {
            color: white;
            margin-bottom: 20px;
        }

        .add-skill-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .add-skill-form .form-group {
            margin-bottom: 0;
        }

        .add-skill-form .form-group label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .add-skill-form .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: none;
        }

        .add-skill-form .btn {
            padding: 12px 30px;
        }

        .skills-list-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .skills-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .skills-header h2 {
            color: #2c3e50;
            margin: 0;
        }

        .skill-count {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .skills-grid {
            display: grid;
            gap: 15px;
        }

        .skill-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .skill-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .skill-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .skill-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .skill-icon.beginner {
            background: #fff3cd;
        }

        .skill-icon.intermediate {
            background: #cfe2ff;
        }

        .skill-icon.advanced {
            background: #d1e7dd;
        }

        .skill-details {
            flex: 1;
        }

        .skill-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .skill-level-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .skill-level-badge.beginner {
            background: #ffc107;
            color: #856404;
        }

        .skill-level-badge.intermediate {
            background: #3498db;
            color: white;
        }

        .skill-level-badge.advanced {
            background: #27ae60;
            color: white;
        }

        .skill-actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 18px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
        }

        @media(max-width:768px) {
            .add-skill-form {
                grid-template-columns: 1fr;
            }

            .skill-card {
                flex-direction: column;
                gap: 15px;
            }

            .skill-info {
                width: 100%;
            }

            .skill-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>

<body style="background:#f5f7fa;">

    <!-- Loading Overlay (for spinner on submit) -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <?php
    $current_page = 'manage_skills.php';
    require_once '../includes/navbar.php';
    ?>



    <div class="skills-container">
        <?php
        $success = getSuccess();
        $error = getError();
        if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Skill Card -->
        <div class="add-skill-card">
            <h2>Add a New Skill</h2>
            <form method="post" class="add-skill-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">

                <div class="form-group">
                    <label for="skill_name">Skill Name</label>
                    <input type="text" name="skill_name" id="skill_name" class="form-control"
                        placeholder="e.g., Python, Public Speaking">
                </div>

                <div class="form-group">
                    <label for="skill_level">Level</label>
                    <select name="skill_level" id="skill_level" class="form-control">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    Add Skill
                </button>

            </form>
        </div>

        <!-- Skills List -->
        <div class="skills-list-card">
            <div class="skills-header">
                <h2>Your Skills</h2>
                <span class="skill-count">
                    <?php echo count($skills); ?> skill<?php echo count($skills) === 1 ? '' : 's'; ?>
                </span>
            </div>

            <?php if (count($skills) > 0): ?>
                <div class="skills-grid">
                    <?php foreach ($skills as $skill):
                        $level = getSkillLevelClass($skill);
                        ?>
                        <div class="skill-card">
                            <div class="skill-info">
                                <div class="skill-icon <?php echo htmlspecialchars($level); ?>">
                                    <?php echo strtoupper(substr($skill['skill_name'], 0, 1)); ?>
                                </div>
                                <div class="skill-details">
                                    <div class="skill-name">
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    </div>
                                    <div class="skill-level-badge <?php echo htmlspecialchars($level); ?>">
                                        <?php echo ucfirst($level); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="skill-actions">
                                <form method="post" onsubmit="return confirm('Remove this skill?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="skill_id" value="<?php echo (int) $skill['skill_id']; ?>">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                                    <button type="submit" class="btn btn-danger btn-icon">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No skills added yet</h3>
                    <p>Start by adding a few skills above to showcase your strengths.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS at bottom so menu + overlay work -->
    <script src="../assets/js/main.js"></script>
</body>

</html>