<?php
session_start();
if (!(isset($_SESSION['role']) && $_SESSION['role']==="admin")) {
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
    <title>Administrator Dashboard</title>
    <link rel="stylesheet" href="styles1.css">
</head>
<body>
    
<div id="administrator-dashboard" class="container">
        <h3>Administrator Dashboard</h3>
        <p>Welcome, <?php echo ucwords($username); ?>!</p>
      
        <h4>System Overview</h4>
        <ul class="dashboard-menu">
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_courses.php">Manage Courses</a></li>
            <li><a href="course_enrollment.php">Course Enrollment</a></li>
            <li><a href="attendance_records.php">View Attendance Records</a></li>
            <li><a href="generate_reports.php">Generate Reports</a></li>
            <!-- <li><a href="send_notifications.php">Send Notifications</a></li> -->
            
        </ul>
        
        <form action="index.php" method="post" class="logout-form">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
    </div>  
   
</body>
</html>
