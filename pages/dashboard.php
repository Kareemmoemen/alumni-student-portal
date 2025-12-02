<?php 
// Include required files 
require_once '../config/database.php'; 
require_once '../includes/functions.php'; 
// Require login 
requireLogin(); 
// Get user information 
$user_id = getUserId(); 
$user_type = getUserType(); 
$user_email = getUserEmail(); 
// Create database connection 
$database = new Database(); 
$conn = $database->getConnection(); 
// Get user profile information 
$query = "SELECT p.*, u.email, u.registration_date  
FROM profiles p  
INNER JOIN users u ON p.user_id = u.user_id  
WHERE p.user_id = :user_id"; 
$stmt = $conn->prepare($query); 
$stmt->bindParam(':user_id', $user_id); 
$stmt->execute(); 
$profile = $stmt->fetch(PDO::FETCH_ASSOC); 
// Get some statistics 
// Count total users 
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total']; 
 
// Count students 
$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND status = 
'active'"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total']; 
 
// Count alumni 
$query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'alumni' AND status = 
'active'"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$total_alumni = $stmt->fetch(PDO::FETCH_ASSOC)['total']; 
 
// Count active mentorship matches 
$query = "SELECT COUNT(*) as total FROM mentorship_matches WHERE status IN ('active', 
'pending')"; 
$stmt = $conn->prepare($query); 
$stmt->execute(); 
$total_matches = $stmt->fetch(PDO::FETCH_ASSOC)['total']; 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Dashboard - Alumni Portal</title> 
    <style> 
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        } 
         
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f7fa; 
        } 
         
        .navbar { 
            background: white; 
            padding: 15px 50px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        } 
         
        .navbar h1 { 
            color: #667eea; 
            font-size: 24px; 
        } 
         
        .navbar .user-info { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        } 
         
        .navbar .user-info span { 
            color: #666; 
        } 
         
        .navbar .user-info .badge { 
            background: #667eea; 
            color: white; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            text-transform: uppercase; 
        } 
         
        .navbar a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600; 
        } 
         
        .container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px; 
        } 
         
        .welcome-section { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
        } 
         
        .welcome-section h2 { 
            color: #333; 
            margin-bottom: 10px; 
        } 
         
        .welcome-section p { 
            color: #666; 
            font-size: 16px; 
        } 
         
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        } 
         
        .stat-card { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            text-align: center; 
        } 
         
        .stat-card h3 { 
            font-size: 36px; 
            color: #667eea; 
            margin-bottom: 10px; 
        } 
         
        .stat-card p { 
            color: #666; 
            font-size: 14px; 
        } 
         
        .quick-actions { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        } 
         
        .quick-actions h3 { 
            color: #333; 
            margin-bottom: 20px; 
        } 
         
        .action-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
        } 
         
        .action-btn { 
            display: block; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            text-align: center; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: 600; 
            transition: transform 0.2s; 
        } 
         
        .action-btn:hover { 
            transform: translateY(-3px); 
        } 
         
        .success-message { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border-left: 4px solid #28a745; 
        } 
    </style> 
</head> 
<body> 
    <nav class="navbar"> 
        <h1>🎓 Alumni Portal</h1> 
        <div class="user-info"> 
            <span><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); 
?></span> 
            <span class="badge"><?php echo htmlspecialchars($user_type); ?></span> 
            <a href="logout.php">Logout</a> 
        </div> 
    </nav> 
     
    <div class="container"> 
        <?php  
        $success = getSuccess(); 
        if ($success):  
        ?> 
            <div class="success-message"><?php echo $success; ?></div> 
        <?php endif; ?> 
         
        <div class="welcome-section"> 
            <h2>Welcome back, <?php echo htmlspecialchars($profile['first_name']); ?>! 👋</h2> 
            <p>Member since <?php echo formatDate($profile['registration_date']); ?></p> 
        </div> 
         
        <div class="stats-grid"> 
            <div class="stat-card"> 
                <h3><?php echo $total_users; ?></h3> 
                <p>Total Members</p> 
            </div> 
            <div class="stat-card"> 
                <h3><?php echo $total_students; ?></h3> 
                <p>Active Students</p> 
            </div> 
            <div class="stat-card"> 
                <h3><?php echo $total_alumni; ?></h3> 
                <p>Active Alumni</p> 
            </div> 
            <div class="stat-card"> 
                <h3><?php echo $total_matches; ?></h3> 
                <p>Mentorship Connections</p> 
            </div> 
        </div> 
         
        <div class="quick-actions"> 
            <h3>Quick Actions</h3> 
            <div class="action-grid"> 
                <a href="profile.php" class="action-btn">My Profile</a> 
                <a href="matching.php" class="action-btn">Find Mentors</a> 
                <a href="forum.php" class="action-btn">Forum</a> 
                <a href="jobs.php" class="action-btn">Job Board</a> 
                <a href="events.php" class="action-btn">Events</a> 
            </div> 
        </div> 
    </div> 
</body> 
</html> 