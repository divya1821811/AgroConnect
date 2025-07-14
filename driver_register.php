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
        $allowed_vehicle_types = ['Tractor', 'JCB', 'Lorry'];
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
                // Optionally redirect to login page after successful registration
                // header('location: driver_login.php');
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
    <title>Driver Registration - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
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
                    <option value="Tractor" <?php echo ($vehicle_type == 'Tractor') ? 'selected' : ''; ?>>Tractor</option>
                    <option value="JCB" <?php echo ($vehicle_type == 'JCB') ? 'selected' : ''; ?>>JCB</option>
                    <option value="Lorry" <?php echo ($vehicle_type == 'Lorry') ? 'selected' : ''; ?>>Lorry</option>
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