<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$student_id = $_GET['id'] ?? 0;

if ($student_id) {
    // Get student details
    $stmt = $conn->prepare("SELECT name, photo FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        // Delete photo if exists
        if ($student['photo'] && file_exists('../uploads/students/' . $student['photo'])) {
            unlink('../uploads/students/' . $student['photo']);
        }
        
        // Delete student
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        
        // if ($stmt->execute()) {
        //     log_activity('admin', $_SESSION['user_id'], 'Deleted student: ' . $student['name']);
        // }
        $stmt->close();
    }
}

header('Location: students.php');
exit();
?>