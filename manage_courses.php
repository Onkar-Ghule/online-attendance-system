<?php
session_start();
    if (!(isset($_SESSION['role']) && $_SESSION['role']==="admin")) {
        header("Location: index.php");
        exit();
    }
$host = "host=127.0.0.1";
$port = "port=5432";
$dbname = "dbname=postgres";
$credentials = "user=postgres password=pass123";

// Establish database connection
$db = pg_connect("$host $port $dbname $credentials");
if (!$db) {
    echo "Error: Unable to open database\n";
    exit;
}

// Initialize variables for form fields
$edit_mode = false;
$course_name = $course_code = $department = $professor_id = '';

// Check if editing an existing course
if (isset($_GET['id'])) {
    $course_id = (int)$_GET['id'];
    $edit_mode = true;


    // Fetch existing course data
    $query = "SELECT * FROM courses WHERE course_id = $course_id";
    $result = pg_query($db, $query);
     
    
    if ($row = pg_fetch_assoc($result)) {
        $course_name = $row['course_name'];
        $course_code = $row['course_code'];
        $department = $row['department'];
        $professor_id = $row['professor_id'];
    }
}

// Add or Update Course
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_or_update'])) {
    $course_name = pg_escape_string($db, $_POST['course_name']);
    $course_code = pg_escape_string($db, $_POST['course_code']);
    $department = pg_escape_string($db, $_POST['department']);
    $professor_id = (int)$_POST['professor_id'];

    // Validate Professor ID
    $query1 = "SELECT * FROM professor WHERE id = $professor_id";
    $result1 = pg_query($db, $query1);
    if (pg_num_rows($result1) > 0) {
        if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
            // Update existing course
            $course_id = (int)$_POST['course_id'];
            $query = "UPDATE courses SET course_name='$course_name', course_code='$course_code',
                      department='$department', professor_id=$professor_id WHERE course_id = $course_id";
            $result = pg_query($db, $query);

            if ($result) {
                header("Location: manage_courses.php?success=updated");
                exit;
            } else {
                $message = "Error updating course.";
            }
        } else {
            // Add new course
            $query = "INSERT INTO courses (course_name, course_code, department, professor_id)
                      VALUES ('$course_name', '$course_code', '$department', $professor_id)";
            $result = pg_query($db, $query);

            if ($result) {
                header("Location: manage_courses.php?success=added");
                exit;
            } else {
                $message = "Error adding course.";
            }
        }
    } else {
        $message = "Professor ID not found!";
    }
}

// Delete Course
if (isset($_GET['delete_course'])) {
    $course_id = (int)$_GET['delete_course'];

    $query = "DELETE FROM courses WHERE course_id = $course_id";
    $result = pg_query($db, $query);

    if ($result) {
        header("Location: manage_courses.php?success=deleted");
        exit;
    } else {
        $message = "Error deleting course.";
    }
}

// Search for Course by ID
$search_result = null;
if (isset($_POST['search_course'])) {
    $search_course_id = (int)$_POST['search_course_id'];
    $search_query = "SELECT * FROM courses WHERE course_id = $search_course_id";
    $search_result = pg_query($db, $search_query);
    if (pg_num_rows($search_result) == 0) {
        $message = "No course found with ID $search_course_id.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Online Attendance System</title>
    <link rel="stylesheet" href="manage_course.css">
</head>
<body>
    <?php
    // Display success messages based on URL parameters
    if (isset($_GET['success'])) {
        if ($_GET['success'] == "added") echo "<p>Course added successfully.</p>";
        elseif ($_GET['success'] == "updated") echo "<p>Course updated successfully.</p>";
        elseif ($_GET['success'] == "deleted") echo "<p>Course deleted successfully.</p>";
    }
    if (isset($message)) echo "<p>$message</p>";
    ?>
 <form action="./admin_dashboard.php" method="post">
            <button type="submit" name="logout">Go to Dashboard</button>
        </form>
    <!-- Course Form -->
    <form id="courseForm" method="POST" action="manage_courses.php">
        <input type="hidden" name="course_id" value="<?php echo isset($course_id) ? $course_id : ''; ?>">
        <input type="text" name="course_name" placeholder="Course Name" value="<?php echo $course_name; ?>" required>
        <input type="text" name="course_code" placeholder="Course Code" value="<?php echo $course_code; ?>" required>
        <input type="text" name="department" placeholder="Department" value="<?php echo $department; ?>" required>
        <input type="number" name="professor_id" placeholder="Enter Professor ID" value="<?php echo $professor_id; ?>" required>
        <button type="submit" name="add_or_update"><?php echo $edit_mode ? 'Update Course' : 'Add Course'; ?></button>
    </form>

    <!-- Search Form -->
    <form method="POST" action="manage_courses.php">
        <input type="number" name="search_course_id" placeholder="Enter Course ID to Search" required>
        <button type="submit" name="search_course">Search</button>
    </form>

    <?php
    // Display Search Results if found
    if ($search_result && pg_num_rows($search_result) > 0) {
        echo "<h2>Search Results:</h2>";
        echo "<table border='1'>";
        echo "<tr>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Course Code</th>
                <th>Department</th>
                <th>Professor ID</th>
                <th>Actions</th>
              </tr>";
        while ($row = pg_fetch_assoc($search_result)) {
            echo "<tr>";
            echo "<td>" . $row['course_id'] . "</td>";
            echo "<td>" . $row['course_name'] . "</td>";
            echo "<td>" . $row['course_code'] . "</td>";
            echo "<td>" . $row['department'] . "</td>";
            echo "<td>" . $row['professor_id'] . "</td>";
            echo "<td>
            <a href='manage_courses.php?id=" . $row['course_id'] . "'>Edit</a> |
            <a href='manage_courses.php?delete_course=" . $row['course_id'] . "' onclick='return confirm(\"Are you sure?\");'>Delete</a>
          </td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <!-- Display All Courses -->
    <h2>All Courses:</h2>
    <?php
    $query = "SELECT * FROM courses";
    $result = pg_query($db, $query);

    echo "<table border='1'>";
    echo "<tr>
            <th>Course ID</th>
            <th>Course Name</th>
            <th>Course Code</th>
            <th>Department</th>
            <th>Professor ID</th>
            <th>Actions</th>
          </tr>";

    while ($row = pg_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['course_id'] . "</td>";
        echo "<td>" . $row['course_name'] . "</td>";
        echo "<td>" . $row['course_code'] . "</td>";
        echo "<td>" . $row['department'] . "</td>";
        echo "<td>" . $row['professor_id'] . "</td>";
        echo "<td>
                <a href='manage_courses.php?id=" . $row['course_id'] . "'>Edit</a> |
                <a href='manage_courses.php?delete_course=" . $row['course_id'] . "' onclick='return confirm(\"Are you sure?\");'>Delete</a>
              </td>";
        echo "</tr>";
        $professor_query = "SELECT * FROM professor WHERE id = " . $row['professor_id'];
        $professor_result = pg_query($db, $professor_query);
        $professor = pg_fetch_assoc($professor_result);
        if (pg_num_rows($professor_result) > 0) {
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Address</th>
                </tr>
                <tr>
                    <td>" . $professor['id'] . "</td>
                    <td>" . $professor['name'] . "</td>
                    <td>" . $professor['email'] . "</td>
                    <td>" . $professor['department'] . "</td>
                    <td>" . $professor['phone'] . "</td>
                    <td>" . $professor['address'] . "</td>
                </tr>
            </table>";
        }
    }
    echo "</table>";
    ?>
</body>
</html>
