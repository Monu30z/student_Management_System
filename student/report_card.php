<?php
require_once '../config.php';
require_once '../includes/functions.php';

check_login('student');

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT s.*, b.branch_name, b.branch_code 
                        FROM students s
                        LEFT JOIN branches b ON s.branch_id = b.id
                        WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: dashboard.php');
    exit();
}

$subjects = [];

$stmt = $conn->prepare("SELECT sub.*, 
                        COALESCE(im.assignment_marks,0) AS assignment_marks,
                        COALESCE(im.quiz_marks,0) AS quiz_marks,
                        COALESCE(im.mid_sem_marks,0) AS mid_sem_marks,
                        COALESCE(im.practical_marks,0) AS practical_marks,
                        COALESCE(im.total_internal,0) AS total_internal,
                        f.name AS faculty_name
                        FROM subjects sub
                        LEFT JOIN internal_marks im 
                            ON sub.id = im.subject_id AND im.student_id = ?
                        LEFT JOIN faculty f 
                            ON sub.faculty_id = f.id
                        WHERE sub.branch_id = ? 
                        AND sub.year = ? 
                        AND sub.semester = ? 
                        AND sub.status = 'active'
                        ORDER BY sub.subject_name");

$stmt->bind_param("iiss", $student_id, $student['branch_id'], $student['year'], $student['semester']);
$stmt->execute();
$res = $stmt->get_result();

$total_obtained = 0;
$total_max = 0;

while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
    $total_obtained += (float)$row['total_internal'];
    $total_max += 100;
}
$stmt->close();

$percentage = $total_max > 0 ? ($total_obtained / $total_max) * 100 : 0;

if ($percentage >= 90) {
    $grade = 'A+';
} elseif ($percentage >= 80) {
    $grade = 'A';
} elseif ($percentage >= 70) {
    $grade = 'B+';
} elseif ($percentage >= 60) {
    $grade = 'B';
} elseif ($percentage >= 50) {
    $grade = 'C';
} elseif ($percentage >= 33) {
    $grade = 'D';
} else {
    $grade = 'F';
}

