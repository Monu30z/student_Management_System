<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$faculty_name = $_SESSION['name'] ?? 'Faculty';

$faculty_data = null;
$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$faculty_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$subjects = [];

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code
                        FROM subjects s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.faculty_id = ? AND s.status = 'active'
                        ORDER BY s.semester, s.subject_name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'");
    $count_stmt->bind_param("iss", $row['branch_id'], $row['year'], $row['semester']);
    $count_stmt->execute();
    $count = $count_stmt->get_result()->fetch_assoc();
    $count_stmt->close();

    $row['student_count'] = $count['total'];
    $subjects[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Subjects - GP Mau</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#F1F5F9}
        .wrapper{display:flex;min-height:100vh}
        .sidebar{width:260px;background:linear-gradient(180deg,#0F172A,#1E293B);color:#fff;position:fixed;height:100vh}
        .side-head{padding:25px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .side-head .logo{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px}
        .menu{padding:20px 0}
        .menu a{display:flex;gap:12px;align-items:center;padding:12px 20px;color:rgba(255,255,255,.75);text-decoration:none;border-left:3px solid transparent}
        .menu a:hover,.menu a.active{background:rgba(255,255,255,.1);color:#fff;border-left-color:#06B6D4}
        .main{margin-left:260px;flex:1}
        .topbar{background:#fff;padding:15px 30px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
        .content{padding:30px}
        .card{border:none;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
        .card-header{background:#fff;padding:20px 25px;border-bottom:1px solid #E2E8F0}
        .table th{background:#F8FAFC}
    </style>
</head>

<body>
<div class="wrapper">

    <div class="sidebar">
        <div class="side-head">
            <div class="logo"><i class="fas fa-chalkboard-teacher"></i></div>
            <h4>Faculty Panel</h4>
        </div>

        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="all_subjects.php" class="active"><i class="fas fa-book"></i> My Subjects</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php"><i class="fas fa-pen"></i> Marks</a>
            <?php if (!empty($faculty_data) && $faculty_data['is_hod'] == 'yes'): ?>
                <a href="hod_panel.php"><i class="fas fa-star"></i> HOD Panel</a>
            <?php endif; ?>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">My Subjects</h5>
            <div><strong><?php echo htmlspecialchars($faculty_name); ?></strong></div>
        </div>

        <div class="content">

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-book-open"></i> Assigned Subjects</h5>
                </div>

                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Branch</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($subjects as $sub): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($sub['subject_code']); ?></small>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($sub['branch_code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($sub['year']); ?></td>
                                            <td>Sem <?php echo $sub['semester']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $sub['student_count']; ?></span></td>
                                            <td>
                                                <a href="attendance.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-success btn-sm">
                                                    Attendance
                                                </a>
                                                <a href="marks.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-warning btn-sm">
                                                    Marks
                                                </a>
                                                <a href="view_students.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-info btn-sm">
                                                    Students
                                                </a>
                                            </td>
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