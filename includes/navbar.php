<?php
// FILE: includes/navbar.php
// DESCRIPTION: Reusable navbar component based on matching.php design
// Standardized active state logic and mobile responsiveness

// Ensure current page is determined
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

// Ensure user_type is set (should be from session or page)
if (!isset($user_type) && isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
}
?>
<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-container">
        <a href="../index.php" class="navbar-brand">Alumni Portal</a>

        <!-- Mobile Menu Toggle (required for responsiveness) -->
        <button class="mobile-menu-toggle">☰</button>

        <ul class="navbar-menu">
            <li><a href="dashboard.php"
                    class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">Profile</a>
            </li>
            <li><a href="matching.php"
                    class="<?php echo $current_page === 'matching.php' ? 'active' : ''; ?>">Matching</a></li>

            <?php if ($user_type === 'admin'): ?>
                <li><a href="all_connections.php"
                        class="<?php echo $current_page === 'all_connections.php' ? 'active' : ''; ?>">All Connections</a>
                </li>
            <?php endif; ?>

            <?php if ($user_type !== 'admin'): ?>
                <li><a href="my_mentorships.php"
                        class="<?php echo $current_page === 'my_mentorships.php' ? 'active' : ''; ?>">My Connections</a>
                </li>
            <?php endif; ?>

            <li><span class="badge badge-secondary"><?php echo ucfirst($user_type ?? 'User'); ?></span></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Mobile Overlay -->
<div class="mobile-overlay"></div>