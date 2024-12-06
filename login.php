<?php
session_start();
$host        = "host=127.0.0.1";
$port        = "port=5432";
$dbname      = "dbname=postgres";
$credentials = "user=postgres password=pass123";

// Establish the database connection
$db = pg_connect("$host $port $dbname $credentials");
if (!$db) {
    echo "Error: Unable to open database\n";
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = pg_escape_string($db, $_POST['id']);
    $username = pg_escape_string($db, $_POST['username']);
    $password = pg_escape_string($db, $_POST['password']);
    $role = $_POST['role'];
    $_SESSION['username'] = $username;
    $_SESSION['user_id'] = $id;
    // Query the database with decryption for password comparison
    if($role=='admin')
    {
    $query = "SELECT * FROM $role WHERE name = '$username' AND pgp_sym_decrypt(password::bytea, 'PWD') = '$password' AND id = '$id'";
    $result = pg_query($db, $query);
    if (pg_num_rows($result) > 0) {
       
                header("Location: admin_dashboard.php");
                
    } else {
        // Invalid credentials
        header("Location: index.php?error=1");
        exit();
    }
    }
    else
    {
        $query = "SELECT * FROM $role WHERE name = '$username' AND id = '$id'";
        $result = pg_query($db, $query);
    
        if ($row = pg_fetch_assoc($result)) {
            // Verify password using password_verify
            if (password_verify($password, $row['password'])) {
                $_SESSION['username'] = $username;
                switch ($role) {
                    case "professor":
                        header("Location: professor_dashboard.php");
                        exit();
                    case "student":
                        header("Location: student_dashboard.php");
                        exit();
                }
            }
        }
        else
        {
        // Invalid credentials
        header("Location: index.php?error=1");
        exit();
    }
}
   
}
?>
