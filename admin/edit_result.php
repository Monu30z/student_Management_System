<?php
$page_title = "Edit Result";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

function result_grade_edit($total) {
    if ($total >= 180) return 'A+';
    if ($total >= 160) return 'A';
    if ($total >= 140) return 'B+';
    if ($total >= 120) return 'B';
    if ($total >= 100) return 'C';
    if ($total >= 66) return 'D';
    return 'F';
}

function result_status_edit($external, $total) {
    return ($external >= 33 && $total >= 66) ? 'Pass' : 'Fail';
}

$result_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

$stmt = $conn->prepare("SELECT r.*, 
                        st.name AS student_name, st.enrollment_no,
                        b.branch_name, b.branch_code,
                        sub.subject_name, sub.subject_code
                        FROM results r
                        LEFT JOIN students st ON r.student_id = st.id
                        LEFT JOIN branches b ON st.branch_id = b.id
                        LEFT JOIN subjects sub ON r.subject_id = sub.id
                        WHERE r.id = ?");
$stmt->bind_param("i", $result_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    header('Location: results.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $internal = (float)$_POST['internal_marks'];
    $external = (float)$_POST['external_marks'];

    if ($internal < 0) $internal = 0;
    if ($internal > 100) $internal = 100;

    if ($external < 0) $external = 0;
    if ($external > 100) $external = 100;

    $total = $internal + $external;
    $grade = result_grade_edit($total);
    $status = result_status_edit($external, $total);

    $stmt = $conn->prepare("UPDATE results 
                            SET internal_marks = ?, external_marks = ?, total_marks = ?, grade = ?, status = ?
                            WHERE id = ?");
    $stmt->bind_param("dddssi", $internal, $external, $total, $grade, $status, $result_id);

    if ($stmt->execute()) {
        // log_activity('admin', $_SESSION['user_id'], 'Edited result ID ' . $result_id);
        header('Location: results.php?success=1');
        exit();
    } else {
        $error = 'Result update failed.';
    }

    $stmt->close();
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-edit"></i> Edit Result</h4>
        <a href="results.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Result Details</h5>
        </div>

        <div class="card-body">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($result['student_name']); ?> (<?php echo htmlspecialchars($result['enrollment_no']); ?>)</p>
            <p><strong>Branch:</strong> <?php echo htmlspecialchars($result['branch_name']); ?></p>
            <p><strong>Semester:</strong> Semester <?php echo htmlspecialchars($result['semester']); ?></p>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($result['subject_name']); ?> (<?php echo htmlspecialchars($result['subject_code']); ?>)</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Edit Marks</h5>
        </div>

        <div class="card-body">
            <form method="POST">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Internal Marks (100)</label>
                        <input type="number" name="internal_marks" class="form-control" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($result['internal_marks']); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">External Marks (100)</label>
                        <input type="number" name="external_marks" class="form-control" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($result['external_marks']); ?>" required>
                    </div>

                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Result
                </button>

            </form>
        </div>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>