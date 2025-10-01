<?php 
// Include database connection file
require_once 'config.php';

// Create upload directories if they don't exist
$photo_dir = 'uploads/photos/';
$license_dir = 'uploads/licenses/';

if (!file_exists($photo_dir)) {
    mkdir($photo_dir, 0777, true);
}
if (!file_exists($license_dir)) {
    mkdir($license_dir, 0777, true);
}

// Initialize variables
$name = $age = $phone_number = $vehicle_type = $location = $district = $charge = $email = '';
$driver_photo = $license = '';
$name_err = $age_err = $phone_number_err = $vehicle_type_err = $location_err = $district_err = $charge_err = $email_err = '';
$driver_photo_err = $license_err = '';
$password = $confirm_password = '';
$password_err = $confirm_password_err = '';
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
    } elseif (!preg_match('/^[0-9]{10}$/', trim($_POST['phone_number']))) {
        $phone_number_err = 'Please enter a valid 10-digit phone number.';
    } else {
        $phone_number = trim($_POST['phone_number']);
        $sql = 'SELECT id FROM drivers WHERE phone_number = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_phone_number);
            $param_phone_number = $phone_number;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $phone_number_err = 'This phone number is already registered.';
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter an email.';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
    } else {
        $email = trim($_POST['email']);
        
        // Check if email already exists
        $sql = 'SELECT id FROM drivers WHERE email = ?';
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $param_email);
            $param_email = $email;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = 'This email is already registered.';
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate driver photo (image only)
    if (!empty($_FILES['driver_photo']['name'])) {
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($_FILES['driver_photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_extensions)) {
            $driver_photo_err = 'Only JPG, JPEG, PNG files are allowed for driver photo.';
        } elseif ($_FILES['driver_photo']['size'] > 5000000) { // 5MB limit
            $driver_photo_err = 'Photo size must be less than 5MB.';
        } else {
            $driver_photo_filename = uniqid() . '.' . $file_ext;
            $driver_photo_path = $photo_dir . $driver_photo_filename;
            
            if (move_uploaded_file($_FILES['driver_photo']['tmp_name'], $driver_photo_path)) {
                $driver_photo = $driver_photo_path;
            } else {
                $driver_photo_err = 'Failed to upload driver photo. Please try again.';
            }
        }
    } else {
        $driver_photo_err = 'Please upload driver photo.';
    }

    // Validate license (PDF only)
    if (!empty($_FILES['license']['name'])) {
        $file_ext = strtolower(pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'pdf') {
            $license_err = 'Only PDF files are allowed for license.';
        } elseif ($_FILES['license']['size'] > 10000000) { // 10MB limit
            $license_err = 'License file size must be less than 10MB.';
        } else {
            $license_filename = uniqid() . '.' . $file_ext;
            $license_path = $license_dir . $license_filename;
            
            if (move_uploaded_file($_FILES['license']['tmp_name'], $license_path)) {
                $license = $license_path;
            } else {
                $license_err = 'Failed to upload license file. Please try again.';
            }
        }
    } else {
        $license_err = 'Please upload your driving license (PDF).';
    }

    // Validate vehicle type
    if (empty(trim($_POST['vehicle_type']))) {
        $vehicle_type_err = 'Please select a vehicle type.';
    } else {
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
if (empty($name_err) && empty($age_err) && empty($phone_number_err) && empty($email_err) && empty($driver_photo_err) && empty($license_err) &&
    empty($vehicle_type_err) && empty($location_err) && empty($district_err) && empty($charge_err) && empty($password_err) && empty($confirm_password_err)) {
    
    $sql = 'INSERT INTO drivers (`name`, `age`, `phone_number`, `email`, `driver_photo`, `licence`, `vehicle_type`, `location`, `district`, `charge`, `password`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Fix: use all strings to avoid datatype mismatch
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssss',
            $param_name, $param_age, $param_phone_number, $param_email,
            $param_driver_photo, $param_license, $param_vehicle_type,
            $param_location, $param_district, $param_charge, $param_password
        );

        $param_name = $name;
        $param_age = $age;
        $param_phone_number = $phone_number;
        $param_email = $email;
        $param_driver_photo = $driver_photo;
        $param_license = $license; // still using PHP variable, DB column is "licence"
        $param_vehicle_type = $vehicle_type;
        $param_location = $location;
        $param_district = $district;
        $param_charge = $charge;
        $param_password = password_hash($password, PASSWORD_DEFAULT);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = '✅ Driver registration successful! You can now log in.';
            // Clear form fields after successful registration
            $name = $age = $phone_number = $vehicle_type = $location = $district = $charge = $email = '';
        } else {
            $success_message = '<span style="color:red;">❌ Registration failed. Please try again later.<br>Error: ' . htmlspecialchars(mysqli_error($conn)) . '</span>';
            // Clean up uploaded files if database insertion fails
            if (file_exists($driver_photo)) unlink($driver_photo);
            if (file_exists($license)) unlink($license);
        }
        mysqli_stmt_close($stmt);
    } else {
        $success_message = '<span style="color:red;">❌ Database statement preparation failed: ' . htmlspecialchars(mysqli_error($conn)) . '</span>';
    }
}


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
    max-width: 900px; /* Increased width for two columns */
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

