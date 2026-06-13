<?php
session_start();
require_once 'config.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Fetch marks with subject details
$stmt = $pdo->prepare("
    SELECT m.*, s.subject_name, s.subject_code, s.max_marks, s.min_marks 
    FROM marks m 
    JOIN subjects s ON m.subject_id = s.id 
    WHERE m.student_id = ?
    ORDER BY s.subject_code
");
$stmt->execute([$student_id]);
$marks = $stmt->fetchAll();

// Calculate totals
$total_marks = 0;
$obtained_marks = 0;
$subject_count = count($marks);

foreach ($marks as $mark) {
    $total_marks += $mark['max_marks'];
    $obtained_marks += $mark['marks_obtained'];
}

$percentage = $subject_count > 0 ? ($obtained_marks / $total_marks) * 100 : 0;

// Determine grade
function getGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 40) return 'D';
    return 'F';
}

// Determine result
function getResult($marks) {
    foreach ($marks as $mark) {
        if ($mark['marks_obtained'] < $mark['min_marks']) {
            return 'FAIL';
        }
    }
    return 'PASS';
}

$grade = getGrade($percentage);
$result = getResult($marks);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - <?php echo htmlspecialchars($student['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }

        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-button button {
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .print-button button:hover {
            background: #1d4ed8;
        }

        /* A4 Page Setup */
        .report-card {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Header Section */
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .college-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }

        .college-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .college-name {
            font-size: 26px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .college-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #dc2626;
            margin-top: 10px;
            text-decoration: underline;
        }

        /* Student Info */
        .student-info {
            margin: 25px 0;
            border: 2px solid #ddd;
            padding: 15px;
            background: #f8fafc;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #334155;
        }

        .info-value {
            flex: 1;
            color: #1e293b;
        }

        /* Marks Table */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .marks-table th {
            background: #2563eb;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            border: 1px solid #1e40af;
        }

        .marks-table td {
            padding: 10px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
        }

        .marks-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .marks-table tbody tr:hover {
            background: #e0f2fe;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Summary Section */
        .summary {
            margin: 25px 0;
            padding: 15px;
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 5px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            background: white;
            border-radius: 3px;
        }

        .summary-label {
            font-weight: bold;
            color: #92400e;
        }

        .summary-value {
            color: #1e293b;
            font-weight: bold;
        }

        /* Result Badge */
        .result-section {
            text-align: center;
            margin: 20px 0;
        }

        .result-badge {
            display: inline-block;
            padding: 15px 40px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 5px;
            text-transform: uppercase;
        }

        .result-pass {
            background: #22c55e;
            color: white;
        }

        .result-fail {
            background: #ef4444;
            color: white;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            width: 200px;
            border-top: 2px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .issue-date {
            font-size: 12px;
            color: #666;
            margin-top: 30px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-button {
                display: none;
            }

            .report-card {
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 15mm;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }

        /* Status indicators */
        .status-pass {
            color: #16a34a;
            font-weight: bold;
        }

        .status-fail {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="print-button">
    <button onclick="window.print()">🖨️ Print Report Card</button>
</div>

<div class="report-card">
    <!-- Header -->
    <div class="header">
        <div class="college-logo">
            <!-- Replace with your college logo -->
            <img src="college-logo.png" alt="College Logo" onerror="this.src='https://via.placeholder.com/80?text=LOGO'">
        </div>
        <div class="college-name">XYZ College of Excellence</div>
        <div class="college-address">123 Education Street, Knowledge City - 123456</div>
        <div class="college-address">Phone: +91-1234567890 | Email: info@xyzcollege.edu</div>
        <div class="college-address">Website: www.xyzcollege.edu</div>
        <div class="report-title">ACADEMIC REPORT CARD</div>
    </div>

    <!-- Student Information -->
    <div class="student-info">
        <div class="info-row">
            <div class="info-label">Student Name:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Roll Number:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['roll_number']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Class:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['class']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Academic Session:</div>
            <div class="info-value">2024-2025</div>
        </div>
    </div>

    <!-- Marks Table -->
    <table class="marks-table">
        <thead>
            <tr>
                <th width="10%">S.No</th>
                <th width="20%">Subject Code</th>
                <th width="30%">Subject Name</th>
                <th width="15%" class="text-center">Max Marks</th>
                <th width="15%" class="text-center">Marks Obtained</th>
                <th width="10%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sno = 1;
            foreach ($marks as $mark): 
                $status = $mark['marks_obtained'] >= $mark['min_marks'] ? 'Pass' : 'Fail';
                $status_class = $status == 'Pass' ? 'status-pass' : 'status-fail';
            ?>
            <tr>
                <td class="text-center"><?php echo $sno++; ?></td>
                <td><?php echo htmlspecialchars($mark['subject_code']); ?></td>
                <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                <td class="text-center"><?php echo $mark['max_marks']; ?></td>
                <td class="text-center"><strong><?php echo $mark['marks_obtained']; ?></strong></td>
                <td class="text-center <?php echo $status_class; ?>"><?php echo $status; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e2e8f0; font-weight: bold;">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-center"><?php echo $total_marks; ?></td>
                <td class="text-center"><?php echo $obtained_marks; ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Summary Section -->
    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Marks:</span>
                <span class="summary-value"><?php echo $total_marks; ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Obtained Marks:</span>
                <span class="summary-value"><?php echo $obtained_marks; ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Percentage:</span>
                <span class="summary-value"><?php echo number_format($percentage, 2); ?>%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Grade:</span>
                <span class="summary-value"><?php echo $grade; ?></span>
            </div>
        </div>
    </div>

    <!-- Result -->
    <div class="result-section">
        <div class="result-badge <?php echo $result == 'PASS' ? 'result-pass' : 'result-fail'; ?>">
            <?php echo $result; ?>
        </div>
    </div>

    <!-- Footer with Signatures -->
    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">Class Teacher</div>
        </div>
        <div class="signature-box">
            <div class="issue-date">Date of Issue: <?php echo date('d-M-Y'); ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Principal</div>
        </div>
    </div>
</div>

</body>
</html>