<?php
// Database connection
$host = 'sql100.infinityfree.com';
$dbname = 'if0_40190613_studentportal';
$username = 'if0_40190613';
$password = 'nsZHXTsMtGo';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if(!isset($_GET['roll_number'])) {
    die("Roll number is required");
}

$roll_number = $_GET['roll_number'];

// Fetch student by roll number
$sql = "SELECT * FROM students WHERE roll_number = :roll_number";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':roll_number', $roll_number, PDO::PARAM_STR);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    die("Student not found");
}

// Fetch student marks
$sql = "SELECT sm.*, s.subject_name, s.max_marks 
        FROM student_marks sm 
        JOIN subjects s ON sm.subject_id = s.id 
        WHERE sm.student_id = :student_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':student_id', $student['id'], PDO::PARAM_INT);
$stmt->execute();
$marks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate result
$total_obtained = 0;
$total_max = 0;
$all_passed = true;

foreach($marks as $mark) {
    $total_obtained += $mark['marks_obtained'];
    $total_max += $mark['max_marks'];
    if($mark['marks_obtained'] < ($mark['max_marks'] / 2)) {
        $all_passed = false;
    }
}

$percentage = $total_max > 0 ? round(($total_obtained / $total_max) * 100, 2) : 0;
$result_status = $all_passed ? 'PASS' : 'FAIL';

// Create HTML PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Result - ' . $student['roll_number'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .student-info { margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        th { background-color: #343a40; color: white; }
        .fail { background-color: #ffcccc; }
        .pass { color: #28a745; font-weight: bold; }
        .fail-text { color: #dc3545; font-weight: bold; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .result-badge { padding: 5px 10px; border-radius: 20px; color: white; font-weight: bold; }
        .pass-badge { background-color: #28a745; }
        .fail-badge { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 STUDENT RESULT PORTAL</h1>
        <h3>Official Academic Result Card</h3>
    </div>
    
    <div class="student-info">
        <p><strong>Student Name:</strong> ' . $student['student_name'] . '</p>
        <p><strong>Father Name:</strong> ' . $student['father_name'] . '</p>
        <p><strong>Roll Number:</strong> ' . $student['roll_number'] . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Max Marks</th>
                <th>Obtained Marks</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

foreach($marks as $mark) {
    $is_fail = $mark['marks_obtained'] < ($mark['max_marks'] / 2);
    $status = $is_fail ? 'FAIL' : 'PASS';
    $row_class = $is_fail ? 'fail' : '';
    $badge_class = $is_fail ? 'fail-badge' : 'pass-badge';
    
    $html .= '<tr class="' . $row_class . '">
                <td>' . $mark['subject_name'] . '</td>
                <td>' . $mark['max_marks'] . '</td>
                <td>' . $mark['marks_obtained'] . '</td>
                <td><span class="result-badge ' . $badge_class . '">' . $status . '</span></td>
            </tr>';
}

$html .= '</tbody>
        <tfoot>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td><strong>' . $total_max . '</strong></td>
                <td><strong>' . $total_obtained . '</strong></td>
                <td>
                    <span class="result-badge ' . ($all_passed ? 'pass-badge' : 'fail-badge') . '">
                        ' . $result_status . '
                    </span>
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="2"><strong>PERCENTAGE</strong></td>
                <td colspan="2"><strong>' . $percentage . '%</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p><strong>Generated on:</strong> ' . date('d-m-Y H:i:s') . '</p>
        <p><em>This is a computer generated result card. No signature required.</em></p>
    </div>
</body>
</html>';

// Output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Result_' . $student['roll_number'] . '.pdf"');
echo $html;
exit;
?>
