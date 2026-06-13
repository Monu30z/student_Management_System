<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$success = '';
$error = '';

$faculty_name = $_SESSION['name'] ?? 'Faculty';

$assigned_subjects = [];
$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                        FROM subjects s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.faculty_id = ? AND s.status = 'active'
                        ORDER BY s.semester, s.subject_name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $assigned_subjects[] = $row;
}
$stmt->close();

$subject = null;
$students = [];

if ($subject_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                            FROM subjects s
                            LEFT JOIN branches b ON s.branch_id = b.id
                            WHERE s.id = ? AND s.faculty_id = ?");
    $stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$subject) {
        header('Location: attendance.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM students 
                            WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'
                            ORDER BY name");
    $stmt->bind_param("iss", $subject['branch_id'], $subject['year'], $subject['semester']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $subject_id > 0) {
    $date = $_POST['date'];
    $attendance = $_POST['attendance'] ?? [];

    if (empty($date)) {
        $error = 'Please select date';
    } else {
        $stmt = $conn->prepare("DELETE FROM attendance WHERE subject_id = ? AND date = ?");
        $stmt->bind_param("is", $subject_id, $date);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO attendance (student_id, subject_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?)");

        foreach ($students as $student) {
            $status = $attendance[$student['id']] ?? 'Absent';
            $stmt->bind_param("iissi", $student['id'], $subject_id, $date, $status, $_SESSION['user_id']);
            $stmt->execute();
        }

        $stmt->close();

        // log_activity('faculty', $_SESSION['user_id'], 'Marked attendance for ' . $subject['subject_name']);
        $success = 'Attendance marked successfully';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance - GP Mau</title>
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
            <a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php"><i class="fas fa-pen"></i> Marks</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">Attendance</h5>
            <div><strong><?php echo htmlspecialchars($faculty_name); ?></strong></div>
        </div>

        <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($subject_id == 0): ?>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-book"></i> Select Subject for Attendance</h5>
                    </div>

                    <div class="card-body">
                        <?php if (count($assigned_subjects) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Branch</th>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($assigned_subjects as $sub): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($sub['subject_code']); ?></small>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($sub['branch_code']); ?></span></td>
                                                <td><?php echo htmlspecialchars($sub['year']); ?></td>
                                                <td>Sem <?php echo $sub['semester']; ?></td>
                                                <td>
                                                    <a href="attendance.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Mark Attendance
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

            <?php else: ?>

                <div class="card mb-3">
                    <div class="card-body">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?> |
                        <strong>Branch:</strong> <?php echo htmlspecialchars($subject['branch_name']); ?> |
                        <strong>Semester:</strong> <?php echo $subject['semester']; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-check"></i> Mark Attendance</h5>
                    </div>

                    <div class="card-body">
                        <form method="POST">

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" class="form-control" required>
                                </div>

                                <div class="col-md-8 d-flex align-items-end gap-2">
                                    <button type="button" onclick="markAll('Present')" class="btn btn-success">All Present</button>
                                    <button type="button" onclick="markAll('Absent')" class="btn btn-danger">All Absent</button>
                                </div>
                            </div>

                            <?php if (count($students) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Enrollment</th>
                                                <th>Name</th>
                                                <th>Attendance</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php $i = 1; foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo $i++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="p<?php echo $student['id']; ?>" value="Present" checked>
                                                            <label class="btn btn-outline-success" for="p<?php echo $student['id']; ?>">Present</label>

                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="a<?php echo $student['id']; ?>" value="Absent">
                                                            <label class="btn btn-outline-danger" for="a<?php echo $student['id']; ?>">Absent</label>

                                                            <input type="radio" class="btn-check" name="attendance[<?php echo $student['id']; ?>]" id="l<?php echo $student['id']; ?>" value="Leave">
                                                            <label class="btn btn-outline-warning" for="l<?php echo $student['id']; ?>">Leave</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>

                                    </table>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Submit Attendance
                                </button>
                            <?php else: ?>
                                <div class="alert alert-info">No students found for this subject.</div>
                            <?php endif; ?>

                        </form>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('input[value="' + status + '"]').forEach(function(el) {
        el.checked = true;
    });
}
</script>

</body>
</html>