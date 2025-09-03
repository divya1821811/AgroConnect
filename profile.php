<?php
// Start the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$name = $email = '';
$name_err = $email_err = '';
$success_message = $error_message = '';
$created_at = $last_modified_at = '';

// Fetch current user details
$sql_fetch = "SELECT name, email, created_at, last_modified_at FROM users WHERE id = ?";
if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch)) {
    mysqli_stmt_bind_param($stmt_fetch, 'i', $user_id);
    if (mysqli_stmt_execute($stmt_fetch)) {
        mysqli_stmt_store_result($stmt_fetch);
        if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
            mysqli_stmt_bind_result($stmt_fetch, $name, $email, $created_at, $last_modified_at);
            mysqli_stmt_fetch($stmt_fetch);
        } else {
            // User not found, perhaps delete session and redirect to login
            session_destroy();
            header('location: user_login.php');
            exit();
        }
    } else {
        $error_message = 'Oops! Something went wrong fetching user details.';
    }
    mysqli_stmt_close($stmt_fetch);
}

// Process form submission for updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Validate name
    if (empty(trim($_POST['name']))) {
        $name_err = 'Please enter your name.';
    } else {
        $name = trim($_POST['name']);
    }

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email.';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
    } else {
        $new_email = trim($_POST['email']);
        // Check if email already exists for another user
        $sql_check_email = 'SELECT id FROM users WHERE email = ? AND id != ?';
        if ($stmt_check_email = mysqli_prepare($conn, $sql_check_email)) {
            mysqli_stmt_bind_param($stmt_check_email, 'si', $param_email, $user_id);
            $param_email = $new_email;
            if (mysqli_stmt_execute($stmt_check_email)) {
                mysqli_stmt_store_result($stmt_check_email);
                if (mysqli_stmt_num_rows($stmt_check_email) == 1) {
                    $email_err = 'This email is already taken by another account.';
                } else {
                    $email = $new_email; // Update email if valid and not taken
                }
            } else {
                echo 'Oops! Something went wrong checking email. Please try again later.';
            }
            mysqli_stmt_close($stmt_check_email);
        }
    }

    // If no errors, update database
    if (empty($name_err) && empty($email_err)) {
        $sql_update = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, 'ssi', $param_name, $param_email, $user_id);

            $param_name = $name;
            $param_email = $email;

            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = 'Profile updated successfully!';
                // Update session variable if name changed
                $_SESSION['user_name'] = $name;

                // Re-fetch data to show updated last_modified_at immediately
                $sql_re_fetch = "SELECT created_at, last_modified_at FROM users WHERE id = ?";
                if ($stmt_re_fetch = mysqli_prepare($conn, $sql_re_fetch)) {
                    mysqli_stmt_bind_param($stmt_re_fetch, 'i', $user_id);
                    if (mysqli_stmt_execute($stmt_re_fetch)) {
                        mysqli_stmt_bind_result($stmt_re_fetch, $created_at, $last_modified_at);
                        mysqli_stmt_fetch($stmt_re_fetch);
                    }
                    mysqli_stmt_close($stmt_re_fetch);
                }
            } else {
                $error_message = 'Error updating profile: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = 'Error preparing update statement.';
        }
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AgroConnect</title>
    <!-- Font Awesome for Icons (replace with local SVG or Phosphor if preferred) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Profile Page Specific Styles */
        :root {
            --primary-green: #28a745; /* Consistent with dashboard */
            --secondary-green: #218838;
            --light-green: #e9f5e9;
            --primary-blue: #007bff; /* Consistent with dashboard */
            --secondary-blue: #0056b3;
            --light-blue: #e6f7ff;
            --white: #ffffff;
            --dark: #333333;
            --light-gray: #f0f5f0; /* Body background from dashboard */
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
            --card-radius: 10px; /* Consistent with dashboard */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif; /* Consistent with dashboard */
        }

        body {
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            color: var(--dark); /* Default text color */
        }

        .profile-container {
            background-color: var(--white);
            padding: 35px;
            border-radius: 15px; /* More rounded */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Deeper shadow */
            width: 100%;
            max-width: 550px;
            text-align: center;
            animation: slideInUp 0.8s ease-out; /* Animation on load */
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--primary-green); /* Themed green */
            margin-bottom: 25px;
            font-size: 2.5em; /* Larger heading */
            border-bottom: 2px solid var(--medium-gray); /* Themed gray border */
            padding-bottom: 15px;
        }

        .profile-icon {
            font-size: 4em; /* Large icon */
            color: var(--primary-blue); /* Themed blue */
            margin-bottom: 20px;
            animation: bounceIn 1s ease-out; /* Icon animation */
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark); /* Consistent dark text */
            font-weight: bold;
            font-size: 1.1em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: calc(100% - 24px); /* Adjust for padding and border */
            padding: 12px;
            border: 1px solid var(--medium-gray); /* Themed border */
            border-radius: 8px; /* More rounded inputs */
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--white); /* White input background */
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus {
            border-color: var(--primary-blue); /* Themed blue on focus */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); /* Themed blue shadow */
            outline: none;
        }

        .profile-dates {
            margin-top: 30px;
            font-size: 0.95em;
            color: var(--dark-gray); /* Themed gray */
            text-align: center;
            border-top: 1px dashed var(--medium-gray); /* Themed dashed border */
            padding-top: 20px;
        }

        .profile-dates p {
            margin: 5px 0;
        }

        .profile-dates strong {
            color: var(--dark); /* Stronger dark color */
        }

        .btn-update {
            background-color: var(--primary-green); /* Themed green for primary action */
            color: var(--white);
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%; /* Full width button */
            margin-top: 20px;
            border: none; /* Ensure no default button border */
            cursor: pointer;
        }

        .btn-update:hover {
            background-color: var(--secondary-green); /* Darker green on hover */
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-back-dashboard {
            background-color: var(--dark-gray); /* A neutral gray for back button */
            color: var(--white);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
            margin-top: 15px;
            text-decoration: none; /* Remove underline */
            display: inline-block; /* To allow padding */
        }

        .btn-back-dashboard:hover {
            background-color: #5a6268; /* Slightly darker gray on hover */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Alert messages (using themed colors) */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 15px;
            text-align: left;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background-color: var(--danger);
            color: var(--white);
            border: 1px solid var(--danger);
        }

        .alert-success {
            background-color: var(--success);
            color: var(--white);
            border: 1px solid var(--success);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                padding: 25px;
                margin: 15px;
            }
            h2 {
                font-size: 2em;
            }
            .profile-icon {
                font-size: 3.5em;
            }
            .form-group input {
                padding: 10px;
            }
            .btn-update, .btn-back-dashboard {
                font-size: 1em;
                padding: 10px 25px;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 20px;
                margin: 10px;
            }
            h2 {
                font-size: 1.8em;
            }
            .profile-icon {
                font-size: 3em;
            }
            .form-group label {
                font-size: 0.95em;
            }
            .form-group input {
                padding: 8px;
            }
            .profile-dates {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container profile-container">
        <h2>My Profile</h2>

        <div class="profile-icon">
            <i class="fas fa-user-circle"></i> <!-- User icon -->
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $name_err; ?></span>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $email_err; ?></span>
            </div>
            
            <div class="form-group">
                <button type="submit" name="update_profile" class="btn btn-update">Update Profile</button>
            </div>
        </form>

        <div class="profile-dates">
            <p><strong>Account Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($created_at)); ?></p>
            <p><strong>Last Modified:</strong> <?php echo date('F j, Y, g:i a', strtotime($last_modified_at)); ?></p>
        </div>

        <p>
            <a href="user_dashboard.php" class="btn btn-back-dashboard">Back to Dashboard</a>
        </p>
    </div>
</body>
</html>
