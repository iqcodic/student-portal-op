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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .student-photo { max-width: 200px; max-height: 200px; border: 3px solid #28a745; }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1><i class="fas fa-graduation-cap"></i> Student Result Portal</h1>
            <p class="lead">Check your academic results by Roll Number</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-center"><i class="fas fa-search"></i> Search Your Result</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="roll_number" class="form-label">Enter Roll Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="roll_number" name="roll_number" 
                                           placeholder="e.g., STU0001" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search Result
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger mt-3 text-center">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
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
                    <div class="card result-card shadow">
                        <div class="card-header bg-success text-white text-center">
                            <h4 class="mb-0"><i class="fas fa-award"></i> Academic Result</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <?php if($student['student_image'] && file_exists($student['student_image'])): ?>
                                        <img src="<?php echo $student['student_image']; ?>" class="student-photo img-fluid rounded mb-3" alt="Student Photo">
                                    <?php else: ?>
                                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-user-graduate fa-5x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($student['student_name']); ?></h5>
                                    <p class="text-muted">
                                        <i class="fas fa-id-card"></i> Roll No: <?php echo htmlspecialchars($student['roll_number']); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-user-friends"></i> Father: <?php echo htmlspecialchars($student['father_name']); ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-8">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th><i class="fas fa-book"></i> Subject</th>
                                                <th><i class="fas fa-chart-line"></i> Max Marks</th>
                                                <th><i class="fas fa-trophy"></i> Obtained</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
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
                                                        <i class="<?php echo $is_fail ? 'fas fa-times' : 'fas fa-check'; ?>"></i>
                                                        <?php echo $is_fail ? 'FAIL' : 'PASS'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <td><strong><i class="fas fa-calculator"></i> TOTAL</strong></td>
                                                <td><strong><?php echo $total_max; ?></strong></td>
                                                <td><strong><?php echo $total_obtained; ?></strong></td>
                                                <td class="<?php echo $result_class; ?>">
                                                    <strong>
                                                        <i class="<?php echo $all_passed ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
                                                        <?php echo $result_status; ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"><strong><i class="fas fa-percentage"></i> PERCENTAGE</strong></td>
                                                <td colspan="2"><strong><?php echo $percentage; ?>%</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    
                                    <div class="text-center mt-4">
                                        <a href="pdf.php?roll_number=<?php echo $student['roll_number']; ?>" class="btn btn-success btn-lg">
                                            <i class="fas fa-download"></i> Download PDF Result
                                        </a>
                                        <button onclick="window.print()" class="btn btn-primary btn-lg">
                                            <i class="fas fa-print"></i> Print Result
                                        </button>
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
            <p>&copy; 2024 Student Result Portal. All rights reserved.</p>
            <p class="mb-0">Developed with ❤️ for students</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
