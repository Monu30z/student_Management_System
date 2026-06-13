<?php
$page_title = "Add Subject";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$success = '';
$error = '';

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
    
    // Validate
    if (empty($subject_code) || empty($subject_name) || empty($branch_id) || empty($year) || empty($semester)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if subject code exists
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Subject code already exists';
        } else {
            // Insert subject
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, branch_id, year, semester, faculty_id, max_marks, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssissii", $subject_code, $subject_name, $branch_id, $year, $semester, $faculty_id, $max_marks);
            
            if ($stmt->execute()) {
                log_activity('admin', $_SESSION['user_id'], 'Added subject: ' . $subject_name);
                header('Location: subjects.php');
                exit();
            } else {
                $error = 'Failed to add subject';
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
        <h4><i class="fas fa-plus"></i> Add New Subject</h4>
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
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g., CS101" required>
                    </div>
                    
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g., Computer Programming" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-control" required>
                            <option value="">Select Year</option>
                            <option value="First Year">First Year</option>
                            <option value="Second Year">Second Year</option>
                            <option value="Third Year">Third Year</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-control" required>
                            <option value="">Select Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Assign Faculty</label>
                        <select name="faculty_id" class="form-control">
                            <option value="">Select Faculty (Optional)</option>
                            <?php foreach ($faculty_list as $faculty): ?>
                                <option value="<?php echo $faculty['id']; ?>">
                                    <?php echo htmlspecialchars($faculty['name']) . ' (' . htmlspecialchars($faculty['faculty_id']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Maximum Marks</label>
                        <input type="number" name="max_marks" class="form-control" value="100" min="1">
                    </div>
                    
                </div>
                
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Add Subject
                </button>
                <a href="subjects.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>