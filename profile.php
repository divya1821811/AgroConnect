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
$name = $email = $phone = $address = '';
$name_err = $email_err = '';
$success_message = $error_message = '';
$created_at = $last_modified_at = '';

// Fetch current user details including phone and address
$sql_fetch = "SELECT name, email, phone, address, created_at, last_modified_at FROM users WHERE id = ?";
if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch)) {
    mysqli_stmt_bind_param($stmt_fetch, 'i', $user_id);
    if (mysqli_stmt_execute($stmt_fetch)) {
        mysqli_stmt_store_result($stmt_fetch);
        if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
            mysqli_stmt_bind_result($stmt_fetch, $name, $email, $phone, $address, $created_at, $last_modified_at);
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

    // Validate email (read-only, but still validate if somehow changed)
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
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modern CSS Variables */
        :root {
            --primary-green: #10b981;
            --primary-dark: #1f2937;
            --primary-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --text-dark: #374151;
            --text-light: #6b7280;
            --border-light: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-secondary: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --gradient-accent: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
           
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Profile Container */
        .profile-container {
            background: var(--white);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transition: var(--transition);
        }

        .profile-container:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-primary);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header Styles */
        h2 {
            color: var(--primary-dark);
            text-align: center;
            margin-bottom: 32px;
            font-size: 2.25rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 16px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        /* Profile Icon */
        .profile-icon {
            font-size: 5rem;
            color: var(--primary-blue);
            margin-bottom: 24px;
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
            display: inline-block;
            width: 100%;
        }

        .profile-icon:hover {
            transform: scale(1.1) rotate(5deg);
            color: var(--accent-purple);
        }

        .profile-icon::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: var(--transition);
        }

        .profile-icon:hover::after {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.2);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
            position: relative;
            transition: var(--transition);
        }

        .form-group:hover {
            transform: translateX(8px);
        }

        .form-group label {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            margin-right: 12px;
            color: var(--primary-green);
            font-size: 1.1rem;
            width: 20px;
            transition: var(--transition);
        }

        .form-group:hover label i {
            transform: scale(1.2);
            color: var(--primary-blue);
        }

        .input-wrapper {
            position: relative;
            transition: var(--transition);
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: var(--transition);
            z-index: 2;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--light-bg);
            transition: var(--transition);
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:hover:not(:focus) {
            border-color: #cbd5e1;
            background: var(--white);
        }

        .form-group input[readonly] {
            background: #f8fafc;
            color: var(--text-light);
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        .form-group input[readonly]:hover {
            border-color: #e2e8f0;
            transform: none;
        }

        /* Profile Dates Section */
        .profile-dates {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px dashed var(--border-light);
            position: relative;
        }

        .profile-dates::before {
            content: '\f133';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--white);
            padding: 0 20px;
            color: var(--primary-green);
            font-size: 1.25rem;
        }

        .profile-dates p {
            margin: 12px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .profile-dates p:hover {
            transform: translateX(8px);
            color: var(--text-dark);
        }

        .profile-dates p i {
            margin-right: 12px;
            color: var(--primary-green);
            font-size: 1rem;
        }

        .profile-dates strong {
            color: var(--text-dark);
            margin-right: 8px;
        }

        /* Button Styles */
        .btn-update {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
            width: 100%;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-update::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-update:hover::before {
            left: 100%;
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn-update:active {
            transform: translateY(-1px);
        }

        .btn-back-dashboard {
            background: var(--primary-dark);
            color: var(--white);
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow);
            width: 100%;
            margin-top: 16px;
            display: inline-block;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .btn-back-dashboard::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: 0.5s;
        }

        .btn-back-dashboard:hover::before {
            width: 100%;
        }

        .btn-back-dashboard:hover {
            background: #374151;
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease-out;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid;
        }

        .alert:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left-color: #ef4444;
        }

        /* Error States */
        .is-invalid {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .profile-container {
                padding: 32px 24px;
                margin: 0;
            }

            h2 {
                font-size: 2rem;
                margin-bottom: 24px;
            }

            .profile-icon {
                font-size: 4rem;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group input {
                padding: 12px 14px 12px 44px;
            }

            .btn-update,
            .btn-back-dashboard {
                padding: 14px 24px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 24px 20px;
                border-radius: 20px;
            }

            h2 {
                font-size: 1.75rem;
            }

            .profile-icon {
                font-size: 3.5rem;
            }

            .form-group label {
                font-size: 0.9rem;
            }

            .form-group input {
                padding: 12px 12px 12px 40px;
                font-size: 0.95rem;
            }

            .profile-dates {
                font-size: 0.85rem;
            }

            .profile-dates p {
                flex-direction: column;
                text-align: center;
                gap: 4px;
            }

            .profile-dates p i {
                margin-right: 0;
                margin-bottom: 4px;
            }
        }

        /* Reduced motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus states for accessibility */
        .form-group input:focus-visible,
        .btn-update:focus-visible,
        .btn-back-dashboard:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="container profile-container">
        <h2>My Profile</h2>

        <div class="profile-icon" id="profileIcon">
            <i class="fas fa-user-circle"></i>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="profileForm">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i>Name:</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                </div>
                <span class="alert-danger"><?php echo $name_err; ?></span>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i>Email:</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" readonly>
                </div>
                <span class="alert-danger"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i>Phone Number:</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-control" readonly>
                </div>
            </div>
            <div class="form-group">
                <label for="address"><i class="fas fa-home"></i>Address:</label>
                <div class="input-wrapper">
                    <i class="fas fa-home"></i>
                    <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($address); ?>" class="form-control" readonly>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="update_profile" class="btn btn-update" id="updateBtn">
                    <i class="fas fa-sync-alt"></i> Update Profile
                </button>
            </div>
        </form>

        <div class="profile-dates">
            <p><i class="fas fa-calendar-plus"></i><strong>Account Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($created_at)); ?></p>
            <p><i class="fas fa-calendar-check"></i><strong>Last Modified:</strong> <?php echo date('F j, Y, g:i a', strtotime($last_modified_at)); ?></p>
        </div>

        <p>
            <a href="user_dashboard.php" class="btn btn-back-dashboard">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.getElementById('profileForm');
            const loading = document.getElementById('loading');
            const updateBtn = document.getElementById('updateBtn');
            const profileIcon = document.getElementById('profileIcon');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            // Profile icon interaction
            if (profileIcon) {
                profileIcon.addEventListener('click', function() {
                    this.style.transform = 'scale(1.2) rotate(10deg)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 300);
                });
            }
            
            // Form submission handling
            if (profileForm) {
                profileForm.addEventListener('submit', function() {
                    loading.style.display = 'flex';
                    updateBtn.disabled = true;
                    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                });
            }
            
            // Alert dismissal
            if (successAlert) {
                successAlert.addEventListener('click', function() {
                    this.style.opacity = '0';
                    setTimeout(() => {
                        this.style.display = 'none';
                    }, 300);
                });
            }
            
            if (errorAlert) {
                errorAlert.addEventListener('click', function() {
                    this.style.opacity = '0';
                    setTimeout(() => {
                        this.style.display = 'none';
                    }, 300);
                });
            }
            
            // Enhanced input interactions
            const inputs = document.querySelectorAll('input:not([readonly])');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.style.transform = 'translateX(8px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.parentElement.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>