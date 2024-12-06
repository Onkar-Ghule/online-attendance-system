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

// Check if admin is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Fetch all courses for admin dropdown (for Course ID)
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

// Handle Report Generation
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generate_report'])) {
    $student_id = pg_escape_string($db, $_GET['student_id']);
    $course_id = pg_escape_string($db, $_GET['course_id']);
    $start_date = pg_escape_string($db, $_GET['start_date']);
    $end_date = pg_escape_string($db, $_GET['end_date']);

    // Query to fetch the attendance report based on student_id, course_id, and date range
    $query = "SELECT date, status FROM attendance 
              WHERE id = '$student_id' 
              AND course_id = '$course_id' 
              AND date BETWEEN '$start_date' AND '$end_date'
              ORDER BY date";

    $attendance_result = pg_query($db, $query);

    if (!$attendance_result) {
        echo "<p>Error in SQL query: " . pg_last_error($db) . "</p>";
    }
    
    $attendance_data = [];
    while ($row = pg_fetch_assoc($attendance_result)) {
        $attendance_data[] = $row;
    }

    // Optionally handle CSV Export
    if (isset($_GET['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Status']);
        foreach ($attendance_data as $data) {
            fputcsv($output, $data);
        }
        fclose($output);
        exit;
    }

    // Optionally handle PDF Export (You would need to install FPDF or use a similar library)
    if (isset($_GET['export_pdf'])) {
        // You can use libraries like FPDF or TCPDF to generate PDFs here
        // Example: Generate PDF (for simplicity, skipping actual PDF code here)
        echo "<p>PDF export feature is under construction.</p>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Attendance Report</title>
    <link rel="stylesheet" href="admin_dashboard.css"> <!-- Optional for styling -->
</head>
<body>
    <h2>Generate Attendance Report</h2>

    <!-- Form for inputting report criteria -->
    <form method="GET" action="">
        <!-- Student ID Field -->
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" id="student_id" required>

        <!-- Course ID Field -->
        <label for="course_id">Course ID:</label>
        <input type="text" name="course_id" id="course_id" required>

        <!-- Start Date Field -->
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" required>

        <!-- End Date Field -->
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" id="end_date" required>

        <!-- Generate Report Button -->
        <button type="submit" name="generate_report">Generate Report</button>
    </form>

    <br><br>
    <h3>Export Options</h3>
    <!-- Export buttons to generate CSV or PDF -->
    <form method="GET" action="">
        <button type="submit" name="export_csv" <?php echo empty($attendance_data) ? 'disabled' : ''; ?>>Export as CSV</button>
        <button type="submit" name="export_pdf" <?php echo empty($attendance_data) ? 'disabled' : ''; ?>>Export as PDF</button>
    </form>

    <?php if (isset($attendance_data) && count($attendance_data) > 0): ?>
        <h3>Attendance Report</h3>
        <table border="1">
            <tr>
                <th>Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($attendance_data as $data): ?>
                <tr>
                    <td><?php echo htmlspecialchars($data['date']); ?></td>
                    <td><?php echo htmlspecialchars($data['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php elseif (isset($attendance_data)): ?>
        <p>No records found for the selected criteria.</p>
    <?php endif; ?>
</body>
</html>
