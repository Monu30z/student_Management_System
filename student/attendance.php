<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('student');

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$subjects = [];
$stmt = $conn->prepare("SELECT * FROM subjects 
                        WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'
                        ORDER BY subject_name");
$stmt->bind_param("iss", $student['branch_id'], $student['year'], $student['semester']);
$stmt->execute();
$res = $stmt->get_result();

while ($sub = $res->fetch_assoc()) {
    $att_stmt = $conn->prepare("SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN status='Leave' THEN 1 ELSE 0 END) AS leave_count
        FROM attendance 
        WHERE student_id = ? AND subject_id = ?");
    $att_stmt->bind_param("ii", $student_id, $sub['id']);
    $att_stmt->execute();
    $att = $att_stmt->get_result()->fetch_assoc();
    $att_stmt->close();

    $percentage = 0;
    if ($att['total'] > 0) {
        $percentage = ($att['present'] / $att['total']) * 100;
    }

    $sub['attendance'] = [
        'total' => $att['total'],
        'present' => $att['present'],
        'absent' => $att['absent'],
        'leave' => $att['leave_count'],
        'percentage' => round($percentage, 2)
    ];

    $subjects[] = $sub;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Attendance - GP Mau</title>
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
        .topbar{background:white;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between}
        .content{padding:30px}
        .card{border:none;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .card-header{background:white;padding:20px 25px;border-bottom:1px solid #E2E8F0}
        .table th{background:#F8FAFC}
        .progress{height:24px}
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
            <a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> My Attendance</a>
            <a href="marks.php"><i class="fas fa-pen-to-square"></i> My Marks</a>
            <a href="results.php"><i class="fas fa-certificate"></i> Results</a>
            <a href="report_card.php"><i class="fas fa-file-pdf"></i> Report Card</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">My Attendance</h5>
            <strong><?php echo htmlspecialchars($student['name']); ?></strong>
        </div>

        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Subject-wise Attendance</h5>
                </div>

                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Total</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Leave</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($subjects as $sub): ?>
                                        <?php $a = $sub['attendance']; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($sub['subject_code']); ?></small>
                                            </td>
                                            <td><?php echo $a['total']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $a['present']; ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo $a['absent']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $a['leave']; ?></span></td>
                                            <td style="width:220px;">
                                                <div class="progress">
                                                    <div class="progress-bar <?php echo $a['percentage'] >= 75 ? 'bg-success' : ($a['percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" style="width:<?php echo $a['percentage']; ?>%;">
                                                        <?php echo $a['percentage']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No subjects found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
</html>