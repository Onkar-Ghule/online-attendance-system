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

$professor_id = $_SESSION['user_id'];
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

// CSV export functionality
if (isset($_GET['export_csv'])) {
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

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=attendance_records.csv');
    echo "\xEF\xBB\xBF"; // Add BOM for UTF-8 compatibility with Excel
    $output = fopen('php://output', 'w');

    // Add CSV headers
    fputcsv($output, ['Date', 'Student Name', 'Course Name', 'Status']);

    // Fetch and write rows
    while ($row = pg_fetch_assoc($result)) {
        fputcsv($output, [
            '"' . $row['date'] . '"', // Wrap the date in quotes
            $row['student_name'],
            $row['course_name'],
            $row['status']
        ]);
    }

    fclose($output);
    exit;
}

// Fetch and filter attendance records
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
    <form action="./professor_dashboard.php" method="post">
        <button type="submit" name="logout">Go to Dashboard</button>
    </form>
    <h2>Search and View Attendance Records</h2>

    <!-- Search/Filter Form -->
    <form method="GET" action="">
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" id="student_id" value="<?php echo ($student_id); ?>">

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
        <button type="submit" name="export_csv" value="1">Download CSV</button>
    </form>

    <!-- Display Search/Filter Results -->
    <?php if ($result && pg_num_rows($result) > 0): ?>
        <h2>Attendance Report</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Student Name</th>
                <th>Course Name</th>
                <th>Status</th>
            </tr>
            <?php while ($row = pg_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No attendance records found.</p>
    <?php endif; ?>
</body>
</html>
