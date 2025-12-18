<?php
// FILE: includes/navbar.php
// PURPOSE: Single navbar renderer for ALL roles with identical spacing/layout.

if (!function_exists('getUserType')) {
    // If a page includes navbar before functions.php by mistake, fail safely.
    $user_type = 'guest';
} else {
    $user_type = getUserType();
}

$current = basename($_SERVER['PHP_SELF']); // e.g., dashboard.php

// Base menu items for all authenticated users
$menu = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'key' => 'dashboard.php'],
    ['label' => 'Profile', 'href' => 'profile.php', 'key' => 'profile.php'],
    ['label' => 'Matching', 'href' => 'matching.php', 'key' => 'matching.php'],
    ['label' => 'Jobs', 'href' => 'jobs.php', 'key' => 'jobs.php'],

];

// Conditionally add checking for "My Connections" vs "My Mentorships"
// In previous steps, it seems 'my_mentorships.php' is the file, but sometimes labeled "My Connections".
// I will keep the user's provided list but verifying file existence might be good. 
// User provided: ['label' => 'My Connections', 'href' => 'my_mentorships.php', 'key' => 'my_mentorships.php']
// I will adhere to the user's provided code for the menu structure.

$menu[] = ['label' => 'My Connections', 'href' => 'my_mentorships.php', 'key' => 'my_mentorships.php'];
$menu[] = ['label' => 'Events', 'href' => 'events.php', 'key' => 'events.php'];


// Role-specific additions (same markup, only items differ)
if ($user_type === 'alumni') {
    // Example: alumni sees My Jobs, Post Job, etc.
    $menu[] = ['label' => 'My Jobs', 'href' => 'my_jobs.php', 'key' => 'my_jobs.php'];
}

if ($user_type === 'admin') {
    // Admin specific logic if needed, but links removed as requested.
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
        <a href="../index.php" class="navbar-brand">Alumni<br>Portal</a>

        <ul class="navbar-menu">
            <?php foreach ($menu as $item): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>"
                        class="<?php echo navActive($item['key'], $current); ?>">
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <!-- Role badge always in same place -->
            <li class="navbar-badge">
                <span class="badge badge-secondary"><?php echo htmlspecialchars($badge); ?></span>
            </li>

            <!-- Logout always in same place -->
            <li class="navbar-logout">
                <a href="logout.php">Logout</a>
            </li>
        </ul>

        <!-- Mobile Toggle (Preserving existing mobile menu logic if needed, but per user request adhering to their snippet) -->
        <!-- User snippet didn't include mobile toggle button HTML, but the CSS supports specific layouts. -->
        <!-- I will stick strictly to the user's PHP snippet to avoid deviations. -->
        <!-- Wait, previous navbars had a mobile toggle. If I remove it, mobile might break. -->
        <!-- The user provided CSS has: .mobile-menu-toggle { display: none; ... } -->
        <!-- But the PHP snippet provided DOES NOT have the toggle button. -->
        <!-- "Replace navbar code in ALL pages with one include" -->
        <!-- "Create: includes/navbar.php ... [PHP CODE]" -->
        <!-- I will verify if I should add the mobile toggle. The user said "Make the navbar spacing consistent with CSS (one time)" -->
        <!-- I will add the mobile toggle button to be safe, inside the container, matching previous implementation logic but with new structure if needed. -->
        <!-- Actually, strict compliance means using their PHP. But breaking mobile is bad. -->
        <!-- The user's CSS for mobile:
            @media (max-width: 900px) { ... .navbar-container { flex-direction: column; ... } }
            This suggests a stacked layout on mobile, maybe no toggle needed? 
            OR the user forgot it. 
            I'll stick to the user's provided PHP exactly. If mobile toggle is missing, I can add it if requested or if I see it's broken.
            (Actually, looking at the user's CSS, there is NO mention of .mobile-menu-toggle in the "Add/merge this" section, but the file `style.css` has existing mobile styles.)
            I will use the user's PHP code exactly as requested.
        -->
    </div>
</nav>