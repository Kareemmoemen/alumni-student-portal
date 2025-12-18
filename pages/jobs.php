<?php
// FILE: pages/jobs.php
// DESCRIPTION: Main jobs board - view all jobs, filter, search

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = getUserId();
$user_type = getUserType();

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search_keyword = isset($_GET['keyword']) ? sanitizeInput($_GET['keyword']) : '';
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$filter_location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Build query
$query = "SELECT j.*, p.first_name, p.last_name, p.profile_picture, p.company as poster_company
         FROM jobs j
         INNER JOIN profiles p ON j.posted_by = p.user_id
         WHERE j.status = 'active'";

// Add keyword search
if (!empty($search_keyword)) {
    $query .= " AND (j.title LIKE :keyword OR j.description LIKE :keyword OR j.company LIKE :keyword)";
}

// Add filters
if (!empty($filter_type)) {
    $query .= " AND j.job_type = :job_type";
}

if (!empty($filter_location)) {
    $query .= " AND j.location LIKE :location";
}

// Add sorting
switch ($sort_by) {
    case 'newest':
        $query .= " ORDER BY j.posted_date DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY j.posted_date ASC";
        break;
    case 'deadline':
        $query .= " ORDER BY j.application_deadline ASC";
        break;
}

$stmt = $conn->prepare($query);

if (!empty($search_keyword)) {
    $keyword_param = "%{$search_keyword}%";
    $stmt->bindParam(':keyword', $keyword_param);
}
if (!empty($filter_type)) {
    $stmt->bindParam(':job_type', $filter_type);
}
if (!empty($filter_location)) {
    $location_param = "%{$filter_location}%";
    $stmt->bindParam(':location', $location_param);
}

