<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id > 0) {
    $stmt = $conn->prepare("SELECT subject_name, is_compulsory FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($subject) {
        if ($subject['is_compulsory'] == 'yes') {
            header('Location: subjects.php?error=compulsory');
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subject_id);

        // if ($stmt->execute()) {
        //     log_activity('admin', $_SESSION['user_id'], 'Deleted optional subject: ' . $subject['subject_name']);
        // }

        $stmt->close();
    }
}

header('Location: subjects.php?deleted=1');
exit();
?>