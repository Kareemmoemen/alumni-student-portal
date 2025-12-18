<?php
// FILE: pages/post_job.php
// DESCRIPTION: Alumni can post a new job

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireUserType('alumni');

$user_id = getUserId();

$database = new Database();
$conn = $database->getConnection();

$errors = [];
$values = [
    'title' => '',
    'company' => '',
    'location' => '',
    'job_type' => '',
    'salary_range' => '',
    'application_deadline' => '',
    'requirements' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect + sanitize
    foreach ($values as $k => $_) {
        $values[$k] = isset($_POST[$k]) ? sanitizeInput($_POST[$k]) : '';
    }

    // Trim key fields
    $values['title'] = trim($values['title']);
    $values['company'] = trim($values['company']);
    $values['location'] = trim($values['location']);
    $values['job_type'] = trim($values['job_type']);
    $values['salary_range'] = trim($values['salary_range']);
    $values['application_deadline'] = trim($values['application_deadline']);
    $values['requirements'] = trim($values['requirements']);
    $values['description'] = trim($values['description']);

    // Validate required
    if ($values['title'] === '')
        $errors[] = "Job title is required.";
    if ($values['company'] === '')
        $errors[] = "Company is required.";
    if ($values['location'] === '')
        $errors[] = "Location is required.";
    if ($values['description'] === '')
        $errors[] = "Job description is required.";

    // Validate job type
    $allowed_types = ['full-time', 'part-time', 'internship', 'contract'];
    if ($values['job_type'] === '' || !in_array($values['job_type'], $allowed_types, true)) {
        $errors[] = "Job type must be one of: Full-Time, Part-Time, Internship, Contract.";
    }

    // Validate length (soft limits)
    if (mb_strlen($values['title'], 'UTF-8') > 120)
        $errors[] = "Job title must be 120 characters or less.";
    if (mb_strlen($values['company'], 'UTF-8') > 120)
        $errors[] = "Company must be 120 characters or less.";
    if (mb_strlen($values['location'], 'UTF-8') > 120)
        $errors[] = "Location must be 120 characters or less.";
    if ($values['salary_range'] !== '' && mb_strlen($values['salary_range'], 'UTF-8') > 120)
        $errors[] = "Salary range must be 120 characters or less.";
    if ($values['application_link'] !== '' && mb_strlen($values['application_link'], 'UTF-8') > 255)
        $errors[] = "Application link must be 255 characters or less.";

    // Validate deadline
    if ($values['application_deadline'] !== '') {
        $ts = strtotime($values['application_deadline']);
        if (!$ts) {
            $errors[] = "Application deadline must be a valid date.";
        } else {
            $today = strtotime(date('Y-m-d'));
            if ($ts < $today)
                $errors[] = "Application deadline cannot be in the past.";
        }
    }

    // Validate URL (REMOVED: application_link column missing)

    // Insert if valid
    if (empty($errors)) {
        // NOTE: This INSERT assumes your jobs table contains the columns used by jobs.php:
        // title, company, location, job_type, salary_range, description, requirements, application_deadline, application_link,
        // posted_by, status, posted_date
        $query = "INSERT INTO jobs
            (posted_by, title, company, location, job_type, salary_range, description, requirements, application_deadline, status, posted_date)
            VALUES
            (:posted_by, :title, :company, :location, :job_type, :salary_range, :description, :requirements, :application_deadline, 'active', NOW())";

        $stmt = $conn->prepare($query);

        $deadline_param = ($values['application_deadline'] !== '') ? $values['application_deadline'] : null;
        $salary_param = ($values['salary_range'] !== '') ? $values['salary_range'] : null;
        $requirements_param = ($values['requirements'] !== '') ? $values['requirements'] : null;

        $stmt->bindParam(':posted_by', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $values['title']);
        $stmt->bindParam(':company', $values['company']);
        $stmt->bindParam(':location', $values['location']);
        $stmt->bindParam(':job_type', $values['job_type']);
        $stmt->bindParam(':salary_range', $salary_param);
        $stmt->bindParam(':description', $values['description']);
        $stmt->bindParam(':requirements', $requirements_param);
        $stmt->bindParam(':application_deadline', $deadline_param);
        // :application_link Removed

        try {
            $stmt->execute();
            setSuccess("Job posted successfully!");
            header("Location: jobs.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .page-wrap {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header-card {
            background: white;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .header-card h1 {
            margin: 0 0 6px 0;
            color: #2c3e50;
        }

        .header-card p {
            margin: 0;
            color: #666;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            padding: 12px 12px;
            font-size: 14px;
            outline: none;
            background: #fff;
        }

        textarea.form-control {
            min-height: 130px;
            resize: vertical;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-back {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            background: #eef1f5;
            color: #2c3e50;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit {
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 6px;
        }

        .alert {
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'jobs.php'; // Keeping 'jobs.php' as active context for post jobs
    require_once '../includes/navbar.php';
    ?>

    <div class="page-wrap">
        <div class="header-card animate-fade-in-up">
            <div>
                <h1 class="animate-fade-in-up">✏️ Post a Job</h1>
                <p class="animate-fade-in-up delay-100">Share an opportunity with students and fellow alumni.</p>
            </div>
            <a class="btn-back ripple-effect hover-lift" href="jobs.php">← Back to Jobs</a>
        </div>

        <div class="form-card animate-fade-in-up delay-100">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger animate-fade-in">
                    <strong>Please fix the following:</strong>
                    <ul style="margin: 10px 0 0 18px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="postJobForm">
                <div class="grid">
                    <div class="form-group">
                        <label for="title">Job Title *</label>
                        <input id="title" name="title" type="text" class="form-control input-focus-glow"
                            value="<?php echo htmlspecialchars($values['title']); ?>" maxlength="120" required>
                    </div>

                    <div class="form-group">
                        <label for="company">Company *</label>
                        <input id="company" name="company" type="text" class="form-control input-focus-glow"
                            value="<?php echo htmlspecialchars($values['company']); ?>" maxlength="120" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input id="location" name="location" type="text" class="form-control input-focus-glow"
                            value="<?php echo htmlspecialchars($values['location']); ?>" maxlength="120" required>
                    </div>

                    <div class="form-group">
                        <label for="job_type">Job Type *</label>
                        <select id="job_type" name="job_type" class="form-control input-focus-glow" required>
                            <option value="">Select type</option>
                            <option value="full-time" <?php echo $values['job_type'] === 'full-time' ? 'selected' : ''; ?>>
                                Full-Time</option>
                            <option value="part-time" <?php echo $values['job_type'] === 'part-time' ? 'selected' : ''; ?>>
                                Part-Time</option>
                            <option value="internship" <?php echo $values['job_type'] === 'internship' ? 'selected' : ''; ?>>
                                Internship</option>
                            <option value="contract" <?php echo $values['job_type'] === 'contract' ? 'selected' : ''; ?>>
                                Contract</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="salary_range">Salary Range (optional)</label>
                        <input id="salary_range" name="salary_range" type="text" class="form-control input-focus-glow"
                            value="<?php echo htmlspecialchars($values['salary_range']); ?>" maxlength="120"
                            placeholder="e.g., 15k–25k EGP / month">
                    </div>

                    <div class="form-group">
                        <label for="application_deadline">Application Deadline (optional)</label>
                        <input id="application_deadline" name="application_deadline" type="date"
                            class="form-control input-focus-glow"
                            value="<?php echo htmlspecialchars($values['application_deadline']); ?>">
                        <div class="hint">If set, the job board will show “days left”.</div>
                    </div>

                    <!-- Application Link Removed due to DB schema mismatch -->

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="requirements">Requirements (optional)</label>
                        <textarea id="requirements" name="requirements" class="form-control input-focus-glow"
                            placeholder="Bullet points or short paragraph..."><?php echo htmlspecialchars($values['requirements']); ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" class="form-control input-focus-glow" required
                            placeholder="Describe responsibilities, team, benefits, and how to apply..."><?php echo htmlspecialchars($values['description']); ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <a class="btn-back ripple-effect hover-lift" href="jobs.php">← Back to Jobs</a>
                    <button type="submit" id="submitBtn"
                        class="btn btn-primary btn-modern ripple-effect hover-glow btn-submit">
                        Post Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
    <script>
        // Minimal loading state
        const form = document.getElementById('postJobForm');
        const btn = document.getElementById('submitBtn');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.textContent = 'Posting...';
            });
        }
    </script>
</body>

</html>