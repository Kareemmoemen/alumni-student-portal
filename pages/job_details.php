<?php
// FILE: pages/job_details.php
// DESCRIPTION: View a single job posting details (with owner controls)

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

$database = new Database();
$conn = $database->getConnection();

// Validate ID
$job_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($job_id <= 0) {
    $job = null;
} else {
    // Fetch job + poster info
    // Fetch job + poster info (including email from users table)
    $query = "SELECT j.*, p.first_name, p.last_name, p.profile_picture, p.company AS poster_company, u.email
              FROM jobs j
              INNER JOIN profiles p ON j.posted_by = p.user_id
              INNER JOIN users u ON p.user_id = u.user_id
              WHERE j.job_id = :job_id
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If not found
if (!$job) {
    $not_found = true;
} else {
    $not_found = false;
}

// Owner check
$is_owner = ($job && (int) $job['posted_by'] === (int) $user_id);

// Closed job visibility rule
if ($job && $job['status'] !== 'active' && !$is_owner) {
    $not_available = true;
} else {
    $not_available = false;
}

// Handle owner actions: close/reopen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job && $is_owner && $user_type === 'alumni') {
    if (isset($_POST['close_job'])) {
        $q = "UPDATE jobs SET status = 'closed' WHERE job_id = :job_id AND posted_by = :user_id";
        $s = $conn->prepare($q);
        $s->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $s->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $s->execute();

        setSuccess("Job closed successfully!");
        header("Location: my_jobs.php");
        exit();
    }

    if (isset($_POST['reopen_job'])) {
        $q = "UPDATE jobs SET status = 'active' WHERE job_id = :job_id AND posted_by = :user_id";
        $s = $conn->prepare($q);
        $s->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $s->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $s->execute();

        setSuccess("Job reopened successfully!");
        header("Location: my_jobs.php");
        exit();
    }
}

// Compute deadline badge logic
$deadline_ts = null;
$days_left = null;
$is_urgent = false;
$is_expired = false;

if ($job && !empty($job['application_deadline'])) {
    $deadline_ts = strtotime($job['application_deadline']);
    if ($deadline_ts) {
        $days_left = (int) ceil(($deadline_ts - time()) / (60 * 60 * 24));
        $is_urgent = ($days_left <= 7);
        $is_expired = ($days_left < 0);
    }
}

