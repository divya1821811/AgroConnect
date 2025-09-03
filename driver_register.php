<?php
// Include database connection file
require_once 'config.php';

// Initialize variables
$name = $age = $phone_number = $vehicle_type = $location = $district = $charge = $password = $confirm_password = '';
$name_err = $age_err = $phone_number_err = $vehicle_type_err = $location_err = $district_err = $charge_err = $password_err = $confirm_password_err = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate name
    if (empty(trim($_POST['name']))) {
        $name_err = 'Please enter driver\'s name.';
    } else {
        $name = trim($_POST['name']);
    }

    // Validate age
    if (empty(trim($_POST['age']))) {
        $age_err = 'Please enter driver\'s age.';
    } elseif (!is_numeric(trim($_POST['age'])) || trim($_POST['age']) < 18) {
        $age_err = 'Age must be a number and at least 18.';
    } else {
        $age = trim($_POST['age']);
    }

    // Validate phone number
    if (empty(trim($_POST['phone_number']))) {
        $phone_number_err = 'Please enter phone number.';
    } elseif (!preg_match('/^[0-9]{10}$/', trim($_POST['phone_number']))) { // Assuming 10 digit Indian number
        $phone_number_err = 'Please enter a valid 10-digit phone number.';
    } else {
        $phone_number = trim($_POST['phone_number']);
        // Check if phone number already exists
        $sql = 'SELECT id FROM drivers WHERE phone_number = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_phone_number);
            $param_phone_number = $phone_number;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $phone_number_err = 'This phone number is already registered.';
                }
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate vehicle type
    if (empty(trim($_POST['vehicle_type']))) {
        $vehicle_type_err = 'Please select a vehicle type.';
    } else {
        // Corrected list of allowed vehicle types to match the HTML values with underscores
        $allowed_vehicle_types = ['Tractor', 'JCB', 'Lorry', 'Power_Tiller', 'Mini_Truck', 'Loader', 'Sprayer_Vehicle', 'Water_Tanker'];
        if (!in_array(trim($_POST['vehicle_type']), $allowed_vehicle_types)) {
            $vehicle_type_err = 'Invalid vehicle type selected.';
        } else {
            $vehicle_type = trim($_POST['vehicle_type']);
        }
    }

    // Validate location
    if (empty(trim($_POST['location']))) {
        $location_err = 'Please enter your location (village/town).';
    } else {
        $location = trim($_POST['location']);
    }

    // Validate district
    if (empty(trim($_POST['district']))) {
        $district_err = 'Please enter your district.';
    } else {
        $district = trim($_POST['district']);
    }

    // Validate service charge
    if (empty(trim($_POST['charge']))) {
        $charge_err = 'Please enter service charge.';
    } elseif (!is_numeric(trim($_POST['charge'])) || trim($_POST['charge']) <= 0) {
        $charge_err = 'Service charge must be a positive number.';
    } else {
        $charge = trim($_POST['charge']);
    }

    // Validate password (Drivers will also have a password for login)
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
    if (empty($name_err) && empty($age_err) && empty($phone_number_err) && empty($vehicle_type_err) && empty($location_err) && empty($district_err) && empty($charge_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = 'INSERT INTO drivers (name, age, phone_number, vehicle_type, location, district, charge, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sissssss', $param_name, $param_age, $param_phone_number, $param_vehicle_type, $param_location, $param_district, $param_charge, $param_password);

            $param_name = $name;
            $param_age = $age;
            $param_phone_number = $phone_number;
            $param_vehicle_type = $vehicle_type;
            $param_location = $location;
            $param_district = $district;
            $param_charge = $charge;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

            if (mysqli_stmt_execute($stmt)) {
                $success_message = 'Driver registration successful! You can now log in.';
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
    <title>Driver Registration - AgroConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
/* style.css */
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
    max-width: 500px;
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

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%238d6e63'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 15px;
    padding-right: 40px;
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
    </style>
</head>
<body>
    <div class="container">
           <div class="driver-icon">
             <i class="fas fa-truck"></i>
         </div>
        <h2>Driver Registration</h2>
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
                <label for="age">Age</label>
                <input type="text" name="age" id="age" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $age; ?>">
                <span class="alert-danger"><?php echo $age_err; ?></span>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number (10 digits)</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone_number; ?>">
                <span class="alert-danger"><?php echo $phone_number_err; ?></span>
            </div>
            <div class="form-group">
                <label for="vehicle_type">Vehicle Type</label>
                <select name="vehicle_type" id="vehicle_type" class="form-control <?php echo (!empty($vehicle_type_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Vehicle</option>
                    <!-- Vehicle values now match the PHP validation array with underscores -->
                    <option value="Tractor" <?php echo ($vehicle_type == 'Tractor') ? 'selected' : ''; ?>>Tractor</option>
                    <option value="JCB" <?php echo ($vehicle_type == 'JCB') ? 'selected' : ''; ?>>JCB</option>
                    <option value="Lorry" <?php echo ($vehicle_type == 'Lorry') ? 'selected' : ''; ?>>Lorry</option>
                    <option value="Power_Tiller" <?php echo ($vehicle_type == 'Power_Tiller') ? 'selected' : ''; ?>>Power Tiller</option>
                    <option value="Mini_Truck" <?php echo ($vehicle_type == 'Mini_Truck') ? 'selected' : ''; ?>>Mini Truck</option>
                    <option value="Loader" <?php echo ($vehicle_type == 'Loader') ? 'selected' : ''; ?>>Loader</option>
                    <option value="Sprayer_Vehicle" <?php echo ($vehicle_type == 'Sprayer_Vehicle') ? 'selected' : ''; ?>>Sprayer Vehicle</option>
                    <option value="Water_Tanker" <?php echo ($vehicle_type == 'Water_Tanker') ? 'selected' : ''; ?>>Water Tanker</option>
                </select>
                <span class="alert-danger"><?php echo $vehicle_type_err; ?></span>
            </div>
            <div class="form-group">
                <label for="location">Location (Village/Town)</label>
                <input type="text" name="location" id="location" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $location; ?>">
                <span class="alert-danger"><?php echo $location_err; ?></span>
            </div>
            <div class="form-group">
                <label for="district">District</label>
                <input type="text" name="district" id="district" class="form-control <?php echo (!empty($district_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $district; ?>">
                <span class="alert-danger"><?php echo $district_err; ?></span>
            </div>
            <div class="form-group">
                <label for="charge">Service Charge (e.g., per hour/day)</label>
                <input type="text" name="charge" id="charge" class="form-control <?php echo (!empty($charge_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $charge; ?>">
                <span class="alert-danger"><?php echo $charge_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Register">
            </div>
            <p class="link-text">Already have an account? <a href="driver_login.php">Login here</a>.</p>
        </form>
    </div>
</body>
</html>
