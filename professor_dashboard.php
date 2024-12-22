<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>professor Dashboard</title>
    <link rel="stylesheet" href="styles1.css">
</head>
<body>
    
<div id="administrator-dashboard" class="container">
        <h3>professor_dashboard</h3>
        <p>Welcome,, <?php echo strtoupper($username); ?>!</p>
      
        <h4>System Overview</h4>
        <ul class="dashboard-menu">
            <li><a href="attendance_records_course.php">View Attendance Records</a></li>
            <li><a href="mark_attendance.php">mark attendance</a></li>
            <li><a href=" generate_reports_course.php">generate attendance</a></li>
           
        </ul>
        
        <form action="index.php" method="post" class="logout-form">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
    </div>  
   
</body>
</html>
