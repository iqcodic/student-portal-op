<?php
include 'config.php';

$error = '';
$student = null;
$marks = null;

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $roll_number = trim($_POST['roll_number']);
    
    // Fetch student by roll number
    $sql = "SELECT * FROM students WHERE roll_number = :roll_number";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':roll_number', $roll_number, PDO::PARAM_STR);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($student) {
        // Fetch student marks
        $sql = "SELECT sm.*, s.subject_name, s.max_marks 
                FROM student_marks sm 
                JOIN subjects s ON sm.subject_id = s.id 
                WHERE sm.student_id = :student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':student_id', $student['id'], PDO::PARAM_INT);
        $stmt->execute();
        $marks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Student not found with Roll Number: " . htmlspecialchars($roll_number);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .pass-status { color: green; font-weight: bold; }
        .fail-status { color: red; font-weight: bold; }
        .fail-mark { background-color: #ffcccc; }
        .result-card { border: 2px solid #28a745; }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Student Result Portal</h1>
            <p class="lead">Check your academic results by Roll Number</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Search Your Result</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="roll_number" class="form-label">Enter Roll Number</label>
                                <input type="text" class="form-control" id="roll_number" name="roll_number" 
                                       placeholder="e.g., STU0001" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Search Result</button>
                        </form>
                    </div>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display Result -->
        <?php if($student && $marks): 
            // Calculate total and result
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
            $result_class = $all_passed ? 'pass-status' : 'fail-status';
        ?>
            <div class="row justify-content-center mt-4">
                <div class="col-md-10">
                    <div class="card result-card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Academic Result</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <?php if($student['student_image'] && file_exists($student['student_image'])): ?>
                                        <img src="<?php echo $student['student_image']; ?>" class="img-fluid rounded mb-3" style="max-height: 200px;" alt="Student Photo">
                                    <?php else: ?>
                                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-user fa-5x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($student['student_name']); ?></h5>
                                    <p class="text-muted">Roll No: <?php echo htmlspecialchars($student['roll_number']); ?></p>
                                </div>
                                
                                <div class="col-md-8">
                                    <table class="table table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Max Marks</th>
                                                <th>Obtained</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($marks as $mark): 
                                                $is_fail = $mark['marks_obtained'] < ($mark['max_marks'] / 2);
                                                $row_class = $is_fail ? 'fail-mark' : '';
                                            ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                                    <td><?php echo $mark['max_marks']; ?></td>
                                                    <td><?php echo $mark['marks_obtained']; ?></td>
                                                    <td class="<?php echo $is_fail ? 'fail-status' : 'pass-status'; ?>">
                                                        <?php echo $is_fail ? 'FAIL' : 'PASS'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <td><strong>Total</strong></td>
                                                <td><strong><?php echo $total_max; ?></strong></td>
                                                <td><strong><?php echo $total_obtained; ?></strong></td>
                                                <td class="<?php echo $result_class; ?>">
                                                    <strong><?php echo $result_status; ?></strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"><strong>Percentage</strong></td>
                                                <td colspan="2"><strong><?php echo $percentage; ?>%</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    
                                    <div class="text-center mt-3">
                                        <a href="student_generate_pdf.php?roll_number=<?php echo $student['roll_number']; ?>" class="btn btn-success btn-lg">
                                            <i class="fas fa-download"></i> Download PDF Result
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container text-center">
            <p>&copy; 2024 Student Portal. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
