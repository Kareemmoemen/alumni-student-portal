<?php
// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Get platform statistics
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'alumni' AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_alumni = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM mentorship_matches WHERE status IN ('active', 'accepted')";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_connections = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent events (top 3)
$query = "SELECT e.*, u.user_type, p.first_name, p.last_name 
 FROM events e
 INNER JOIN users u ON e.created_by = u.user_id
 INNER JOIN profiles p ON e.created_by = p.user_id
 WHERE e.event_date >= NOW()
 ORDER BY e.event_date ASC
 LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent jobs (top 3)
$query = "SELECT j.*, p.first_name, p.last_name, p.company
 FROM jobs j
 INNER JOIN profiles p ON j.posted_by = p.user_id
 WHERE j.status = 'active' AND j.application_deadline >= CURDATE()
 ORDER BY j.posted_date DESC
 LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni-Student Connection Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: white;
            animation: fadeIn 1s ease;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero .btn {
            padding: 15px 40px;
            font-size: 18px;
        }

        .btn-white {
            background: white;
            color: #667eea;
        }

        .btn-white:hover {
            background: #f8f9fa;
        }

        /* Stats Section */
        .stats-section {
            padding: 60px 20px;
            background: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Features Section */
        .features-section {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #2c3e50;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Recent Content Section */
        .recent-section {
            padding: 80px 20px;
            background: white;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 40px auto 0;
        }

        .content-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .content-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .content-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .content-meta {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }

        .content-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: white;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .stat-number {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand"> Alumni Portal</a>
            <ul class="navbar-menu">
                <li><a href="pages/login.php">Login</a></li>
                <li><a href="pages/register.php" class="btn btn-primary btn-sm">Get Started</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Connect. Learn. Grow.</h1>
            <p>Bridge the gap between students and alumni. Find mentors, explore opportunities, and build meaningful connections.</p>
            <div class="hero-buttons">
                <a href="pages/register.php" class="btn btn-white btn-lg">Join Now</a>
                <a href="#features" class="btn btn-outline btn-lg" style="color: white; border-color: white;">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_alumni; ?></div>
                <div class="stat-label">Alumni Network</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_connections; ?></div>
                <div class="stat-label">Connections Made</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <h2 class="section-title">What We Offer</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Mentorship Matching</h3>
                <p>Connect students with experienced alumni based on shared interests, skills, and career goals.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Discussion Forum</h3>
                <p>Ask questions, share experiences, and engage in meaningful conversations with the community.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Job Opportunities</h3>
                <p>Discover internships and job openings posted by alumni and industry professionals.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Networking Events</h3>
                <p>Attend workshops, seminars, and networking events to expand your professional network.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Career Resources</h3>
                <p>Access career guidance, resume tips, and interview preparation from experienced professionals.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3>Skill Development</h3>
                <p>Showcase your skills and learn from others' expertise to advance your career.</p>
            </div>
        </div>
    </section>

    <!-- Recent Events -->
    <?php if (count($recent_events) > 0): ?>
        <section class="recent-section">
            <h2 class="section-title">Upcoming Events</h2>
            <div class="content-grid">
                <?php foreach ($recent_events as $event): ?>
                    <div class="content-card">
                        <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                        <div class="content-meta">
                            <?php echo date('F j, Y', strtotime($event['event_date'])); ?> | 
                            <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        <p><?php echo substr(htmlspecialchars($event['description']), 0, 100); ?>...</p>
                        <span class="badge badge-primary"><?php echo ucfirst($event['event_type']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Recent Jobs -->
    <?php if (count($recent_jobs) > 0): ?>
        <section class="recent-section" style="background: #f8f9fa;">
            <h2 class="section-title">Latest Job Opportunities</h2>
            <div class="content-grid">
                <?php foreach ($recent_jobs as $job): ?>
                    <div class="content-card">
                        <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                        <div class="content-meta">
                            <?php echo htmlspecialchars($job['company']); ?> | 
                            <?php echo htmlspecialchars($job['location']); ?>
                        </div>
                        <p><?php echo substr(htmlspecialchars($job['description']), 0, 100); ?>...</p>
                        <span class="badge badge-success">
                            <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join our community of students and alumni today!</p>
            <a href="pages/register.php" class="btn btn-white btn-lg">Create Your Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Alumni-Student Connection Platform. All rights reserved.</p>
            <p>Developed by Kareem Moemen Mounir &amp; Youssef Fouad Hassan</p>
        </div>
    </footer>
</body>
</html>