/* Two column form layout */
.form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.form-column {
    flex: 0 0 50%;
    padding: 0 10px;
    margin-bottom: 10px;
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
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
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

/* Full width form groups for file inputs and button */
.full-width {
    flex: 0 0 100%;
}

.file-info {
    font-size: 12px;
    color: var(--dark-gray);
    margin-top: 5px;
}

@media (max-width: 768px) {
    .form-column {
        flex: 0 0 100%;
    }
    
    .container {
        padding: 20px;
        max-width: 500px;
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
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
    <div class="form-row">
        <div class="form-column">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                <span class="alert-danger"><?php echo $name_err; ?></span>
            </div>
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" name="age" id="age" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $age; ?>" min="18" max="100">
                <span class="alert-danger"><?php echo $age_err; ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column">
            <div class="form-group">
                <label for="phone_number">Phone Number (10 digits)</label>
                <input type="tel" name="phone_number" id="phone_number" class="form-control <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone_number; ?>" pattern="[0-9]{10}">
                <span class="alert-danger"><?php echo $phone_number_err; ?></span>
            </div>
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="email">Email ID</label>
                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="alert-danger"><?php echo $email_err; ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column">
            <div class="form-group">
                <label for="vehicle_type">Vehicle Type</label>
                <select name="vehicle_type" id="vehicle_type" class="form-control <?php echo (!empty($vehicle_type_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Vehicle</option>
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
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="charge">Service Charge (e.g., per hour/day)</label>
                <input type="number" name="charge" id="charge" class="form-control <?php echo (!empty($charge_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $charge; ?>" min="0" step="0.01">
                <span class="alert-danger"><?php echo $charge_err; ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column">
            <div class="form-group">
                <label for="location">Location (Village/Town)</label>
                <input type="text" name="location" id="location" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $location; ?>">
                <span class="alert-danger"><?php echo $location_err; ?></span>
            </div>
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="district">District</label>
                <input type="text" name="district" id="district" class="form-control <?php echo (!empty($district_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $district; ?>">
                <span class="alert-danger"><?php echo $district_err; ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column full-width">
            <div class="form-group">
                <label for="driver_photo">Driver Photo (JPG, PNG - Max 5MB)</label>
                <input type="file" name="driver_photo" id="driver_photo" class="form-control <?php echo (!empty($driver_photo_err)) ? 'is-invalid' : ''; ?>" accept=".jpg,.jpeg,.png">
                <span class="alert-danger"><?php echo $driver_photo_err; ?></span>
                <div class="file-info">Accepted formats: JPG, JPEG, PNG | Max size: 5MB</div>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column full-width">
            <div class="form-group">
                <label for="license">License (PDF only - Max 10MB)</label>
                <input type="file" name="license" id="license" class="form-control <?php echo (!empty($license_err)) ? 'is-invalid' : ''; ?>" accept=".pdf">
                <span class="alert-danger"><?php echo $license_err; ?></span>
                <div class="file-info">Accepted format: PDF | Max size: 10MB</div>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $password_err; ?></span>
            </div>
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="alert-danger"><?php echo $confirm_password_err; ?></span>
            </div>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-column full-width">
            <div class="form-group">
                <input type="submit" class="btn" value="Register">
            </div>
        </div>
    </div>
    
    <p class="link-text">Already have an account? <a href="driver_login.php">Login here</a>.</p>
</form>
    </div>

    <script>
        // Add some client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone_number');
            const ageInput = document.getElementById('age');
            const chargeInput = document.getElementById('charge');
            
            // Phone number validation
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
            
            // Age validation
            ageInput.addEventListener('input', function() {
                if (this.value < 18) this.value = 18;
                if (this.value > 100) this.value = 100;
            });
            
            // Charge validation
            chargeInput.addEventListener('input', function() {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
</body>
</html>