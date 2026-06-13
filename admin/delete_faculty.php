<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$faculty_id = $_GET['id'] ?? 0;

if ($faculty_id) {
    // Get faculty details
    $stmt = $conn->prepare("SELECT name, photo FROM faculty WHERE id = ?");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $faculty = $result->fetch_assoc();
    $stmt->close();
    
    if ($faculty) {
        // Delete photo if exists
        if ($faculty['photo'] && file_exists('../uploads/faculty/' . $faculty['photo'])) {
            unlink('../uploads/faculty/' . $faculty['photo']);
        }
        
        // Delete faculty
        $stmt = $conn->prepare("DELETE FROM faculty WHERE id = ?");
        $stmt->bind_param("i", $faculty_id);
        
        if ($stmt->execute()) {
            log_activity('admin', $_SESSION['user_id'], 'Deleted faculty: ' . $faculty['name']);
        }
        $stmt->close();
    }
}

header('Location: faculty.php');
exit();
?>