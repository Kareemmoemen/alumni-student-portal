<?php
// FILE: pages/my_jobs.php
// Alumni can view, edit, and close their job postings

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireUserType('alumni');

$user_id = getUserId();

$database = new Database();
$conn = $database->getConnection();

// Handle close job action
if (isset($_POST['close_job'])) {
    $job_id = (int) $_POST['job_id'];

    $query = "UPDATE jobs SET status = 'closed' WHERE job_id = :job_id AND posted_by = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    setSuccess("Job closed successfully!");
    header("Location: my_jobs.php");
    exit();
}

// Get all jobs posted by this user
$query = "SELECT * FROM jobs WHERE posted_by = :user_id ORDER BY posted_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$my_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate by status
$active_jobs = array_filter($my_jobs, fn($j) => $j['status'] === 'active');
$closed_jobs = array_filter($my_jobs, fn($j) => $j['status'] === 'closed');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Alumni Portal</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
</head>

<body>

    <?php
    $current_page = 'my_jobs.php';
    require_once '../includes/navbar.php';
    ?>

    <div class="my-jobs-container">
        <?php if ($success = getSuccess()): ?>
            <div class="alert alert-success animate-fade-in"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="page-header animate-fade-in-up">
            <div>
                <h1>My Job Postings</h1>
                <p>Manage your job opportunities</p>
            </div>
            <a href="post_job.php" class="btn btn-primary ripple-effect">Post New Job</a>
        </div>

        <div class="tabs">
            <button class="tab active ripple-effect" onclick="showTab('active', this)">
                Active (<?php echo count($active_jobs); ?>)
            </button>
            <button class="tab ripple-effect" onclick="showTab('closed', this)">
                Closed (<?php echo count($closed_jobs); ?>)
            </button>
        </div>

        <!-- Active Jobs -->
        <div id="active" class="tab-content active">
            <?php if (count($active_jobs) > 0): ?>
                <?php foreach ($active_jobs as $job): ?>
                    <div class="job-item scroll-reveal hover-lift">
                        <div class="job-item-header">
                            <div>
                                <div class="job-item-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                <div class="job-item-meta">
                                    <span><?php echo htmlspecialchars($job['company']); ?></span>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                    <span>Posted <?php echo timeAgo($job['posted_date']); ?></span>
                                </div>
                            </div>

                            <div class="job-item-actions">
                                <a href="job_details.php?id=<?php echo (int) $job['job_id']; ?>"
                                    class="btn btn-primary btn-sm ripple-effect">
                                    View
                                </a>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="job_id" value="<?php echo (int) $job['job_id']; ?>">
                                    <input type="hidden" name="close_job" value="1">
                                    <button type="submit" class="btn-close ripple-effect"
                                        onclick="return confirm('Close this job posting?')">
                                        Close Job
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;">📄</div>
                    <h3>No Active Jobs</h3>
                    <p>Post your first job to help students find opportunities</p>
                    <a href="post_job.php" class="btn btn-primary ripple-effect" style="margin-top: 20px;">Post a Job</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Closed Jobs -->
        <div id="closed" class="tab-content">
            <?php if (count($closed_jobs) > 0): ?>
                <?php foreach ($closed_jobs as $job): ?>
                    <div class="job-item scroll-reveal" style="opacity: 0.7;">
                        <div class="job-item-header">
                            <div>
                                <div class="job-item-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                <div class="job-item-meta">
                                    <span><?php echo htmlspecialchars($job['company']); ?></span>
                                    <span>Posted <?php echo timeAgo($job['posted_date']); ?></span>
                                </div>
                            </div>
                            <span class="badge badge-secondary">Closed</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No closed jobs</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/animations.js"></script>
    <script>
        function showTab(tabName, btnEl) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));

            const target = document.getElementById(tabName);
            if (target) target.classList.add('active');
            if (btnEl) btnEl.classList.add('active');
        }
    </script>

    <style>
        /* Minimal page-specific CSS kept from guide to avoid redesign */
        body {
            background: #f5f7fa;
        }

        .my-jobs-container {
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
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .job-item {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .job-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .job-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .job-item-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .job-item-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .job-item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-close {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }
    </style>

</body>

</html>