$status = ($percentage >= 33) ? 'Pass' : 'Fail';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Report Card - <?php echo htmlspecialchars($student['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #F1F5F9;
            font-family: Arial, sans-serif;
        }

        .toolbar {
            text-align: center;
            margin: 20px 0;
        }

        .report-card {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            padding: 35px;
            border: 2px solid #0F172A;
        }

        .college-header {
            text-align: center;
            border-bottom: 3px solid #0F172A;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .college-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: #0F172A;
        }

        .college-header h3 {
            margin-top: 8px;
            font-size: 18px;
            color: #2563EB;
        }

        .student-box {
            display: grid;
            grid-template-columns: 1fr 150px;
            gap: 25px;
            margin-bottom: 25px;
        }

        .student-photo {
            width: 130px;
            height: 150px;
            border: 1px solid #CBD5E1;
            object-fit: cover;
        }

        .info-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #E2E8F0;
        }

        .info-table td:first-child {
            font-weight: 700;
            color: #0F172A;
            width: 180px;
        }

        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .marks-table th {
            background: #0F172A;
            color: white;
            padding: 10px;
            border: 1px solid #0F172A;
            font-size: 13px;
        }

        .marks-table td {
            padding: 9px;
            border: 1px solid #CBD5E1;
            font-size: 13px;
        }

        .marks-table tr:nth-child(even) {
            background: #F8FAFC;
        }

        .summary {
            margin-top: 25px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .summary-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
        }

        .summary-card h4 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #2563EB;
        }

        .summary-card p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #64748B;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 70px;
        }

        .sign {
            width: 220px;
            text-align: center;
            border-top: 2px solid #0F172A;
            padding-top: 8px;
            font-weight: 700;
        }

        @media print {
            body {
                background: white;
            }

            .toolbar {
                display: none;
            }

            .report-card {
                margin: 0;
                border: 2px solid #0F172A;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="toolbar">
    <button onclick="downloadPDF()" class="btn btn-primary">
        Download PDF
    </button>

    <button onclick="window.print()" class="btn btn-success">
        Print
    </button>

    <a href="dashboard.php" class="btn btn-secondary">
        Back
    </a>
</div>

<div class="report-card" id="reportCard">

    <div class="college-header">
        <h1>GOVERNMENT POLYTECHNIC MAU</h1>
        <h3>Internal Assessment Report Card</h3>
        <p>Academic Year: <?php echo date('Y'); ?>-<?php echo date('Y') + 1; ?></p>
    </div>

    <div class="student-box">
        <div>
            <table class="info-table" width="100%">
                <tr>
                    <td>Enrollment No</td>
                    <td><?php echo htmlspecialchars($student['enrollment_no']); ?></td>
                </tr>
                <tr>
                    <td>Student Name</td>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                </tr>
                <tr>
                    <td>Father Name</td>
                    <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                </tr>
                <tr>
                    <td>Mother Name</td>
                    <td><?php echo htmlspecialchars($student['mother_name']); ?></td>
                </tr>
                <tr>
                    <td>Date of Birth</td>
                    <td><?php echo date('d-m-Y', strtotime($student['dob'])); ?></td>
                </tr>
                <tr>
                    <td>Branch</td>
                    <td><?php echo htmlspecialchars($student['branch_name']); ?></td>
                </tr>
                <tr>
                    <td>Year / Semester</td>
                    <td><?php echo htmlspecialchars($student['year']); ?> / Semester <?php echo htmlspecialchars($student['semester']); ?></td>
                </tr>
            </table>
        </div>

        <div style="text-align:center;">
            <?php if (!empty($student['photo']) && file_exists('../uploads/students/' . $student['photo'])): ?>
                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" class="student-photo">
            <?php else: ?>
                <div style="width:130px;height:150px;border:1px solid #CBD5E1;display:flex;align-items:center;justify-content:center;color:#64748B;">
                    Photo
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h5 style="font-weight:800;color:#0F172A;">Subject-wise Internal Marks</h5>

    <table class="marks-table">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Assignment<br>20</th>
                <th>Quiz<br>20</th>
                <th>Mid-Sem<br>30</th>
                <th>Practical<br>30</th>
                <th>Total<br>100</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($subjects) > 0): ?>
                <?php $i = 1; foreach ($subjects as $sub): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <td><?php echo $sub['assignment_marks']; ?></td>
                        <td><?php echo $sub['quiz_marks']; ?></td>
                        <td><?php echo $sub['mid_sem_marks']; ?></td>
                        <td><?php echo $sub['practical_marks']; ?></td>
                        <td><strong><?php echo $sub['total_internal']; ?></strong></td>
                    </tr>
                <?php endforeach; ?>

                <tr style="background:#E0E7FF;font-weight:800;">
                    <td colspan="7" style="text-align:right;">Grand Total</td>
                    <td><?php echo $total_obtained; ?> / <?php echo $total_max; ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;">No subjects / marks available</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-card">
            <h4><?php echo $total_obtained; ?></h4>
            <p>Obtained Marks</p>
        </div>

        <div class="summary-card">
            <h4><?php echo $total_max; ?></h4>
            <p>Maximum Marks</p>
        </div>

        <div class="summary-card">
            <h4><?php echo number_format($percentage, 2); ?>%</h4>
            <p>Percentage</p>
        </div>

        <div class="summary-card">
            <h4><?php echo $grade; ?></h4>
            <p>Grade</p>
        </div>
    </div>

    <div style="margin-top:20px;text-align:center;">
        <strong>Status:</strong>
        <?php if ($status == 'Pass'): ?>
            <span style="color:green;font-weight:800;">PASS</span>
        <?php else: ?>
            <span style="color:red;font-weight:800;">FAIL</span>
        <?php endif; ?>
    </div>

    <div class="signatures">
        <div class="sign">Class Teacher</div>
        <div class="sign">HOD</div>
        <div class="sign">Principal</div>
    </div>

    <div style="text-align:center;margin-top:25px;font-size:12px;color:#64748B;">
        Generated on <?php echo date('d-m-Y h:i A'); ?>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function downloadPDF() {
    const element = document.getElementById('reportCard');

    const opt = {
        margin: 0.2,
        filename: 'ReportCard_<?php echo preg_replace("/[^A-Za-z0-9]/", "_", $student['enrollment_no']); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}
</script>

</body>
</html>