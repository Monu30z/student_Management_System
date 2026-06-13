<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('faculty');

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$success = isset($_GET['saved']) ? 'Marks saved successfully.' : '';
$error = '';

$faculty_name = $_SESSION['name'] ?? 'Faculty';

$faculty_data = null;
$stmt = $conn->prepare("SELECT * FROM faculty WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$faculty_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
        header('Location: marks.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT st.*,
                            COALESCE(im.assignment_marks, 0) AS assignment_marks,
                            COALESCE(im.quiz_marks, 0) AS quiz_marks,
                            COALESCE(im.mid_sem_marks, 0) AS mid_sem_marks,
                            COALESCE(im.practical_marks, 0) AS practical_marks,
                            COALESCE(im.total_internal, 0) AS total_internal
                            FROM students st
                            LEFT JOIN internal_marks im 
                                ON st.id = im.student_id AND im.subject_id = ?
                            WHERE st.branch_id = ? 
                            AND st.year = ? 
                            AND st.semester = ? 
                            AND st.status = 'active'
                            ORDER BY st.name");
    $stmt->bind_param("iiss", $subject_id, $subject['branch_id'], $subject['year'], $subject['semester']);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $subject_id > 0) {
    $marks = $_POST['marks'] ?? [];

    foreach ($students as $student) {
        $sid = (int)$student['id'];

        $assignment = isset($marks[$sid]['assignment']) ? (float)$marks[$sid]['assignment'] : 0;
        $quiz = isset($marks[$sid]['quiz']) ? (float)$marks[$sid]['quiz'] : 0;
        $mid_sem = isset($marks[$sid]['mid_sem']) ? (float)$marks[$sid]['mid_sem'] : 0;
        $practical = isset($marks[$sid]['practical']) ? (float)$marks[$sid]['practical'] : 0;

        if ($assignment < 0) $assignment = 0;
        if ($quiz < 0) $quiz = 0;
        if ($mid_sem < 0) $mid_sem = 0;
        if ($practical < 0) $practical = 0;

        if ($assignment > 20) $assignment = 20;
        if ($quiz > 20) $quiz = 20;
        if ($mid_sem > 30) $mid_sem = 30;
        if ($practical > 30) $practical = 30;

        $total = $assignment + $quiz + $mid_sem + $practical;

        $check = $conn->prepare("SELECT id FROM internal_marks WHERE student_id = ? AND subject_id = ? LIMIT 1");
        $check->bind_param("ii", $sid, $subject_id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $mid = (int)$existing['id'];

            $upd = $conn->prepare("UPDATE internal_marks 
                                   SET assignment_marks = ?, quiz_marks = ?, mid_sem_marks = ?, practical_marks = ?, total_internal = ?, updated_by = ?
                                   WHERE id = ?");
            $upd->bind_param("dddddii", $assignment, $quiz, $mid_sem, $practical, $total, $_SESSION['user_id'], $mid);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO internal_marks 
                (student_id, subject_id, assignment_marks, quiz_marks, mid_sem_marks, practical_marks, total_internal, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iidddddi", $sid, $subject_id, $assignment, $quiz, $mid_sem, $practical, $total, $_SESSION['user_id']);
            $ins->execute();
            $ins->close();
        }
    }

    // log_activity('faculty', $_SESSION['user_id'], 'Updated marks');
    header('Location: marks.php?subject_id=' . $subject_id . '&saved=1');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Marks - GP Mau</title>
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
        .table th{background:#F8FAFC;font-size:13px}
        .table td{font-size:13px;vertical-align:middle}
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
            <a href="all_subjects.php"><i class="fas fa-book"></i> My Subjects</a>
            <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="marks.php" class="active"><i class="fas fa-pen"></i> Marks</a>
            <?php if (!empty($faculty_data) && $faculty_data['is_hod'] == 'yes'): ?>
                <a href="hod_panel.php"><i class="fas fa-star"></i> HOD Panel</a>
            <?php endif; ?>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <h5 class="mb-0">Marks</h5>
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
                        <h5><i class="fas fa-book"></i> Select Subject for Marks Entry</h5>
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
                                                    <a href="marks.php?subject_id=<?php echo $sub['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-pen"></i> Enter Marks
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
                        <h5><i class="fas fa-pen"></i> Internal Marks Entry</h5>
                    </div>

                    <div class="card-body">
                        <form method="POST">

                            <?php if (count($students) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Enrollment</th>
                                                <th>Name</th>
                                                <th>Assignment<br>20</th>
                                                <th>Quiz<br>20</th>
                                                <th>Mid-Sem<br>30</th>
                                                <th>Practical<br>30</th>
                                                <th>Total<br>100</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php $i = 1; foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo $i++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($student['name']); ?></td>

                                                    <td>
                                                        <input type="number" min="0" max="20" step="0.01" 
                                                               class="form-control form-control-sm marks-input"
                                                               data-student="<?php echo $student['id']; ?>"
                                                               name="marks[<?php echo $student['id']; ?>][assignment]"
                                                               value="<?php echo $student['assignment_marks']; ?>">
                                                    </td>

                                                    <td>
                                                        <input type="number" min="0" max="20" step="0.01"
                                                               class="form-control form-control-sm marks-input"
                                                               data-student="<?php echo $student['id']; ?>"
                                                               name="marks[<?php echo $student['id']; ?>][quiz]"
                                                               value="<?php echo $student['quiz_marks']; ?>">
                                                    </td>

                                                    <td>
                                                        <input type="number" min="0" max="30" step="0.01"
                                                               class="form-control form-control-sm marks-input"
                                                               data-student="<?php echo $student['id']; ?>"
                                                               name="marks[<?php echo $student['id']; ?>][mid_sem]"
                                                               value="<?php echo $student['mid_sem_marks']; ?>">
                                                    </td>

                                                    <td>
                                                        <input type="number" min="0" max="30" step="0.01"
                                                               class="form-control form-control-sm marks-input"
                                                               data-student="<?php echo $student['id']; ?>"
                                                               name="marks[<?php echo $student['id']; ?>][practical]"
                                                               value="<?php echo $student['practical_marks']; ?>">
                                                    </td>

                                                    <td>
                                                        <input type="number" readonly 
                                                               id="total_<?php echo $student['id']; ?>"
                                                               class="form-control form-control-sm"
                                                               style="font-weight:700;background:#F8FAFC"
                                                               value="<?php echo $student['total_internal']; ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>

                                    </table>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Save Marks
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
document.querySelectorAll('.marks-input').forEach(function(input) {
    input.addEventListener('input', function() {
        const sid = this.dataset.student;

        const a = parseFloat(document.querySelector('input[name="marks['+sid+'][assignment]"]').value) || 0;
        const q = parseFloat(document.querySelector('input[name="marks['+sid+'][quiz]"]').value) || 0;
        const m = parseFloat(document.querySelector('input[name="marks['+sid+'][mid_sem]"]').value) || 0;
        const p = parseFloat(document.querySelector('input[name="marks['+sid+'][practical]"]').value) || 0;

        document.getElementById('total_' + sid).value = (a + q + m + p).toFixed(2);
    });
});
</script>

</body>
</html>