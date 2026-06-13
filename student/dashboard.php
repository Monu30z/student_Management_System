<?php
$page_title = "Dashboard";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('student');

$student_info = get_user_info('student', $_SESSION['user_id']);
$branch_name = get_branch_name($student_info['branch_id']);

$subjects = [];

$stmt = $conn->prepare("SELECT s.*, f.name as faculty_name 
                        FROM subjects s 
                        LEFT JOIN faculty f ON s.faculty_id = f.id 
                        WHERE s.branch_id = ? AND s.year = ? AND s.semester = ? AND s.status = 'active'
                        ORDER BY s.subject_name");
$stmt->bind_param("iss", $student_info['branch_id'], $student_info['year'], $student_info['semester']);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - GP Mau</title>
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
        .stat-card{background:white;border-radius:15px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,.05);border-left:4px solid}
        .blue{border-left-color:#2563EB}.green{border-left-color:#10B981}.orange{border-left-color:#F59E0B}.purple{border-left-color:#8B5CF6}
        .stat-icon{width:55px;height:55px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:12px;background:#EFF6FF;color:#2563EB}
        .stat-number{font-size:28px;font-weight:800;color:#0F172A}
        .stat-label{color:#64748B;font-size:14px}
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
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a>
            <a href="marks.php"><i class="fas fa-pen-to-square"></i> My Marks</a>
            <a href="results.php"><i class="fas fa-certificate"></i> Results</a>
            <a href="report_card.php"><i class="fas fa-file-pdf"></i> Report Card</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">Dashboard</h5>
            <div>
                <strong><?php echo htmlspecialchars($student_info['name']); ?></strong><br>
                <small>Student</small>
            </div>
        </div>

        <div class="content">

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-number"><?php echo count($subjects); ?></div>
                        <div class="stat-label">Subjects</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fas fa-sitemap"></i></div>
                        <div class="stat-number"><?php echo htmlspecialchars(substr($branch_name, 0, 3)); ?></div>
                        <div class="stat-label">Branch</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                        <div class="stat-number"><?php echo htmlspecialchars($student_info['year']); ?></div>
                        <div class="stat-label">Year</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                        <div class="stat-number">Sem <?php echo htmlspecialchars($student_info['semester']); ?></div>
                        <div class="stat-label">Semester</div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <a href="report_card.php" class="btn btn-danger btn-lg">
                    <i class="fas fa-file-pdf"></i> Download / Print Report Card
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-book-open"></i> My Subjects</h5>
                </div>

                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Code</th>
                                        <th>Faculty</th>
                                        <th>Max Marks</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($subject['subject_code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($subject['faculty_name'] ?? 'Not Assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['max_marks']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No subjects assigned.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>