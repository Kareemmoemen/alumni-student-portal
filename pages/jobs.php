<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
$user_id = getUserId();
$user_type = getUserType();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - Alumni Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php
    $current_page = 'jobs.php';
    include '../includes/navbar.php';
    ?>

    <div style="max-width: 1200px; margin: 40px auto; padding: 0 20px; text-align: center;">
        <h1>Job Board</h1>
        <p>Job listings coming soon...</p>
    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>