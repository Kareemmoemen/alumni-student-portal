<?php
// FILE: includes/navbar.php
// PURPOSE: Single navbar renderer for ALL roles with identical spacing/layout.

if (!function_exists('getUserType')) {
    // If a page includes navbar before functions.php by mistake, fail safely.
    $user_type = 'guest';
} else {
    $user_type = getUserType();
}

// PATH DETECTION
// Determine if we are in the root directory (index.php) or a subdirectory (pages/)
$in_root = file_exists('pages'); // Simple check: if 'pages' folder exists relative to us, we are in root.
$base_path = $in_root ? 'pages/' : ''; // Links to pages need prefix if we are in root
$root_path = $in_root ? '' : '../';    // Links back to root need prefix if we are in pages



// Use isLoggedIn() from functions.php to determine state
// If function doesn't exist (edge case), default to not logged in.
$is_logged_in = function_exists('isLoggedIn') && isLoggedIn();

if ($is_logged_in) {
    // Authenticated Users Menu
    $menu = [
        ['label' => 'Dashboard', 'href' => $base_path . 'dashboard.php', 'key' => 'dashboard.php'],
        ['label' => 'Profile', 'href' => $base_path . 'profile.php', 'key' => 'profile.php'],
        ['label' => 'Matching', 'href' => $base_path . 'matching.php', 'key' => 'matching.php'],
        ['label' => 'Jobs', 'href' => $base_path . 'jobs.php', 'key' => 'jobs.php'],
        ['label' => 'My Connections', 'href' => $base_path . 'my_mentorships.php', 'key' => 'my_mentorships.php'],
        ['label' => 'Events', 'href' => $base_path . 'events.php', 'key' => 'events.php'],
    ];

    if ($user_type === 'alumni') {
        $menu[] = ['label' => 'My Jobs', 'href' => $base_path . 'my_jobs.php', 'key' => 'my_jobs.php'];
    }
} else {
    // Guest Menu
    $menu = [
        ['label' => 'Login', 'href' => $base_path . 'login.php', 'key' => 'login.php'],
        ['label' => 'Sign Up', 'href' => $base_path . 'register.php', 'key' => 'register.php'],
    ];
}

// Helper to set active class
function navActive($key, $current)
{
    return $key === $current ? 'active' : '';
}

// Badge label
$badge = strtoupper($user_type ?: 'USER');
?>
<nav class="navbar">
    <div class="navbar-container">
        <a href="<?php echo $root_path; ?>index.php" class="navbar-brand">Alumni<br>Portal</a>

        <ul class="navbar-menu">
            <?php foreach ($menu as $item): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>"
                        class="<?php echo navActive($item['key'], $current); ?>">
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if ($is_logged_in): ?>
                <!-- Role badge always in same place -->
                <li class="navbar-badge">
                    <span class="badge badge-secondary"><?php echo htmlspecialchars($badge); ?></span>
                </li>

                <!-- Logout always in same place -->
                <li class="navbar-logout">
                    <a href="<?php echo $base_path; ?>logout.php">Logout</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>