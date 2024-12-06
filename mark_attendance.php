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

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$professor_id = $_SESSION['user_id'];

// Fetch the courses taught by the professor
$query = "SELECT course_id, course_name FROM courses WHERE professor_id = $professor_id";
$result = pg_query($db, $query);

if (!$result) {
    echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
    exit;
}

$courses = [];
while ($row = pg_fetch_assoc($result)) {
    $courses[] = $row;
}

// Fetch students for the selected course
$students = [];
$selected_course_id = isset($_POST['course_id']) ? pg_escape_string($db, $_POST['course_id']) : '';
$date = isset($_POST['date']) ? pg_escape_string($db, $_POST['date']) : ''; // Capture the date here

if ($selected_course_id) {
    $query = "SELECT student.id, student.name FROM student
              JOIN course_enrollment ON student.id = course_enrollment.student_id
              WHERE course_enrollment.course_id = $selected_course_id";
    $result = pg_query($db, $query);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $students[] = $row;
        }
    } else {
        echo "<p>Error in fetching students: " . pg_last_error($db) . "</p>";
    }
}

// Mark attendance (when form is submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $course_id = pg_escape_string($db, $_POST['course_id']);
    $date = pg_escape_string($db, $_POST['date']);
    
    foreach ($_POST['student_id'] as $index => $student_id) {
        $student_id = pg_escape_string($db, $student_id);
        $status = pg_escape_string($db, $_POST['status'][$index]);

        // Check if the attendance for this student and date already exists
        $check_query = "SELECT * FROM attendance WHERE course_id = $course_id AND id = $student_id AND date = '$date'";
        $check_result = pg_query($db, $check_query);
        
        if (pg_num_rows($check_result) > 0) {
            echo "<p>Attendance for student ID $student_id on $date is already marked.</p>";
        } else {
            // If attendance doesn't exist, insert the record
            $query = "INSERT INTO attendance (course_id, id, date, status) VALUES ($course_id, $student_id, '$date', '$status')";
            $insert_result = pg_query($db, $query);

            if (!$insert_result) {
                echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
            }
        }
    }
    echo "<p>Attendance marked successfully.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>mark attendance</title>
    <link rel="stylesheet" href="./professor_dashboard.css">
</head>
<body>
<form action="./professor_dashboard.php" method="post">
        <button type="submit" name="logout">Go to Dashboard</button>
    </form>
    <h2>mark attendance </h2>

    <!-- List of Courses and Date Selection -->
    <h3>Your Courses:</h3>
    <form method="POST">
        <label for="course_id">Course:</label>
        <select name="course_id" id="course_id" onchange="this.form.submit()">
            <option value="">Select Course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo ($selected_course_id == $course['course_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" required onchange="this.form.submit()">
    </form>

    <?php if ($selected_course_id && $date && count($students) > 0): ?>
        <!-- Attendance Form for the selected course -->
        <h3>Mark Attendance for the Course: <?php echo htmlspecialchars($courses[array_search($selected_course_id, array_column($courses, 'course_id'))]['course_name']); ?></h3>
        
        <form method="POST">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>"> <!-- Hidden date field -->
            
            <h4>Student Attendance:</h4>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Mark Attendance</th>
                </tr>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td>
                        <select name="status[]" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                        </select>
                    </td>
                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                </tr>
            <?php endforeach; ?>
            </table>
            <button type="submit" name="mark_attendance">Mark Attendance</button>
        </form>
    <?php elseif ($selected_course_id && $date): ?>
        <p>No students found for this course.</p>
    <?php endif; ?>

</body>
</html>
