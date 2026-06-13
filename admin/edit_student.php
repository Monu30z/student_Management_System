<?php
$page_title = "Edit Student";
require_once '../config.php';
require_once '../includes/functions.php';

check_login('admin');

$success = '';
$error = '';
$student_id = $_GET['id'] ?? 0;

// Get student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: students.php');
    exit();
}

// Get branches
$branches = [];
$result = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enrollment_no = sanitize_input($_POST['enrollment_no']);
    $name = sanitize_input($_POST['name']);
    $father_name = sanitize_input($_POST['father_name']);
    $mother_name = sanitize_input($_POST['mother_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $mobile = sanitize_input($_POST['mobile']);
    $email = sanitize_input($_POST['email']);
    $address = sanitize_input($_POST['address']);
    $branch_id = $_POST['branch_id'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $status = $_POST['status'];
    
    // Validate
    if (empty($enrollment_no) || empty($name) || empty($dob) || empty($branch_id)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if enrollment number exists (excluding current student)
        $stmt = $conn->prepare("SELECT id FROM students WHERE enrollment_no = ? AND id != ?");
        $stmt->bind_param("si", $enrollment_no, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Enrollment number already exists';
        } else {
            // Handle photo upload
            $photo = $student['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['photo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    // Delete old photo
                    if ($photo && file_exists('../uploads/students/' . $photo)) {
                        unlink('../uploads/students/' . $photo);
                    }
                    
                    $photo = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $photo);
                }
            }
            
            // Update student
            $stmt = $conn->prepare("UPDATE students SET enrollment_no = ?, name = ?, father_name = ?, mother_name = ?, dob = ?, gender = ?, mobile = ?, email = ?, address = ?, branch_id = ?, year = ?, semester = ?, photo = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssssssi", $enrollment_no, $name, $father_name, $mother_name, $dob, $gender, $mobile, $email, $address, $branch_id, $year, $semester, $photo, $status, $student_id);
            
            if ($stmt->execute()) {
                // log_activity('admin', $_SESSION['user_id'], 'Updated student: ' . $name);
                header('Location: students.php');
                exit();
            } else {
                $error = 'Failed to update student';
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
        <h4><i class="fas fa-edit"></i> Edit Student</h4>
        <a href="students.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Students
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
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Enrollment Number <span class="text-danger">*</span></label>
                        <input type="text" name="enrollment_no" class="form-control" value="<?php echo htmlspecialchars($student['enrollment_no']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $student['dob']; ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($student['father_name']); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($student['mother_name']); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($student['mobile']); ?>" maxlength="10">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($student['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-control" required>
                            <option value="First Year" <?php echo ($student['year'] == 'First Year') ? 'selected' : ''; ?>>First Year</option>
                            <option value="Second Year" <?php echo ($student['year'] == 'Second Year') ? 'selected' : ''; ?>>Second Year</option>
                            <option value="Third Year" <?php echo ($student['year'] == 'Third Year') ? 'selected' : ''; ?>>Third Year</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Semester <span class="text-danger">*</span></label>
                        <select name="semester" class="form-control" required>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($student['semester'] == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($student['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($student['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="promoted" <?php echo ($student['status'] == 'promoted') ? 'selected' : ''; ?>>Promoted</option>
                            <option value="passed" <?php echo ($student['status'] == 'passed') ? 'selected' : ''; ?>>Passed</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Student Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <?php if ($student['photo']): ?>
                            <small class="text-muted">Current: <?php echo $student['photo']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                    
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Student
                </button>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>