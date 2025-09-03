<?php
// Start the session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // --- DEBUGGING: Check what the user entered ---
    echo "<p style='color: #007bff; font-weight: bold;'>DEBUG: Processing login request...</p>";
    echo "<p>DEBUG: Username submitted: " . htmlspecialchars($username) . "</p>";
    echo "<p>DEBUG: Password submitted: " . htmlspecialchars($password) . "</p>";

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        
        // --- DEBUGGING: Confirming database connection ---
        if ($conn) {
            echo "<p style='color: green;'>DEBUG: Database connection is active.</p>";
        } else {
            echo "<p style='color: red;'>DEBUG: Database connection failed.</p>";
        }
        
        $sql = 'SELECT id, username, password FROM admins WHERE username = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_username);
            $param_username = $username;
            
            // --- DEBUGGING: Confirming query execution ---
            echo "<p style='color: #007bff;'>DEBUG: Executing prepared statement...</p>";

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                // --- DEBUGGING: Checking for a matching user ---
                echo "<p style='color: #007bff;'>DEBUG: Number of rows found: " . mysqli_stmt_num_rows($stmt) . "</p>";

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        
                        // --- DEBUGGING: Verifying the password ---
                        echo "<p style='color: #007bff;'>DEBUG: Verifying password with password_verify().</p>";
                        
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            
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
    
    <style>
        /* Specific styles for admin login */
        body {
            background-color: #e9ecef; /* Slightly different background for admin */
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            max-width: 450px;
            width: 90%;
            text-align: center;
        }
        .container h2 {
            color: #495057; /* Darker text for admin */
            margin-bottom: 30px;
            font-size: 32px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box; /* Ensures padding doesn't affect total width */
        }
        .btn {
            background-color: #007bff; /* Blue for admin buttons */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .link-text {
            margin-top: 20px;
            font-size: 14px;
        }
        .link-text a {
            color: #28a745; /* Green for links in admin context */
            text-decoration: none;
        }
        .link-text a:hover {
            text-decoration: underline;
        }
        
        /* FIX FOR INVISIBLE ALERTS */
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-top: 5px;
            display: block; /* Make sure it takes up space */
        }
        /* Specific error styling for input fields */
        .form-control.is-invalid {
            border-color: #dc3545;
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
                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
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
