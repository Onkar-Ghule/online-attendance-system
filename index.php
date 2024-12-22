<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Online Attendance System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="login-page" class="container">
        <h2>Login</h2>
        <form id="login-form" action="login.php" method="post">
        <label for="ID">ID:</label>
        <input type="text" id="username" name="id" required>
            <label for="username">Username:</label>
            <input type="text" id="id" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="student">Student</option>
                <option value="professor">Professor</option>
                <option value="admin">admin</option>
            </select>
            <button type="submit">Login</button>
        </form>
        <p  class="error-message">
            <?php
            if (isset($_GET['error']) && $_GET['error'] == 1) {
                echo "Invalid id, username, password, or role. Please try again.";
            }
            ?>
        </p>
    </div>
</body>
</html>
