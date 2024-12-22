<?php
session_start();

// Connect to the database
$host = "host=127.0.0.1";
$port = "port=5432";
$dbname = "dbname=postgres";
$credentials = "user=postgres password=pass123";
$db = pg_connect("$host $port $dbname $credentials");

if (!$db) {
    echo "Error: Unable to open database\n";
    exit;
}

// Ensure that admin is logged in
if (!(isset($_SESSION['role']) && $_SESSION['role']==="admin")) {
    header("Location: index.php");
    exit();
}

// Fetch the list of courses for the admin (instead of a select, we'll use text fields for Course ID and Student ID)
$query = "SELECT course_id, course_name FROM courses";
$result = pg_query($db, $query);

if (!$result) {
    echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
    exit;
}

$courses = [];
while ($row = pg_fetch_assoc($result)) {
    $courses[] = $row;
}

// Enroll student when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    $course_id = pg_escape_string($db, $_POST['course_id']);
    $student_id = pg_escape_string($db, $_POST['student_id']);

    // Check if course and student exist
    $course_check_query = "SELECT course_id FROM courses WHERE course_id = $course_id";
    $student_check_query = "SELECT id FROM student WHERE id = $student_id";
    
    $course_check = pg_query($db, $course_check_query);
    $student_check = pg_query($db, $student_check_query);
    
    if (pg_num_rows($course_check) > 0 && pg_num_rows($student_check) > 0) {
        // Check if the student is already enrolled in the course
        $enrollment_check_query = "SELECT * FROM course_enrollment WHERE course_id = $course_id AND student_id = $student_id";
        $enrollment_check = pg_query($db, $enrollment_check_query);

        if (pg_num_rows($enrollment_check) > 0) {
            echo "<p class='error'>This student is already enrolled in the course.</p>";
        } else {
            // Insert enrollment record
            $enroll_query = "INSERT INTO course_enrollment (course_id, student_id) VALUES ($course_id, $student_id)";
            $enroll_result = pg_query($db, $enroll_query);

            if ($enroll_result) {
                echo "<p class='success'>Student enrolled successfully!</p>";
            } else {
                echo "<p class='error'>Error enrolling student: " . pg_last_error($db) . "</p>";
            }
        }
    } else {
        echo "<p class='error'>Invalid Course ID or Student ID.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Course Enrollment</title>
    <link rel="stylesheet" href="./course_enrollement.css">
</head>
<body>
    <form action="./admin_dashboard.php" method="post">
        <button type="submit" name="logout">Go to Dashboard</button>
    </form>
    <h2>Admin Dashboard - Course Enrollment</h2>

    <!-- Enrollment Form -->
    <form method="POST">
        <h3>Enroll a Student in a Course</h3>

        <!-- Course ID Field -->
        <label for="course_id">Course ID:</label>
        <input type="text" name="course_id" id="course_id" required>

        <!-- Student ID Field -->
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" id="student_id" required>

        <button type="submit" name="enroll_student">Enroll Student</button>
    </form>

    <!-- Success or Error Message -->
    <?php if (isset($enroll_result)): ?>
        <p class="<?php echo $enroll_result ? 'success' : 'error'; ?>">
            <?php echo $enroll_result ? 'Student enrolled successfully!' : 'Failed to enroll the student.'; ?>
        </p>
    <?php endif; ?>
</body>
</html>
