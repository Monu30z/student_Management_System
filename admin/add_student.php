<?php
$page_title = "Add Student";
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
    
    // Validate
    if (empty($enrollment_no) || empty($name) || empty($dob) || empty($branch_id)) {
        $error = 'Please fill all required fields';
    } else {
        // Check if enrollment number exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE enrollment_no = ?");
        $stmt->bind_param("s", $enrollment_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Enrollment number already exists';
        } else {
            // Handle photo upload
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['photo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $photo = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/students/' . $photo);
                }
            }
            
            // Insert student
            $stmt = $conn->prepare("INSERT INTO students (enrollment_no, name, father_name, mother_name, dob, gender, mobile, email, address, branch_id, year, semester, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssssssssssss", $enrollment_no, $name, $father_name, $mother_name, $dob, $gender, $mobile, $email, $address, $branch_id, $year, $semester, $photo);
            
            if ($stmt->execute()) {
                log_activity('admin', $_SESSION['user_id'], 'Added student: ' . $name);
                header('Location: students.php');
                exit();
            } else {
                $error = 'Failed to add student';
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
        <h4><i class="fas fa-plus"></i> Add New Student</h4>
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
                        <input type="text" name="enrollment_no" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Student Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="form-control">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" maxlength="10">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control">
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
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Student Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Student
                </button>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>
    
</div>

<?php include '../includes/admin_footer.php'; ?>