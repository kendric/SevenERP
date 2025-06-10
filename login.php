<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "includes/db.php";

if (isLoggedIn()) {
    redirect("index.php");
}

$username = $password_input = ""; // Renamed $password to $password_input to avoid confusion
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password_input = trim($_POST["password"]); // Use $password_input for form data
    }

    if (empty($username_err) && empty($password_err)) {
        // IMPORTANT: The column is still named 'password_hash' in the DB,
        // but we are now expecting it to hold the PLAIN TEXT password.
        $sql = "SELECT id, username, password_hash, is_admin FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // $db_password will now hold the plain text password from the 'password_hash' column
                    mysqli_stmt_bind_result($stmt, $id, $db_username, $db_password, $is_admin); 
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // Directly compare the input password with the plain text password from the DB
                        if ($password_input === $db_password) { // Simple string comparison
                            // session_start(); // Already started in db.php
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $db_username; 
                            $_SESSION["is_admin"] = $is_admin;
                            redirect("index.php");
                        } else {
                            $login_err = "Invalid username or password. (Password mismatch)";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password. (No user found)";
                }
            } else {
                $login_err = "SQL execution error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_err = "SQL prepare error: " . mysqli_error($conn);
        }
    }
    // mysqli_close($conn); // $conn might be needed if the page reloads with an error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SevenSoft ERP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .wrapper { width: 380px; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .wrapper h2 { text-align: center; margin-bottom: 20px; color: #007bff; }
        .form-group label {font-weight: bold;}
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .alert-danger {font-size: 0.9em;}
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>SevenSoft ERP</h2>
        <p>Please fill in your credentials to login.</p>
        <?php 
        if(!empty($login_err)){ 
            echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>'; 
        } 
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>User Name</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" placeholder="admin">
                <span class="invalid-feedback"><?php echo htmlspecialchars($username_err); ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="••••••••">
                <span class="invalid-feedback"><?php echo htmlspecialchars($password_err); ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="Login Here">
            </div>
        </form>
    </div>    
</body>
</html>