<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('student');

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                        FROM students s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: ../logout.php');
    exit();
}

$results = [];

$stmt = $conn->prepare("SELECT r.*, sub.subject_name, sub.subject_code
                        FROM results r
                        LEFT JOIN subjects sub ON r.subject_id = sub.id
                        WHERE r.student_id = ?
                        ORDER BY r.semester DESC, sub.subject_name ASC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}
$stmt->close();

$internal_subjects = [];
$stmt = $conn->prepare("SELECT sub.*, 
                        COALESCE(im.total_internal,0) AS total_internal
                        FROM subjects sub
                        LEFT JOIN internal_marks im ON sub.id = im.subject_id AND im.student_id = ?
                        WHERE sub.branch_id = ?
                        AND sub.year = ?
                        AND sub.semester = ?
                        AND sub.status = 'active'
                        ORDER BY sub.subject_name");
$stmt->bind_param("iiss", $student_id, $student['branch_id'], $student['year'], $student['semester']);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $internal_subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Results - GP Mau</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#F1F5F9}
        .wrapper{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#0F172A,#1E293B);color:white;position:fixed;height:100vh}
        .side-head{padding:25px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .side-head .logo{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px}
        .menu{padding:20px 0}
        .menu a{display:flex;gap:12px;align-items:center;padding:12px 20px;color:rgba(255,255,255,.75);text-decoration:none;border-left:3px solid transparent}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,.1);color:white;border-left-color:#06B6D4}
        .main{margin-left:260px;flex:1}
        .topbar{background:white;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .content{padding:30px}
        .card{border:none;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .card-header{background:white;padding:20px 25px;border-bottom:1px solid #E2E8F0}
        .table th{background:#F8FAFC}
    </style>
</head>

<body>
<div class="wrapper">

    <div class="sidebar">
        <div class="side-head">
            <div class="logo"><i class="fas fa-user-graduate"></i></div>
            <h4>Student Portal</h4>
        </div>

        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a>
            <a href="marks.php"><i class="fas fa-pen-to-square"></i> My Marks</a>
            <a href="results.php" class="active"><i class="fas fa-certificate"></i> Results</a>
            <a href="report_card.php"><i class="fas fa-file-pdf"></i> Report Card</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">Results</h5>
            <div>
                <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                <small><?php echo htmlspecialchars($student['enrollment_no']); ?></small>
            </div>
        </div>

        <div class="content">

            <div class="mb-4">
                <a href="report_card.php" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Download / Print Report Card
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-certificate"></i> Published Results</h5>
                </div>

                <div class="card-body">
                    <?php if (count($results) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Semester</th>
                                        <th>Subject</th>
                                        <th>Internal</th>
                                        <th>External</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($results as $r): ?>
                                        <tr>
                                            <td>Sem <?php echo htmlspecialchars($r['semester']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($r['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($r['subject_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($r['internal_marks']); ?></td>
                                            <td><?php echo htmlspecialchars($r['external_marks']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($r['total_marks']); ?></strong></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($r['grade'] ?? 'N/A'); ?></span></td>
                                            <td>
                                                <?php if ($r['status'] == 'Pass'): ?>
                                                    <span class="badge bg-success">Pass</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Fail</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            External semester result abhi publish nahi hua hai. Internal report card available hai.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Current Internal Result Preview</h5>
                </div>

                <div class="card-body">
                    <?php if (count($internal_subjects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Internal Marks</th>
                                        <th>Maximum</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($internal_subjects as $sub): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($sub['subject_code']); ?></small>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($sub['total_internal']); ?></strong></td>
                                            <td>100</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No internal marks available.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>
</body>
</html>