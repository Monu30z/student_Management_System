<?php
$page_title = "Edit Subject";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$success = '';
$error = '';
$subject_id = $_GET['id'] ?? 0;

// Get subject details
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();

if (!$subject) {
    header('Location: subjects.php');
    exit();
}

// Get branches
$branches = [];
$result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

// Get faculty
$faculty_list = [];
$result = $conn->query("SELECT * FROM faculty WHERE status = 'active' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $faculty_list[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_code = sanitize_input($_POST['subject_code']);
    $subject_name = sanitize_input($_POST['subject_name']);
    $branch_id = $_POST['branch_id'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $faculty_id = $_POST['faculty_id'] ?: NULL;
    $max_marks = $_POST['max_marks'];
    $status = $_POST['status'];
    
    // Validate
    if (empty($subject_code) || empty($subject_name) || empty($branch_id) || empty($year) || empty($semester)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if subject code exists (excluding current)
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
        $stmt->bind_param("si", $subject_code, $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Subject code already exists';
        } else {
            // Update subject
            $stmt = $conn->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, branch_id = ?, year = ?, semester = ?, faculty_id = ?, max_marks = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssissiisi", $subject_code, $subject_name, $branch_id, $year, $semester, $faculty_id, $max_marks, $status, $subject_id);
            
            if ($stmt->execute()) {
                // log_activity('admin', $_SESSION['user_id'], 'Updated subject: ' . $subject_name);
                header('Location: subjects.php');
                exit();
            } else {
                $error = 'Failed to update subject';
            }
        }
        $stmt->close();
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="content-area">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-edit"></i> Edit Subject</h4>
        <a href="subjects.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Subjects
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" class="form-control" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                    </div>
                    
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($subject['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-control" required>
                            <option value="First Year" <?php echo ($subject['year'] == 'First Year') ? 'selected' : ''; ?>>First Year</option>
                            <option value="Second Year" <?php echo ($subject['year'] == 'Second Year') ? 'selected' : ''; ?>>Second Year</option>
                            <option value="Third Year" <?php echo ($subject['year'] == 'Third Year') ? 'selected' : ''; ?>>Third Year</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-control" required>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($subject['semester'] == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Assign Faculty</label>
                        <select name="faculty_id" class="form-control">
                            <option value="">Not Assigned</option>
                            <?php foreach ($faculty_list as $faculty): ?>
                                <option value="<?php echo $faculty['id']; ?>" <?php echo ($subject['faculty_id'] == $faculty['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['name']) . ' (' . htmlspecialchars($faculty['faculty_id']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Maximum Marks</label>
                        <input type="number" name="max_marks" class="form-control" value="<?php echo $subject['max_marks']; ?>" min="1">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($subject['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($subject['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                </div>
                
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Subject
                </button>
                <a href="subjects.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>