<?php
// Start the session
session_start();

// Check if the user is already logged in, if yes then redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '') {
    header('location: user_dashboard.php'); // Create user_dashboard.php later
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables
$email = $password = '';
$email_err = $password_err = $login_err = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email.';
    } else {
        $email = trim($_POST['email']);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        $sql = 'SELECT id, name, email, password FROM users WHERE email = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                // Check if email exists, then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start(); // Already started above, but good practice if not always at top

                            // Store data in session variables
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_type'] = 'user'; // Identify type of logged-in user

                            // Redirect user to dashboard page
                            header('location: user_dashboard.php');
                            exit();
                        } else {
                            // Password is not valid
                            $login_err = 'Invalid email or password.';
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = 'Invalid email or password.';
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
    <title>User Login - AgroConnect</title>
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
            background-image: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .container {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 450px;
            transition: var(--transition);
            border: 1px solid var(--light-brown);
        }

        .container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
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
            border-radius: 5px;
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

        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-green);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            background-color: var(--secondary-green);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
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
                padding: 10px;
            }
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 60px;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-brown);
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary-brown);
        }
    </style>
</head>

<body>
    <div class="container">
        <h2> <span class="logo-text"><i class="fas fa-leaf"></i>Agro<span>Connect</span></span><br>Farmers Login</h2>
        <?php if (!empty($login_err)): ?>
            <div class="alert alert-danger"><?php echo $login_err; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="alert-danger"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $password_err; ?></span>
               
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p class="link-text">Don't have an account? <a href="user_register.php">Register now</a>.</p>
            <p class="link-text"><a href="forgot_password.php">Forgot your password?</a></p>


        </form>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>