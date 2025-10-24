<?php
include 'config.php';

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
        .student-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .fail { background-color: #ffcccc; }
        .pass { color: green; font-weight: bold; }
        .fail-text { color: red; font-weight: bold; }
        .total-row { background-color: #e6e6e6; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>STUDENT PORTAL - ACADEMIC RESULT</h1>
        <h3>Official Result Card</h3>
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
    
    $html .= '<tr class="' . $row_class . '">
                <td>' . $mark['subject_name'] . '</td>
                <td>' . $mark['max_marks'] . '</td>
                <td>' . $mark['marks_obtained'] . '</td>
                <td class="' . ($is_fail ? 'fail-text' : 'pass') . '">' . $status . '</td>
            </tr>';
}

$html .= '</tbody>
        <tfoot>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td><strong>' . $total_max . '</strong></td>
                <td><strong>' . $total_obtained . '</strong></td>
                <td class="' . ($all_passed ? 'pass' : 'fail-text') . '"><strong>' . $result_status . '</strong></td>
            </tr>
            <tr class="total-row">
                <td colspan="2"><strong>PERCENTAGE</strong></td>
                <td colspan="2"><strong>' . $percentage . '%</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>Generated on: ' . date('d-m-Y H:i:s') . '</p>
        <p><em>This is a computer generated result card.</em></p>
    </div>
</body>
</html>';

// Output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Result_' . $student['roll_number'] . '.pdf"');
echo $html;
exit;
?>
