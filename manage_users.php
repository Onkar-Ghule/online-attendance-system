    <?php
    session_start();
    if (!(isset($_SESSION['role']) && $_SESSION['role']==="admin")) {
        header("Location: index.php");
        exit();
    }

    //connecting to database
    $host = "host=127.0.0.1";
    $port = "port=5432";
    $dbname = "dbname=postgres";
    $credentials = "user=postgres password=pass123";
    $db = pg_connect("$host $port $dbname $credentials");

    if (!$db) {
        echo "Error: Unable to open database\n";
        exit;
    }

    // Handle form submission for adding a new user
    if (isset($_POST['add_user'])) {
        $username = pg_escape_string($db, $_POST['username']);
        $id = pg_escape_string($db, $_POST['id']);
        $password = pg_escape_string($db, $_POST['password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = $_POST['role'];

        if ($role == 'professor') {
            // Check if all professor fields are set
            if (isset($_POST['ema'], $_POST['department'], $_POST['phone'], $_POST['address'])) {
                
                $email = pg_escape_string($db, $_POST['ema']);
                $department = pg_escape_string($db, $_POST['department']);
                $phone = pg_escape_string($db, $_POST['phone']);
                $address = pg_escape_string($db, $_POST['address']);
                $date_joined = date('Y-m-d'); // sets Current date as joined date 
                // querry to add new professor
                $query = "INSERT INTO professor (id, name, email, password, department, role, date_joined, phone, address)
                        VALUES ('$id', '$username', '$email', '$hashed_password', '$department', '$role', '$date_joined', '$phone', '$address')";
                $result = pg_query($db, $query);
            } else {
                $message = "Please fill in all professor details.";
            }
        } else {
            // Check if all student fields are set
            if (isset($_POST['course'], $_POST['year'], $_POST['section'], $_POST['email'], $_POST['contact_number'], )) {
                $course = pg_escape_string($db, $_POST['course']);
                $year = pg_escape_string($db, $_POST['year']);
                $section = pg_escape_string($db, $_POST['section']);
                $email = pg_escape_string($db, $_POST['email']);
                $contact_number = pg_escape_string($db, $_POST['contact_number']);

                $query = "INSERT INTO student (id, name, password, course, year, section, email, contact_number)
                        VALUES ('$id', '$username', '$hashed_password', '$course', '$year', '$section', '$email', '$contact_number')";
                $result = pg_query($db, $query);
            } else {
                $message = "Please fill in all student details.";
            }
        }

        if (isset($result) && $result) {
            $message = "User added successfully!";
        } else {
            $message = "Error adding user.";
        }
    }

    // Handle update user request
    if (isset($_POST['update_user'])) {
        $id = pg_escape_string($db, $_POST['id']);
        $role = $_POST['role'];
        $update_field = pg_escape_string($db, $_POST['update_field']);
        $new_value = pg_escape_string($db, $_POST['new_value']);

        // Construct the query based on the selected field
        if ($role == 'professor') {
            $query = "UPDATE professor SET $update_field = '$new_value' WHERE id = '$id'";
        } else {
            $query = "UPDATE student SET $update_field = '$new_value' WHERE id = '$id'";
        }

        $result = pg_query($db, $query);

        if ($result) {
            $message = "User information updated successfully!";
        } else {
            $message = "Error updating user information.";
        }
    }
    // Handle delete user request
    if (isset($_POST['delete_user'])) {
        $id = pg_escape_string($db, $_POST['id']);
        $role = $_POST['role'];

        if ($role == 'professor') {
            $query = "DELETE FROM professor WHERE id = '$id'";
        } else {
            $query = "DELETE FROM student WHERE id = '$id'";
        }

        $result = pg_query($db, $query);

        if ($result) {
            $message = "User deleted successfully!";
        } else {
            $message = "Error deleting user.";
        }
    }
    if (isset($_POST['search_user'])) {
        $search_id = pg_escape_string($db, $_POST['search_id']);
        $role = $_POST['role'];

        if ($role == 'professor') {
            // Query for professor
            $query = "SELECT * FROM professor WHERE id = '$search_id'";
            $result = pg_query($db, $query);

            if (pg_num_rows($result) > 0) {
                $user = pg_fetch_assoc($result);
                $user_type = 'professor';
            } else {
                $message = "No professor found with the ID: $search_id";
            }
        } elseif ($role == 'student') {
            // Query for student
            $query = "SELECT * FROM student WHERE id = '$search_id'";
            $result = pg_query($db, $query);

            if (pg_num_rows($result) > 0) {
                $user = pg_fetch_assoc($result);
                $user_type = 'student';
            } else {
                $message = "No student found with the ID: $search_id";
            }
        } else {
            $message = "Please select a role.";
        }
    }
   

    // Retrieve all professors and students for display
    $professors = pg_query($db, "SELECT * FROM professor");
    $students = pg_query($db, "SELECT * FROM student");
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Users</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="manage_users.css">
    </head>
    <body>
    
        <h2>Manage Users</h2>
        <form action="./admin_dashboard.php" method="post">
            <button type="submit" name="logout">Go to Dashboard</button>
        </form>
        <!-- Display Success/Error Messages -->
        <?php if (isset($message)) { echo "<p>$message</p>"; } ?>

        <form action="manage_users.php" method="post">
        <input type="text" name="username" placeholder="Name" required>
        <input type="text" name="id" placeholder="User ID" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
        <option value="" disabled selected>Select Role</option>
            <option value="professor">Professor</option>
            <option value="student">Student</option>
        </select>

        <!-- Dynamic fields for professor -->
        <div id="professor-fields" style="display:none;">
            <input type="email" name="ema" placeholder="Email">
            <input type="text" name="department" placeholder="Department">
            <input type="text" name="phone" placeholder="Phone">
            <textarea name="address" placeholder="Address"></textarea>
        </div>

        <!-- Dynamic fields for student -->
        <div id="student-fields" style="display:none;">
            <input type="text" name="course" placeholder="Course">
            <input type="text" name="year" placeholder="Year">
            <input type="text" name="section" placeholder="Section">
            <input type="email" name="email" placeholder="Email">
            <input type="text" name="contact_number" placeholder="Contact Number">
        </div>

        <button type="submit" name="add_user">Add User</button>
    </form>
    <!-- Search Form -->
<form action="manage_users.php" method="post">
    <input type="text" name="search_id" placeholder="Search by ID" required>
    
    <select name="role" required id="role_select">
        <option value="" disabled selected>Select Role</option>
        <option value="professor">Professor</option>
        <option value="student">Student</option>
    </select>
    
    <button type="submit" name="search_user">Search</button>
</form>


<!-- displaying searched the users in table -->

<?php if (isset($user)): ?>
    <h3>User Found: <?php echo ucfirst(string: $user_type); ?></h3>
    
    <?php
    if ($user_type == "professor") {
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
                    <td>$user[id]</td>
                    <td> $user[name]</td>
                    <td> $user[email]</td>
                    <td> $user[department]</td>
                    <td>$user[phone]</td>
                    <td>$user[address]</td>
                </tr>
            </table>";
    } else {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                </tr>
                <tr>
                    <td>$user[id]</td>
                    <td> $user[name]</td>
                    <td> $user[email]</td>
                    <td> $user[course]</td>
                    <td>$user[year]</td>
                    <td>$user[section]</td>
                </tr>
            </table>";
    }
    ?>

    <!-- Update Form -->
    <form action="manage_users.php" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="role" value="<?php echo $user_type; ?>">

        <select name="update_field" required id="update_field_select">
            <option value="" disabled selected>Select Field to Update</option>
        </select>

        <input type="text" name="new_value" placeholder="New Value" required>
        <button type="submit" name="update_user">Update</button>
    </form>

    <!-- Delete Form -->
    <form action="manage_users.php" method="post">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="role" value="<?php echo $user_type; ?>">
        <button type="submit" name="delete_user">Delete</button>
    </form>

<?php endif; ?>


    <!-- Display Success/Error Messages -->
    <?php if (isset($message)) { echo "<p>$message</p>"; } ?>

        <!-- User Lists -->
        <h3>Professors</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
            <?php while ($professor = pg_fetch_assoc($professors)): ?>
            <tr>
                <td><?php echo $professor['id']; ?></td>
                <td><?php echo $professor['name']; ?></td>
                <td><?php echo $professor['email']; ?></td>
                <td><?php echo $professor['department']; ?></td>
                <td><?php echo $professor['phone']; ?></td>
                <td><?php echo $professor['address']; ?></td>
                <td>
    <!-- Update Form -->
    <form action="manage_users.php" method="post">
        <input type="hidden" name="id" value="<?php echo $professor['id']; ?>"> <!-- ID of the user being updated -->
        <input type="hidden" name="role" value="professor"> <!-- or 'student' based on the user role -->

        <select name="update_field" id="update_field" required>
            <option value="" disabled selected>Select Field to Update</option>
            <option value="name">Name</option>
            <option value="email">Email</option>
            <option value="contact_number">Contact Number</option>
            <option value="department">Department</option>
            <option value="address">Address</option>
        </select>

        <div id="update_input" style="display:none;">
            <label for="new_value">New Value:</label>
            <input type="text" name="new_value" id="new_value" required>
        </div>

        <button type="submit" name="update_user">Update</button>
    </form>

    <form action="manage_users.php" method="post">  
        <input type="hidden" name="id" value="<?php echo $professor['id']; ?>">
        <input type="hidden" name="role" value="professor">
        <button type="submit" name="delete_user" >Delete</button>
    </form>
    </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h3>Students</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Course</th>
                <th>Year</th>
                <th>Section</th>
            </tr>
            <?php while ($student = pg_fetch_assoc($students)): ?>
            <tr>
                <td><?php echo $student['id']; ?></td>
                <td><?php echo $student['name']; ?></td>
                <td><?php echo $student['email']; ?></td>
                <td><?php echo $student['course']; ?></td>
                <td><?php echo $student['year']; ?></td>
                <td><?php echo $student['section']; ?></td>
                <td>
    <!-- Update Form for Student -->
    <form action="manage_users.php" method="post">
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>"> <!-- ID of the student being updated -->
        <input type="hidden" name="role" value="student"> <!-- Role is 'student' for this form -->

        <select name="update_field" id="update_field_student" required>
            <option value="" disabled selected>Select Field to Update</option>
            <option value="name">Name</option>
            <option value="password">Password</option>
            <option value="course">Course</option>
            <option value="year">Year</option>
            <option value="section">Section</option>
            <option value="email">Email</option>
            <option value="contact_number">Contact Number</option>
        </select>
a
        <div id="update_input_student" style="display:none;">
            <label for="new_value">New Value:</label>
            <input type="text" name="new_value" id="new_value_student" required>
        </div>

        <button type="submit" name="update_user">Update</button>
    </form>
    <form action="manage_users.php" method="post" >
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
        <input type="hidden" name="role" value="student">
        <button type="submit" name="delete_user" class="">Delete</button>
    </form></td>
            </tr>
            <?php endwhile; ?>
        </table>

    </body>
    <script src="manage_user.js"></script>

    </html>

    <script>

// Function to update the fields dynamically based on the selected role
function updateFieldsBasedOnRole() {
    const role = document.querySelector('select[name="role"]').value;
    const updateFieldSelect = document.getElementById('update_field_select');
    updateFieldSelect.innerHTML = '<option value="" disabled selected>Select Field to Update</option>'; // Reset the options

    // Add options based on the selected role
    if (role === "professor") {
        updateFieldSelect.innerHTML += `
            <option value="name">Name</option>
            <option value="email">Email</option>
            <option value="department">Department</option>
            <option value="phone">Phone</option>
            <option value="address">Address</option>
        `;
    } else if (role === "student") {
        updateFieldSelect.innerHTML += `
            <option value="name">Name</option>
            <option value="email">Email</option>
            <option value="course">Course</option>
            <option value="year">Year</option>
            <option value="section">Section</option>
        `;
    }
}

// Add event listener for role change
document.addEventListener('DOMContentLoaded', function() {
    // Update fields based on the selected role
    document.querySelector('select[name="role"]').addEventListener('change', updateFieldsBasedOnRole);

    // If a user is found and the page reloads, we should trigger the function to populate the update fields
    const role = "<?php echo $user_type; ?>";  // PHP variable passed to JavaScript
    if (role) {
        // Set the role select to the current user's role
        document.querySelector('select[name="role"]').value = role;
        updateFieldsBasedOnRole();
    }
});
</script>