$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique locations for filter
$query = "SELECT DISTINCT location FROM jobs WHERE location IS NOT NULL AND location != '' ORDER BY location";
$stmt = $conn->prepare($query);
$stmt->execute();
$all_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_jobs = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$my_jobs = 0;
if ($user_type === 'alumni') {
    $query = "SELECT COUNT(*) as total FROM jobs WHERE posted_by = :user_id AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_jobs = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Safe unique company count (non-empty)
$company_values = array_filter(array_map(
    function ($row) {
        return isset($row['company']) ? trim((string) $row['company']) : '';
    },
    $jobs
), function ($c) {
    return $c !== '';
});
$unique_company_count = count(array_unique($company_values));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Board - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }

        .jobs-container {
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

        .header-actions {
            display: flex;
            gap: 15px;
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
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0 15px;
        }

        .search-input input {
            flex: 1;
            border: none;
            background: none;
            padding: 12px 0;
            font-size: 14px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .jobs-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            gap: 20px;
            position: relative;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .job-company-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .job-content {
            flex: 1;
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .job-title {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .job-company {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #666;
        }

        .job-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .tag {
            padding: 5px 12px;
            background: #e8f0fe;
            color: #1967d2;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .job-poster {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .poster-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .poster-info {
            font-size: 13px;
        }

        .poster-name {
            color: #2c3e50;
            font-weight: 600;
        }

        .posted-date {
            color: #999;
            font-size: 12px;
        }

        .job-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }

        .deadline-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            background: #fff3cd;
            color: #856404;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .deadline-badge.urgent {
            background: #f8d7da;
            color: #721c24;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }

        .no-results-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .job-card {
                flex-direction: column;
            }

            .filters-grid {
                grid-template-columns: 1fr;
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
    <?php
    $current_page = 'jobs.php';
    require_once '../includes/navbar.php';
    ?>

    <div class="jobs-container">
        <?php
        $success = getSuccess();
        if ($success):
            ?>
            <div class="alert alert-success animate-fade-in"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header animate-fade-in-up">
            <div class="header-content">
                <h1><i class="fas fa-briefcase"></i> Job Opportunities</h1>
                <p>Discover career opportunities posted by alumni</p>
            </div>
            <?php if ($user_type === 'alumni'): ?>
                <div class="header-actions">
                    <a href="post_job.php" class="btn btn-primary btn-modern ripple-effect">
                        <i class="fas fa-pen-to-square"></i> Post a Job
                    </a>
                    <a href="my_jobs.php" class="btn btn-secondary">
                        <i class="fas fa-clipboard-list"></i> My Jobs (<?php echo $my_jobs; ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Bar -->
        <div class="stats-bar animate-fade-in-up delay-100">
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-briefcase" style="color: white;"></i></div>
                <div class="stat-info">
                    <h3 class="counter" data-target="<?php echo $total_jobs; ?>">0</h3>
                    <p>Active Jobs</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-building" style="color: white;"></i></div>
                <div class="stat-info">
                    <h3><?php echo $unique_company_count; ?></h3>
                    <p>Companies</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-map-marker-alt" style="color: white;"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($all_locations); ?></h3>
                    <p>Locations</p>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="filters-section animate-fade-in-up delay-200">
            <form method="GET" action="">
                <div class="search-bar">
                    <div class="search-input">
                        <span style="color: #999;"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" class="input-focus-glow"
                            placeholder="Search jobs by title, company, or description..."
                            value="<?php echo htmlspecialchars($search_keyword); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary ripple-effect">Search</button>
                </div>

                <div class="filters-grid">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="type">Job Type</label>
                        <select id="type" name="type" class="form-control input-focus-glow">
                            <option value="">All Types</option>
                            <option value="full-time" <?php echo $filter_type === 'full-time' ? 'selected' : ''; ?>>
                                Full-Time</option>
                            <option value="part-time" <?php echo $filter_type === 'part-time' ? 'selected' : ''; ?>>
                                Part-Time</option>
                            <option value="internship" <?php echo $filter_type === 'internship' ? 'selected' : ''; ?>>
                                Internship</option>
                            <option value="contract" <?php echo $filter_type === 'contract' ? 'selected' : ''; ?>>Contract
                            </option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="location">Location</label>
                        <select id="location" name="location" class="form-control input-focus-glow">
                            <option value="">All Locations</option>
                            <?php foreach ($all_locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location']); ?>" <?php echo $filter_location === $location['location'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control input-focus-glow">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First
                            </option>
                            <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First
                            </option>
                            <option value="deadline" <?php echo $sort_by === 'deadline' ? 'selected' : ''; ?>>Deadline
                                Soon</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <a href="jobs.php" class="btn btn-secondary" style="width: 100%;">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Jobs List -->
        <?php if (count($jobs) > 0): ?>
            <div class="jobs-layout stagger-animation">
                <?php foreach ($jobs as $job):
                    $deadline_raw = isset($job['application_deadline']) ? trim((string) $job['application_deadline']) : '';
                    $deadline_ts = $deadline_raw !== '' ? strtotime($deadline_raw) : false;

                    $days_left = null;
                    $is_urgent = false;

                    if ($deadline_ts && $deadline_ts > time()) {
                        $days_left = (int) ceil(($deadline_ts - time()) / (60 * 60 * 24));
                        $is_urgent = ($days_left <= 7);
                    }

                    $company_name = isset($job['company']) ? trim((string) $job['company']) : '';
                    $logo_text = $company_name !== '' ? strtoupper(substr($company_name, 0, 2)) : 'JB';

                    $desc = isset($job['description']) ? (string) $job['description'] : '';
                    $short_desc = mb_substr($desc, 0, 200, 'UTF-8');
                    $desc_is_long = (mb_strlen($desc, 'UTF-8') > 200);

                    $first = isset($job['first_name']) ? trim((string) $job['first_name']) : '';
                    $last = isset($job['last_name']) ? trim((string) $job['last_name']) : '';
                    $initials = '';
                    if ($first !== '')
                        $initials .= mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8');
                    if ($last !== '')
                        $initials .= mb_strtoupper(mb_substr($last, 0, 1, 'UTF-8'), 'UTF-8');
                    if ($initials === '')
                        $initials = '?';

                    $full_name = trim($first . ' ' . $last);
                    if ($full_name === '')
                        $full_name = 'Unknown';
                    ?>
                    <div class="job-card scroll-reveal hover-lift">
                        <?php if ($days_left !== null && $days_left > 0): ?>
                            <div class="deadline-badge <?php echo $is_urgent ? 'urgent' : ''; ?>">
                                <?php echo $days_left; ?> day<?php echo $days_left != 1 ? 's' : ''; ?> left
                            </div>
                        <?php endif; ?>

                        <div class="job-company-logo">
                            <?php echo htmlspecialchars($logo_text); ?>
                        </div>

                        <div class="job-content">
                            <div class="job-header">
                                <div>
                                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <div class="job-company"><i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($job['company']); ?></div>
                                </div>
                            </div>

                            <div class="job-meta">
                                <div class="meta-item">
                                    <span><i class="fas fa-map-marker-alt"></i></span>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span><i class="fas fa-briefcase"></i></span>
                                    <span><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></span>
                                </div>
                                <?php if (!empty($job['salary_range'])): ?>
                                    <div class="meta-item">
                                        <span><i class="fas fa-money-bill-wave"></i></span>
                                        <span><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="job-description">
                                <?php echo nl2br(htmlspecialchars($short_desc)); ?>
                                <?php if ($desc_is_long): ?>...<?php endif; ?>
                            </div>

                            <div class="job-footer">
                                <div class="job-poster">
                                    <div class="poster-avatar">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div class="poster-info">
                                        <div class="poster-name"><?php echo htmlspecialchars($full_name); ?></div>
                                        <div class="posted-date">Posted <?php echo timeAgo($job['posted_date']); ?></div>
                                    </div>
                                </div>

                                <div class="job-actions">
                                    <a href="job_details.php?id=<?php echo (int) $job['job_id']; ?>"
                                        class="btn-view ripple-effect">
                                        View Details →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon"><i class="fas fa-search"></i></div>
                <h3>No Jobs Found</h3>
                <p>Try adjusting your search filters or check back later for new opportunities.</p>
                <?php if ($user_type === 'alumni'): ?>
                    <a href="post_job.php" class="btn btn-primary" style="margin-top: 20px;">Post the First Job</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/animations.js"></script>
</body>

</html>