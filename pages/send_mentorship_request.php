<?php
// FILE: pages/send_mentorship_request.php
// DESCRIPTION: AJAX handler for sending mentorship requests

require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON header and disable error display to prevent malformed JSON
header('Content-Type: application/json');
ini_set('display_errors', 0);

// Helper for JSON response
function sendJson($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// 1. Require login
if (!isLoggedIn()) {
    sendJson(false, 'Not logged in');
}

// 2. CSRF Protection
// Note: Frontend must send 'csrf_token' key in POST data
$token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($token)) {
    sendJson(false, 'Invalid session token');
}

// 3. Only students can send requests
if (getUserType() !== 'student') {
    sendJson(false, 'Only students can send mentorship requests');
}

// Get data
$student_id = getUserId();
$alumni_id = isset($_POST['alumni_id']) ? (int) $_POST['alumni_id'] : 0;

// 4. Input Validation
if ($alumni_id <= 0) {
    sendJson(false, 'Invalid alumni ID');
}

if ($student_id === $alumni_id) {
    sendJson(false, 'You cannot send a mentorship request to yourself');
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    // 5. Validate Alumni Existence
    $query = "SELECT status FROM users WHERE user_id = :alumni_id AND user_type = 'alumni'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':alumni_id', $alumni_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendJson(false, 'Alumni not found');
    }

    $alumni = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($alumni['status'] !== 'active') {
        sendJson(false, 'This alumni account is not currently active');
    }

    // 6. Check duplicates / status
    $query = "SELECT status, match_id FROM mentorship_matches 
             WHERE student_id = :student_id AND alumni_id = :alumni_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':alumni_id', $alumni_id);
    $stmt->execute();

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // Start Transaction
    $conn->beginTransaction();

    if ($existing) {
        if ($existing['status'] === 'pending') {
            $conn->rollBack();
            sendJson(false, 'Request already pending');
        } elseif ($existing['status'] === 'active' || $existing['status'] === 'accepted') {
            $conn->rollBack();
            sendJson(false, 'You are already connected with this alumni');
        } elseif ($existing['status'] === 'rejected') {
            // Restart the relationship
            $query = "UPDATE mentorship_matches 
                     SET status = 'pending', match_date = NOW() 
                     WHERE match_id = :match_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':match_id', $existing['match_id']);
            $stmt->execute();

            $match_action = "Updated";
        }
    } else {
        // Insert new request
        $query = "INSERT INTO mentorship_matches (student_id, alumni_id, status, match_date) 
                 VALUES (:student_id, :alumni_id, 'pending', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':alumni_id', $alumni_id);
        $stmt->execute();

        $match_action = "Inserted";
    }

    // 7. Create notification for alumni
    $query = "INSERT INTO notifications (user_id, message, type, created_at) 
             VALUES (:user_id, :message, 'mentorship_request', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $alumni_id);
    $message = "You have a new mentorship request";
    $stmt->bindParam(':message', $message);
    $stmt->execute();

    // Commit
    $conn->commit();

    sendJson(true, 'Request sent successfully');

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log error internally if possible, but return generic JSON
    sendJson(false, 'Database error occurred');
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    sendJson(false, 'An unexpected error occurred');
}
?>