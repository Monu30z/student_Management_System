<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$result_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($result_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM results WHERE id = ?");
    $stmt->bind_param("i", $result_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
        $stmt->bind_param("i", $result_id);
        $stmt->execute();
        $stmt->close();

        log_activity('admin', $_SESSION['user_id'], 'Deleted result ID ' . $result_id);
    }
}

header('Location: results.php?deleted=1');
exit();
?>