$apply_link = ($job && !empty($job['email'])) ? 'mailto:' . $job['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .top-bar {
            background: white;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }

        .top-title h1 {
            margin: 0 0 6px 0;
            color: #2c3e50;
            font-size: 26px;
        }

        .top-title p {
            margin: 0;
            color: #666;
        }

        .btn-back {
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            background: #eef1f5;
            color: #2c3e50;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 18px;
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .badge-pill {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eef1f5;
            color: #2c3e50;
        }

        .badge-status-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-status-closed {
            background: #e2e3e5;
            color: #383d41;
        }

        .badge-deadline {
            background: #fff3cd;
            color: #856404;
        }

        .badge-urgent {
            background: #f8d7da;
            color: #721c24;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .meta-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
        }

        .meta-item .k {
            font-size: 12px;
            color: #777;
            margin-bottom: 6px;
        }

        .meta-item .v {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
        }

        .section-title {
            font-size: 14px;
            font-weight: 800;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }

        .text {
            color: #555;
            line-height: 1.7;
            font-size: 14px;
            white-space: pre-wrap;
        }

        .apply-btn {
            width: 100%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            padding: 12px 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .apply-btn.primary {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .apply-btn.disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        .small-note {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
        }

        .owner-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .owner-actions form {
            flex: 1;
        }

        .btn-danger {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-radius: 10px;
            background: #dc3545;
            color: white;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-success {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-radius: 10px;
            background: #28a745;
            color: white;
            font-weight: 800;
            cursor: pointer;
        }

        /* Fallback loading dots (in case not present in style.css) */
        .loading-dots {
            display: inline-flex;
            gap: 5px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'jobs.php'; // Keeping 'jobs.php' as active context for details page
    require_once '../includes/navbar.php';
    ?>

    <div class="wrap">
        <?php $success = getSuccess();
        if ($success): ?>
            <div class="alert alert-success animate-fade-in"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="top-bar animate-fade-in-up">
            <div class="top-title">
                <?php if ($not_found): ?>
                    <h1>Job Not Found</h1>
                    <p>The job you’re looking for doesn’t exist or the link is invalid.</p>
                <?php elseif ($not_available): ?>
                    <h1>Job Not Available</h1>
                    <p>This posting is closed or no longer public.</p>
                <?php else: ?>
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p>🏢 <?php echo htmlspecialchars($job['company']); ?> • 📍
                        <?php echo htmlspecialchars($job['location']); ?>
                    </p>

                    <div class="badges">
                        <span
                            class="badge-pill <?php echo $job['status'] === 'active' ? 'badge-status-active' : 'badge-status-closed'; ?>">
                            <?php echo ucfirst($job['status']); ?>
                        </span>

                        <?php if ($deadline_ts): ?>
                            <?php if ($is_expired): ?>
                                <span class="badge-pill badge-urgent">Expired</span>
                            <?php else: ?>
                                <span class="badge-pill <?php echo $is_urgent ? 'badge-urgent' : 'badge-deadline'; ?>">
                                    <?php echo (int) $days_left; ?> day<?php echo ((int) $days_left !== 1) ? 's' : ''; ?> left
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <a class="btn-back ripple-effect hover-lift" href="jobs.php">← Back to Jobs</a>
        </div>

        <?php if ($not_found || $not_available): ?>
            <div class="card scroll-reveal">
                <p style="margin:0; color:#666;">You can return to the job board to browse available opportunities.</p>
                <div style="margin-top:14px;">
                    <a href="jobs.php" class="btn btn-primary btn-modern ripple-effect hover-glow">Go to Job Board</a>
                </div>
            </div>
        <?php else: ?>
            <div class="layout">
                <!-- Main job details -->
                <div class="card scroll-reveal hover-lift">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="k">Job Type</div>
                            <div class="v"><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="k">Posted</div>
                            <div class="v"><?php echo timeAgo($job['posted_date']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="k">Salary</div>
                            <div class="v">
                                <?php echo !empty($job['salary_range']) ? htmlspecialchars($job['salary_range']) : 'Not specified'; ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="k">Deadline</div>
                            <div class="v">
                                <?php echo !empty($job['application_deadline']) ? htmlspecialchars($job['application_deadline']) : 'Not specified'; ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:18px;">
                        <p class="section-title">Description</p>
                        <div class="text"><?php echo htmlspecialchars($job['description']); ?></div>
                    </div>

                    <?php if (!empty($job['requirements'])): ?>
                        <div style="margin-top:18px;">
                            <p class="section-title">Requirements</p>
                            <div class="text"><?php echo htmlspecialchars($job['requirements']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="card scroll-reveal hover-lift">
                    <p class="section-title">Apply</p>

                    <?php if (!empty($apply_link) && !$is_expired && $job['status'] === 'active'): ?>
                        <a href="<?php echo htmlspecialchars($apply_link); ?>"
                            class="apply-btn primary btn-modern ripple-effect hover-glow" target="_blank"
                            rel="noopener noreferrer">
                            🚀 Apply Now
                        </a>
                        <div class="small-note">Opens in a new tab.</div>
                    <?php else: ?>
                        <div class="apply-btn disabled">
                            🚀 Apply Now
                        </div>
                        <div class="small-note">
                            <?php if ($job['status'] !== 'active'): ?>
                                This job is closed.
                            <?php elseif ($is_expired): ?>
                                The application deadline has passed.
                            <?php else: ?>
                                No email provided. Contact the poster.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:18px;">
                        <p class="section-title">Posted by</p>
                        <div style="display:flex; gap:12px; align-items:center;">
                            <div class="poster-avatar" style="width:44px;height:44px;font-size:16px;">
                                <?php echo strtoupper(substr($job['first_name'], 0, 1) . substr($job['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight:800;color:#2c3e50;">
                                    <?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?>
                                </div>
                                <div style="font-size:12px;color:#888;">
                                    <?php
                                    $pc = !empty($job['poster_company']) ? $job['poster_company'] : '';
                                    echo $pc ? htmlspecialchars($pc) : 'Alumni Member';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_owner && $user_type === 'alumni'): ?>
                        <div style="margin-top:18px;">
                            <p class="section-title">Owner Actions</p>

                            <div class="owner-actions">
                                <?php if ($job['status'] === 'active'): ?>
                                    <form method="POST" class="owner-action-form">
                                        <input type="hidden" name="close_job" value="1">
                                        <button type="submit" class="btn-danger btn-modern ripple-effect hover-glow"
                                            onclick="return confirm('Close this job posting?');">
                                            Close Job
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="owner-action-form">
                                        <input type="hidden" name="reopen_job" value="1">
                                        <button type="submit" class="btn-success btn-modern ripple-effect hover-glow"
                                            onclick="return confirm('Reopen this job posting?');">
                                            Reopen Job
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="small-note">Changes will reflect on the job board immediately.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Minimal loading state for owner action buttons
        document.querySelectorAll('.owner-action-form').forEach(form => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button');
                if (!btn) return;
                btn.disabled = true;
                btn.innerHTML = '<div class="loading-dots"><span></span><span></span><span></span></div>';
            });
        });
    </script>
</body>

</html>