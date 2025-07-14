<?php
// Include database connection file
require_once 'config.php';

// Initialize variables
$name = $email = $password = $confirm_password = '';
$name_err = $email_err = $password_err = $confirm_password_err = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $email = trim($_POST['email']);
        // Check if email already exists
        $sql = 'SELECT id FROM users WHERE email = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_email);
            $param_email = $email;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = 'This email is already registered.';
                }
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = 'Password must have at least 6 characters.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm your password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = 'Passwords do not match.';
        }
    }

    // If no errors, insert into database
    if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = 'INSERT INTO users (name, email, password) VALUES (?, ?, ?)';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sss', $param_name, $param_email, $param_password);

            $param_name = $name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

            if (mysqli_stmt_execute($stmt)) {
                $success_message = 'Registration successful! You can now log in.';
                // Optionally redirect to login page after successful registration
                // header('location: user_login.php');
                // exit();
            } else {
                echo 'Something went wrong. Please try again later.';
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
    <title>Farmers Registration - AgroConnect</title>
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

        .alert-success {
            color: var(--primary-green);
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-green);
            font-weight: 500;
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
       

        /* Strength indicator */
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background-color: var(--light-brown);
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            background-color: #e74c3c;
            transition: var(--transition);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-leaf"></i>Agro<span>Connect</span></span><br>Farmers Registration</h2>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                <span class="alert-danger"><?php echo $name_err; ?></span>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="alert-danger"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $password_err; ?></span>
               
                <div class="password-strength">
                    <div class="strength-meter" id="strengthMeter"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $confirm_password_err; ?></span>
               
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Register">
            </div>
            <p class="link-text">Already have an account? <a href="user_login.php">Login here</a>.</p>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm_password');
        const strengthMeter = document.querySelector('#strengthMeter');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Password strength indicator
        password.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updateStrengthMeter(strength);
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character type checks
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            return Math.min(strength, 5); // Max strength of 5
        }

        function updateStrengthMeter(strength) {
            const colors = ['#e74c3c', '#f39c12', '#f1c40f', '#2ecc71', '#27ae60'];
            const width = (strength / 5) * 100;
            
            strengthMeter.style.width = `${width}%`;
            strengthMeter.style.backgroundColor = colors[strength - 1] || colors[0];
        }
    </script>
</body>
</html>