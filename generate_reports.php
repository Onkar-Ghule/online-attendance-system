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

// Function to generate attendance report for a specific student and date range
function generateAttendanceReport($db, $student_id, $course_id, $start_date, $end_date) {
    $query = "SELECT date, status FROM attendance 
    WHERE 1=1";
    if ($student_id) {
        $query .= " AND  id = $student_id ";
    }
    if ($course_id) {
        $query .= " AND course_id = $course_id ";
    }
    if ($start_date && $end_date) {
        $query .= " AND date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE) 
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

// Generate CSV if requested
if (isset($_GET['generate_csv'])) {
    $student_id = pg_escape_string($db, $_GET['student_id'] ?? '');
    $course_id = pg_escape_string($db, $_GET['course_id'] ?? '');
    $start_date = pg_escape_string($db, $_GET['start_date'] ?? '');
    $end_date = pg_escape_string($db, $_GET['end_date'] ?? '');

    $query = "SELECT a.date, a.status, s.name AS student_name, c.course_name 
              FROM attendance a
              JOIN student s ON a.id = s.id
              JOIN courses c ON a.course_id = c.course_id
              WHERE 1=1";

    if ($student_id) {
        $query .= " AND s.id = '$student_id'";
    }
    if ($course_id) {
        $query .= " AND c.course_id = '$course_id'";
    }
    if ($start_date && $end_date) {
        $query .= " AND a.date BETWEEN '$start_date' AND '$end_date'";
    }

    $query .= " ORDER BY a.date DESC";
    $result = pg_query($db, $query);

    if (!$result) {
        echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
        exit;
    }

    // Generate CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=attendance_records.csv');
    $output = fopen('php://output', 'w');

    // Add CSV headers
    fputcsv($output, ['Date', 'Student ID', 'Student Name', 'Course Name', 'Status']);

    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, [ '"' . $row['date'] . '"', $student_id, $row['student_name'], $row['course_name'], $row['status']]);
    }

    fclose($output);
    exit;
}

// Get search parameters with safe escaping
$student_id = pg_escape_string($db, $_GET['student_id'] ?? '');
$course_id = pg_escape_string($db, $_GET['course_id'] ?? '');
$start_date = pg_escape_string($db, $_GET['start_date'] ?? '');
$end_date = pg_escape_string($db, $_GET['end_date'] ?? '');

// Query for filtering attendance records
$query = "SELECT a.date, a.status, s.name AS student_name, c.course_name 
          FROM attendance a
          JOIN student s ON a.id = s.id
          JOIN courses c ON a.course_id = c.course_id
          WHERE 1=1";

if ($student_id) {
    $query .= " AND s.id = '$student_id'";
}
if ($course_id) {
    $query .= " AND c.course_id = '$course_id'";
}
if ($start_date && $end_date) {
    $query .= " AND a.date BETWEEN '$start_date' AND '$end_date'";
}

$query .= " ORDER BY a.date DESC";
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($student_id || $course_id || $start_date || $end_date)) {
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
    <title>Attendance Records</title>
    <link rel="stylesheet" href="./attendance_record.css">
</head>
<body>
<form action="./admin_dashboard.php" method="post">
    <button type="submit" name="logout">Go to Dashboard</button>
</form>
<h2>Generate Attendance Report</h2>

<!-- Search/Filter Form -->
<form method="GET" action="">
    <label for="student_id">Student ID:</label>
    <input type="text" name="student_id" id="student_id" value="<?php echo ($student_id); ?>" >

    <label for="course_id">Course ID:</label>
    <input type="text" name="course_id" id="course_id" value="<?php echo ($course_id); ?>" >

    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" id="start_date" value="<?php echo ($start_date); ?>">

    <label for="end_date">End Date:</label>
    <input type="date" name="end_date" id="end_date" value="<?php echo ($end_date); ?>" >

    <button type="submit">Search</button>
    <button type="submit" name="generate_csv" value="1">Download CSV</button>
</form>

<!-- Display Search/Filter Results only after form submission -->
<?php if ($result && pg_num_rows($result) > 0): ?>
    <h2>Attendance Report</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Course Name</th>
            <th>Status</th>
        </tr>
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo ($row['date']); ?></td>
                <td><?php echo ($student_id); ?></td>
                <td><?php echo ($row['student_name']); ?></td>
                <td><?php echo ($row['course_name']); ?></td>
                <td><?php echo ($row['status']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No attendance records found.</p>
<?php endif; ?>
</body>
</html>
