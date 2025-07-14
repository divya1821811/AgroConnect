<?php
// Start the session
session_start();

// Check if admin is already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] !== '') {
    header('location: admin_dashboard.php');
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables
$username = $password = '';
$username_err = $password_err = $login_err = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter username.';
    } else {
        $username = trim($_POST['username']);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        $sql = 'SELECT id, username, password FROM admins WHERE username = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            // session_start(); // Already started at top

                            // Store data in session variables
                            $_SESSION['admin_id'] = $id;
                            $_SESSION['admin_username'] = $username;
                            $_SESSION['user_type'] = 'admin'; // Identify type of logged-in user

                            // Redirect to admin dashboard
                            header('location: admin_dashboard.php');
                            exit();
                        } else {
                            $login_err = 'Invalid username or password.';
                        }
                    }
                } else {
                    $login_err = 'Invalid username or password.';
                }
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for admin login */
        body {
            background-color: #e9ecef; /* Slightly different background for admin */
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            max-width: 450px;
        }
        .container h2 {
            color: #495057; /* Darker text for admin */
            margin-bottom: 30px;
            font-size: 32px;
        }
        .btn {
            background-color: #007bff; /* Blue for admin buttons */
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .link-text a {
            color: #28a745; /* Green for links in admin context */
        }
        .link-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>AgroConnect Admin Login</h2>
        <?php if (!empty($login_err)): ?>
            <div class="alert alert-danger"><?php echo $login_err; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="alert-danger"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p class="link-text">Go back to <a href="index.php">Main Site</a></p>
        </form>
    </div>
</body>
</html>