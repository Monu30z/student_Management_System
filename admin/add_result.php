<?php
$page_title = "Publish / Update Result";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

function result_year_from_semester($sem) {
    if ($sem == 1 || $sem == 2) return 'First Year';
    if ($sem == 3 || $sem == 4) return 'Second Year';
    return 'Third Year';
}

function result_grade($total) {
    if ($total >= 180) return 'A+';
    if ($total >= 160) return 'A';
    if ($total >= 140) return 'B+';
    if ($total >= 120) return 'B';
    if ($total >= 100) return 'C';
    if ($total >= 66) return 'D';
    return 'F';
}

function result_status($external, $total) {
    return ($external >= 33 && $total >= 66) ? 'Pass' : 'Fail';
}

$error = '';
$success = '';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;

$branch_filter = $_GET['branch'] ?? 'all';
$year_filter = $_GET['year'] ?? 'all';
$semester_filter = $_GET['filter_semester'] ?? 'all';

$branches = [];
$res = $conn->query("SELECT * FROM branches WHERE status='active' ORDER BY branch_name");
while ($row = $res->fetch_assoc()) {
    $branches[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish_result'])) {
    $student_id = (int)$_POST['student_id'];
    $semester = (int)$_POST['semester'];
    $external = $_POST['external'] ?? [];

    if ($student_id <= 0 || $semester < 1 || $semester > 6) {
        $error = 'Invalid student or semester.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $error = 'Student not found.';
        } else {
            $result_year = result_year_from_semester($semester);

            $stmt = $conn->prepare("SELECT * FROM subjects 
                                    WHERE branch_id = ? AND year = ? AND semester = ? AND status = 'active'
                                    ORDER BY subject_name");
            $stmt->bind_param("iss", $student['branch_id'], $result_year, $semester);
            $stmt->execute();
            $subjects_res = $stmt->get_result();

            $subjects_to_save = [];
            while ($sub = $subjects_res->fetch_assoc()) {
                $subjects_to_save[] = $sub;
            }
            $stmt->close();

            if (count($subjects_to_save) == 0) {
                $error = 'No subjects found for this student branch/year/semester.';
            } else {
                foreach ($subjects_to_save as $sub) {
                    $subject_id = (int)$sub['id'];
                    $external_marks = isset($external[$subject_id]) ? (float)$external[$subject_id] : 0;

                    if ($external_marks < 0) $external_marks = 0;
                    if ($external_marks > 100) $external_marks = 100;

                    $stmt = $conn->prepare("SELECT total_internal FROM internal_marks WHERE student_id = ? AND subject_id = ? LIMIT 1");
                    $stmt->bind_param("ii", $student_id, $subject_id);
                    $stmt->execute();
                    $im = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $internal_marks = $im ? (float)$im['total_internal'] : 0;
                    $total_marks = $internal_marks + $external_marks;
                    $grade = result_grade($total_marks);
                    $status = result_status($external_marks, $total_marks);

                    $stmt = $conn->prepare("INSERT INTO results 
                        (student_id, semester, subject_id, internal_marks, external_marks, total_marks, grade, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        internal_marks = VALUES(internal_marks),
                        external_marks = VALUES(external_marks),
                        total_marks = VALUES(total_marks),
                        grade = VALUES(grade),
                        status = VALUES(status)");

                    $sem_str = (string)$semester;

                    $stmt->bind_param(
                        "isidddss",
                        $student_id,
                        $sem_str,
                        $subject_id,
                        $internal_marks,
                        $external_marks,
                        $total_marks,
                        $grade,
                        $status
                    );

                    $stmt->execute();
                    $stmt->close();
                }

                log_activity('admin', $_SESSION['user_id'], 'Published result for student ID ' . $student_id);
                header('Location: results.php?success=1');
                exit();
            }
        }
    }
}

$students = [];

if ($student_id == 0) {
    $where = "1=1";

    if ($branch_filter !== 'all') {
        $where .= " AND s.branch_id = " . (int)$branch_filter;
    }

    $allowed_years = ['First Year', 'Second Year', 'Third Year'];
    if ($year_filter !== 'all' && in_array($year_filter, $allowed_years)) {
        $safe_year = $conn->real_escape_string($year_filter);
        $where .= " AND s.year = '$safe_year'";
    }

    if ($semester_filter !== 'all') {
        $sem = (int)$semester_filter;
        if ($sem >= 1 && $sem <= 6) {
            $where .= " AND s.semester = '$sem'";
        }
    }

    $query = "SELECT s.*, b.branch_name, b.branch_code
              FROM students s
              LEFT JOIN branches b ON s.branch_id = b.id
              WHERE $where
              ORDER BY s.name";

    $res = $conn->query($query);

    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
}

$student = null;
$subjects = [];

if ($student_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code
                            FROM students s
                            LEFT JOIN branches b ON s.branch_id = b.id
                            WHERE s.id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        header('Location: add_result.php');
        exit();
    }

    if ($semester < 1 || $semester > 6) {
        $semester = (int)$student['semester'];
    }

    $result_year = result_year_from_semester($semester);

    $stmt = $conn->prepare("SELECT sub.*,
                            COALESCE(im.total_internal, 0) AS internal_marks,
                            COALESCE(r.external_marks, 0) AS existing_external,
                            r.id AS result_id,
                            r.grade AS existing_grade,
                            r.status AS existing_status
                            FROM subjects sub
                            LEFT JOIN internal_marks im 
                                ON sub.id = im.subject_id AND im.student_id = ?
                            LEFT JOIN results r 
                                ON sub.id = r.subject_id AND r.student_id = ? AND r.semester = ?
                            WHERE sub.branch_id = ? 
                            AND sub.year = ? 
                            AND sub.semester = ?
                            AND sub.status = 'active'
                            ORDER BY sub.subject_name");

    $sem_str = (string)$semester;

    $stmt->bind_param("iisisi", $student_id, $student_id, $sem_str, $student['branch_id'], $result_year, $semester);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }

    $stmt->close();
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-plus"></i> Publish / Update Result</h4>
        <a href="results.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Results
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($student_id == 0): ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-filter"></i> Select Student</h5>
            </div>

            <div class="card-body">
                <form method="GET" class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <select name="branch" class="form-control">
                            <option value="all">All Branches</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($branch_filter == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <option value="all">All Years</option>
                            <option value="First Year" <?php echo ($year_filter == 'First Year') ? 'selected' : ''; ?>>First Year</option>
                            <option value="Second Year" <?php echo ($year_filter == 'Second Year') ? 'selected' : ''; ?>>Second Year</option>
                            <option value="Third Year" <?php echo ($year_filter == 'Third Year') ? 'selected' : ''; ?>>Third Year</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Semester</label>
                        <select name="filter_semester" class="form-control">
                            <option value="all">All Semesters</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Students</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Enrollment</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Publish</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($students as $st): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($st['enrollment_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($st['name']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($st['branch_code']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($st['year']); ?></td>
                                    <td>Sem <?php echo htmlspecialchars($st['semester']); ?></td>
                                    <td>
                                        <a href="add_result.php?student_id=<?php echo $st['id']; ?>&semester=<?php echo $st['semester']; ?>" class="btn btn-primary btn-sm">
                                            Publish / Update
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Student Details</h5>
            </div>

            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                <p><strong>Enrollment:</strong> <?php echo htmlspecialchars($student['enrollment_no']); ?></p>
                <p><strong>Branch:</strong> <?php echo htmlspecialchars($student['branch_name']); ?></p>

                <form method="GET" class="row g-3">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">

                    <div class="col-md-4">
                        <label class="form-label">Result Semester</label>
                        <select name="semester" class="form-control">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($semester == $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Load Subjects</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Subject-wise Result Entry</h5>
            </div>

            <div class="card-body">
                <?php if (count($subjects) > 0): ?>

                    <form method="POST">
                        <input type="hidden" name="publish_result" value="1">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="semester" value="<?php echo $semester; ?>">

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Internal Marks<br>100</th>
                                        <th>External Marks<br>100</th>
                                        <th>Existing Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($subjects as $sub): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sub['subject_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($sub['subject_code']); ?></small>
                                            </td>

                                            <td>
                                                <input type="number" class="form-control" value="<?php echo htmlspecialchars($sub['internal_marks']); ?>" readonly>
                                            </td>

                                            <td>
                                                <input type="number" 
                                                       name="external[<?php echo $sub['id']; ?>]" 
                                                       class="form-control"
                                                       min="0" max="100" step="0.01"
                                                       value="<?php echo htmlspecialchars($sub['existing_external']); ?>">
                                            </td>

                                            <td>
                                                <?php if (!empty($sub['result_id'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($sub['existing_grade']); ?></span>
                                                    <?php if ($sub['existing_status'] == 'Pass'): ?>
                                                        <span class="badge bg-success">Pass</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Fail</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Published</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Publish / Update Result
                        </button>
                    </form>

                <?php else: ?>
                    <div class="alert alert-info">
                        Is semester ke liye subjects available nahi hain. Pehle Subjects module me compulsory subjects add karo.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php include '../includes/admin_footer.php'; ?>