<?php
// Start the session
session_start();

// Check if the driver is already logged in, if yes then redirect to driver dashboard
if (isset($_SESSION['driver_id']) && $_SESSION['driver_id'] !== '') {
    header('location: driver_dashboard.php');
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables
$login_input = $password = '';
$login_input_err = $password_err = $login_err = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate login input (phone number or email)
    if (empty(trim($_POST['login_input']))) {
        $login_input_err = 'Please enter your phone number or email.';
    } else {
        $login_input = trim($_POST['login_input']);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate credentials
    if (empty($login_input_err) && empty($password_err)) {
        // Check if input is email or phone number
        $is_email = filter_var($login_input, FILTER_VALIDATE_EMAIL);
        
        if ($is_email) {
            // Login with email
            $sql = 'SELECT id, name, phone_number, password FROM drivers WHERE email = ?';
        } else {
            // Login with phone number
            $sql = 'SELECT id, name, phone_number, password FROM drivers WHERE phone_number = ?';
        }
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_login_input);
            $param_login_input = $login_input;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                // Check if account exists, then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $phone_number, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Store data in session variables
                            $_SESSION['driver_id'] = $id;
                            $_SESSION['driver_name'] = $name;
                            $_SESSION['driver_phone_number'] = $phone_number;
                            $_SESSION['user_type'] = 'driver';

                            // Redirect driver to dashboard page
                            header('location: driver_dashboard.php');
                            exit();
                        } else {
                            // Password is not valid
                            $login_err = 'Invalid login credentials.';
                        }
                    }
                } else {
                    // Account doesn't exist
                    $login_err = 'Invalid login credentials.';
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
    <title>Driver Login - AgroConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-green: #4a6f28;
            --secondary-green: #6b8c42;
            --light-green: #e8f5e9;
            --primary-brown: #8d6e63;
            --secondary-brown: #a1887f;
            --light-brown: #d7ccc8;
            --white: #ffffff;
            --dark: #333333;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-green);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-image: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                            url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 30px;
            width: 100%;
            max-width: 450px;
            transition: var(--transition);
            border: 1px solid var(--light-brown);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        h2 {
            color: var(--primary-green);
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-brown);
            border-radius: 3px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-brown);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 2px solid var(--light-brown);
            border-radius: 8px;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(74, 111, 40, 0.2);
        }

        .form-control:hover {
            border-color: var(--secondary-brown);
        }

        .is-invalid {
            border-color: #e74c3c;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }

        .alert-danger {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .alert-success {
            color: var(--primary-green);
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-green);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            background-color: var(--secondary-green);
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
        }

        .btn:hover::after {
            left: 100%;
        }

        .link-text {
            text-align: center;
            margin-top: 20px;
            color: var(--dark);
        }

        .link-text a {
            color: var(--primary-brown);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .link-text a:hover {
            color: var(--primary-green);
        }

        .link-text a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-green);
            transition: var(--transition);
        }

        .link-text a:hover::after {
            width: 100%;
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-brown);
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary-brown);
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 24px;
            }

            .form-control {
                padding: 10px 12px;
            }

            .btn {
                padding: 12px;
            }
        }

        /* Driver specific styling */
        .driver-icon {
            text-align: center;
            margin-bottom: 20px;
        }

        .driver-icon i {
            font-size: 50px;
            color: var(--primary-green);
            background-color: var(--light-brown);
            padding: 15px;
            border-radius: 50%;
            border: 3px solid var(--primary-brown);
        }
        .login-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-brown);
        }
        
        .login-tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }
        
        .login-tab.active {
            border-bottom: 3px solid var(--primary-green);
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .login-tab:hover {
            background-color: var(--light-green);
        }
        
        .login-form {
            display: none;
        }
        
        .login-form.active {
            display: block;
        }
        
        .login-type-note {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: var(--secondary-brown);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="driver-icon">
            <i class="fas fa-truck"></i>
        </div>
        <h2>Driver Login</h2>
        <?php if (!empty($login_err)): ?>
            <div class="alert alert-danger"><?php echo $login_err; ?></div>
        <?php endif; ?>
        
        <div class="login-tabs">
            <div class="login-tab active" id="phone-tab">Phone Login</div>
            <div class="login-tab" id="email-tab">Email Login</div>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="login-form">
            <div class="form-group">
                <label for="login_input" id="login-label">Phone Number</label>
                <input type="text" name="login_input" id="login_input" class="form-control <?php echo (!empty($login_input_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $login_input; ?>" placeholder="Enter your phone number">
                <span class="alert-danger"><?php echo $login_input_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password">
                <span class="alert-danger"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
        </form>
        
        <p class="login-type-note" id="login-note">Login using your registered phone number</p>
        
        <p class="link-text">Don't have an account? <a href="driver_register.php">Register now</a>.</p>
        <p class="link-text">Forgot your password? <a href="d_forgot_password.php">Reset here</a>.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const phoneTab = document.getElementById('phone-tab');
            const emailTab = document.getElementById('email-tab');
            const loginInput = document.getElementById('login_input');
            const loginLabel = document.getElementById('login-label');
            const loginNote = document.getElementById('login-note');
            
            phoneTab.addEventListener('click', function() {
                phoneTab.classList.add('active');
                emailTab.classList.remove('active');
                loginLabel.textContent = 'Phone Number';
                loginInput.placeholder = 'Enter your phone number';
                loginNote.textContent = 'Login using your registered phone number';
            });
            
            emailTab.addEventListener('click', function() {
                emailTab.classList.add('active');
                phoneTab.classList.remove('active');
                loginLabel.textContent = 'Email Address';
                loginInput.placeholder = 'Enter your email address';
                loginNote.textContent = 'Login using your registered email address';
            });
        });
    </script>
</body>
</html>