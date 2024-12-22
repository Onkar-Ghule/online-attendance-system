<?php
session_start();

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
$student_id = $_SESSION['user_id']; // Student ID from session
// Fetch the courses available to the student
$query = "SELECT c.course_id, c.course_name 
          FROM courses c
          JOIN course_enrollment ce ON c.course_id = ce.course_id
          WHERE ce.student_id = $student_id";
$result = pg_query($db, $query);

if (!$result) {
    echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
    exit;
}

$courses = [];
while ($row = pg_fetch_assoc($result)) {
    $courses[] = $row;
}

// Function to generate attendance report for a specific student and date range
function generateAttendanceReport($db, $student_id, $course_id, $start_date, $end_date) {
    $query = "SELECT date, status FROM attendance 
              WHERE     1=1";
    if ($student_id) {
        $query .= " AND  id = $student_id ";
    }
    if ($course_id) {
        $query .= " AND course_id = $course_id ";
    }
    if ($start_date && $end_date) {
        $query .= "date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) 
    ORDER BY date";
    }
    $result = pg_query($db, $query);

    if (!$result) {
        echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
        return [];
    }

    $present_count = 0;
    $total_count = 0;
    $attendance_data = [];

    while ($row = pg_fetch_assoc($result)) {
        $attendance_data[] = $row;
        $total_count++;
        if ($row['status'] == 'present') {
            $present_count++;
        }
    }

    $attendance_percentage = $total_count > 0 ? ($present_count / $total_count) * 100 : 0;

    return [
        'attendance_data' => $attendance_data,
        'total_present' => $present_count,
        'total_absent' => $total_count - $present_count,
        'attendance_percentage' => $attendance_percentage
    ];
}

// Get search parameters with safe escaping
$course_id = pg_escape_string($db, $_GET['course_id'] ?? '');
$start_date = pg_escape_string($db, $_GET['start_date'] ?? '');
$end_date = pg_escape_string($db, $_GET['end_date'] ?? '');

// Query for filtering attendance records for the student
$query = "SELECT a.date, a.status, c.course_name 
          FROM attendance a
          JOIN courses c ON a.course_id = c.course_id
          WHERE a.id = $    ";

if ($course_id) {
    $query .= " AND c.course_id = '$course_id'";
}
if ($start_date && $end_date) {
    $query .= " AND a.date BETWEEN '$start_date' AND '$end_date'";
}

$query .= " ORDER BY a.date DESC";
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($course_id || $start_date || $end_date)) {
    $result = pg_query($db, $query);

    if (!$result) {
        echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Attendance Records</title>
    <link rel="stylesheet" href="./attendance_record.css">
</head>
<body>
   
    
    <h2>Search and View Your Attendance Records</h2>
    
    <!-- Search/Filter Form -->
    <form method="GET" action="">
        <label for="course_id">Course:</label>
        <select name="course_id" id="course_id" required>
            <option value="">Select a course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo ($course_id == $course['course_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" value="<?php echo ($start_date); ?>">

        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" id="end_date" value="<?php echo ($end_date); ?>">

        <button type="submit">Search</button>
    </form>
    
    <!-- Display Search/Filter Results only after form submission -->
    <?php if ($result && pg_num_rows($result) > 0): ?>
        <?php
        $report = generateAttendanceReport($db, $student_id, $course_id, $start_date, $end_date);
        ?>
        <h2>Attendance Report</h2>
        <table>
            <tr>
                <th>Total Present</th>
                <th>Total Absent</th>
                <th>Attendance Percentage</th>
            </tr>
            <tr>
                <td><?php echo $report['total_present']; ?></td>
                <td><?php echo $report['total_absent']; ?></td>
                <td><?php echo round($report['attendance_percentage'], 2); ?>%</td>
            </tr>
        </table>
        <br>    
        <table>
            <tr>
                <th>Date</th>
                <th>Course Name</th>
                <th>Status</th>
            </tr>
            <?php while ($row = pg_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo ($row['date']); ?></td>
                    <td><?php echo ($row['course_name']); ?></td>
                    <td><?php echo ($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No attendance records found.</p>
    <?php endif; ?>
    <form action="./index.php" method="post">
        <button type="submit" name="logout">logout</button>
    </form>
</body>
</html>
