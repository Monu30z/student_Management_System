<?php
$page_title = "Manage Subjects";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$error_msg = '';
$success_msg = '';

if (isset($_GET['error']) && $_GET['error'] == 'compulsory') {
    $error_msg = 'Compulsory fixed subject cannot be deleted.';
}

if (isset($_GET['deleted'])) {
    $success_msg = 'Subject deleted successfully.';
}

$branch_filter = $_GET['branch'] ?? 'all';
$semester_filter = $_GET['semester'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

$branches = [];
$branch_res = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $branch_res->fetch_assoc()) {
    $branches[] = $row;
}

$where = "1=1";

if ($branch_filter !== 'all') {
    $where .= " AND s.branch_id = " . (int)$branch_filter;
}

if ($semester_filter !== 'all') {
    $sem = (int)$semester_filter;
    if ($sem >= 1 && $sem <= 6) {
        $where .= " AND s.semester = '$sem'";
    }
}

if ($type_filter == 'compulsory') {
    $where .= " AND s.is_compulsory = 'yes'";
} elseif ($type_filter == 'optional') {
    $where .= " AND s.is_compulsory = 'no'";
}

$subjects = [];

$query = "SELECT s.*, b.branch_name, b.branch_code, f.name AS faculty_name
          FROM subjects s
          LEFT JOIN branches b ON s.branch_id = b.id
          LEFT JOIN faculty f ON s.faculty_id = f.id
          WHERE $where
          ORDER BY b.branch_code, s.semester, s.subject_code";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">

    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-book"></i> All Subjects</h4>
        <a href="add_subject.php" class="btn btn-warning">
            <i class="fas fa-plus"></i> Add Optional Subject
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Subject Filter</h5>
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
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control">
                        <option value="all">All Semesters</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Subject Type</label>
                    <select name="type" class="form-control">
                        <option value="all" <?php echo ($type_filter == 'all') ? 'selected' : ''; ?>>All</option>
                        <option value="compulsory" <?php echo ($type_filter == 'compulsory') ? 'selected' : ''; ?>>Compulsory Fixed</option>
                        <option value="optional" <?php echo ($type_filter == 'optional') ? 'selected' : ''; ?>>Optional / Custom</option>
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
            <h5>
                <i class="fas fa-list"></i> Subjects Found:
                <span class="badge bg-primary"><?php echo count($subjects); ?></span>
            </h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Branch</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Faculty</th>
                            <th>Type</th>
                            <th>Max Marks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>

                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>

                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($subject['branch_code']); ?>
                                    </span>
                                </td>

                                <td><?php echo htmlspecialchars($subject['year']); ?></td>

                                <td>Sem <?php echo htmlspecialchars($subject['semester']); ?></td>

                                <td><?php echo htmlspecialchars($subject['faculty_name'] ?? 'Not Assigned'); ?></td>

                                <td>
                                    <?php if ($subject['is_compulsory'] == 'yes'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-lock"></i> Compulsory
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Optional</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($subject['max_marks']); ?></td>

                                <td>
                                    <?php if ($subject['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($subject['is_compulsory'] == 'yes'): ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Compulsory subject cannot be deleted">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="delete_subject.php?id=<?php echo $subject['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirmDelete('<?php echo htmlspecialchars($subject['subject_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>