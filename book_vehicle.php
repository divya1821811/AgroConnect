<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

$driver_id = '';
$driver_details = null;
$booking_date = '';
$estimated_load = '';
$pickup_time = '';
$special_instructions = '';
$location_link = '';
$booking_date_err = $estimated_load_err = $pickup_time_err = $special_instructions_err = $location_link_err = '';
$success_message = $error_message = '';

// Check if driver_id is provided in the URL
if (isset($_GET['driver_id']) && !empty(trim($_GET['driver_id']))) {
    $driver_id = trim($_GET['driver_id']);

    // Fetch driver details including photo and license
    $sql_driver = "SELECT id, name, phone_number, vehicle_type, location, district, charge, driver_photo, licence FROM drivers WHERE id = ?";
    if ($stmt_driver = mysqli_prepare($conn, $sql_driver)) {
        mysqli_stmt_bind_param($stmt_driver, 'i', $param_driver_id);
        $param_driver_id = $driver_id;
        if (mysqli_stmt_execute($stmt_driver)) {
            $result_driver = mysqli_stmt_get_result($stmt_driver);
            if (mysqli_num_rows($result_driver) == 1) {
                $driver_details = mysqli_fetch_assoc($result_driver);
            } else {
                $error_message = 'Driver not found.';
            }
        } else {
            $error_message = 'Oops! Something went wrong fetching driver details.';
        }
        mysqli_stmt_close($stmt_driver);
    }
} else {
    // If no driver ID is provided, redirect back to transport list
    header('location: transport_list.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $driver_details) {
    // Validate booking date
    if (empty(trim($_POST['booking_date']))) {
        $booking_date_err = 'Please select a booking date.';
    } else {
        $booking_date = trim($_POST['booking_date']);
        if (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
            $booking_date_err = 'Booking date cannot be in the past.';
        }
    }

    // Validate estimated load
    if (empty(trim($_POST['estimated_load']))) {
        $estimated_load_err = 'Please enter estimated load.';
    } else {
        $estimated_load = trim($_POST['estimated_load']);
    }

    // Validate pickup time
    if (empty(trim($_POST['pickup_time']))) {
        $pickup_time_err = 'Please select pickup time.';
    } else {
        $pickup_time = trim($_POST['pickup_time']);
    }

    // Validate special instructions
    if (empty(trim($_POST['special_instructions']))) {
        $special_instructions_err = 'Please enter special instructions.';
    } else {
        $special_instructions = trim($_POST['special_instructions']);
    }

    // Validate location link
    if (empty(trim($_POST['location_link']))) {
        $location_link_err = 'Please enter location link.';
    } else {
        $location_link = trim($_POST['location_link']);
        // Basic URL validation
        if (!filter_var($location_link, FILTER_VALIDATE_URL)) {
            $location_link_err = 'Please enter a valid URL (e.g., https://maps.google.com/...)';
        }
    }

    // If no validation errors, proceed with booking
    if (empty($booking_date_err) && empty($estimated_load_err) && empty($pickup_time_err) && empty($special_instructions_err) && empty($location_link_err)) {
        $user_id = $_SESSION['user_id'];
        $driver_id_to_book = $driver_details['id'];

        // Combine all fields into one message for the database
        $combined_message = "Estimated Load: " . $estimated_load . "\n" .
                           "Pickup Time: " . $pickup_time . "\n" .
                           "Special Instructions: " . $special_instructions . "\n" .
                           "Location Link: " . $location_link;

        $sql_insert = "INSERT INTO bookings (user_id, driver_id, booking_date, message, status) VALUES (?, ?, ?, ?, 'pending')";
        if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, 'iiss', $user_id, $driver_id_to_book, $booking_date, $combined_message);
            if (mysqli_stmt_execute($stmt_insert)) {
                $success_message = 'Vehicle booked successfully! Redirecting to booking history...';
                header('refresh:3;url=user_history.php');
                exit();
            } else {
                $error_message = 'Error: Could not process booking. ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $error_message = 'Error: Could not prepare booking statement.';
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
    <title>Book Vehicle - AgroConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
    <style>
        :root {
            --primary-green: #27ae60;
            --primary-dark: #2c3e50;
            --primary-blue: #3498db;
            --accent-orange: #e67e22;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-light: #e9ecef;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #27ae60, #2ecc71);
            --gradient-secondary: linear-gradient(135deg, #3498db, #2980b9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .booking-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .booking-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        h2 {
            color: var(--primary-dark);
            text-align: center;
            margin-bottom: 35px;
            font-size: 2.4rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
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

        /* Driver Info Section */
        .driver-info-box {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 35px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        /* Location Auto-fill Styles */
.location-auto-fill {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.btn-location {
    background: var(--gradient-secondary);
    color: var(--white);
    border: none;
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
}

.btn-location:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
}

.btn-location:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.location-status {
    font-size: 0.85rem;
    font-weight: 500;
    transition: var(--transition);
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .location-auto-fill {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .btn-location {
        width: 100%;
        justify-content: center;
    }
}

        .driver-info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--gradient-primary);
        }

        .driver-info-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .driver-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .driver-photo-container {
            position: relative;
            flex-shrink: 0;
        }

        .driver-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .driver-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .default-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid var(--white);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-size: 2rem;
            color: var(--white);
            font-weight: 700;
            transition: var(--transition);
        }

        .default-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .driver-info-content {
            flex: 1;
        }

        .driver-info-content h3 {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .driver-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .meta-item i {
            color: var(--primary-green);
            width: 16px;
        }

        .license-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed var(--border-light);
        }

        .license-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--gradient-secondary);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .license-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            color: var(--white);
        }

        /* Form Styles */
        .booking-form {
            background: var(--white);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .booking-form:hover {
            box-shadow: var(--shadow-hover);
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
        }

        .required::after {
            content: " *";
            color: #e74c3c;
            font-weight: 700;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--light-bg);
            transition: var(--transition);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .form-control:hover {
            border-color: #bdc3c7;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .field-info {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .field-info i {
            color: var(--primary-blue);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-book-confirm {
            background: var(--gradient-primary);
            color: var(--white);
            width: 100%;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-book-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }

        .btn-back {
            background: var(--primary-dark);
            color: var(--white);
            width: 100%;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-back:hover {
            background: #34495e;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
        }

        /* Alert Messages */
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            animation: slideIn 0.5s ease-out;
            border-left: 5px solid;
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

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .alert-danger span {
            display: block;
            margin-top: 5px;
            font-size: 0.9rem;
            font-weight: normal;
        }

        /* Error States */
        .is-invalid {
            border-color: #e74c3c !important;
            background: #fdf2f2 !important;
        }

        .is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-container {
                padding: 25px;
                margin: 10px;
            }

            h2 {
                font-size: 2rem;
            }

            .driver-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .driver-photo, .default-photo {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }

            .driver-meta {
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .btn {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .booking-container {
                padding: 20px;
                border-radius: 15px;
            }

            h2 {
                font-size: 1.8rem;
            }

            .driver-info-box {
                padding: 20px;
            }

            .booking-form {
                padding: 20px;
            }

            .form-control {
                padding: 12px 15px;
            }
        }

        /* Loading state for buttons */
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Focus states for accessibility */
        .form-control:focus-visible,
        .btn:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container booking-container">
        <h2><i class="fas fa-calendar-check"></i> Book Transport Vehicle</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($driver_details): ?>
            <div class="driver-info-box">
                <div class="driver-header">
                    <div class="driver-photo-container">
                        <?php if (!empty($driver_details['driver_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($driver_details['driver_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($driver_details['name']); ?>" 
                                 class="driver-photo">
                        <?php else: ?>
                            <div class="default-photo">
                                <?php echo strtoupper(substr($driver_details['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="driver-info-content">
                        <h3><?php echo htmlspecialchars($driver_details['name']); ?></h3>
                        <div class="driver-meta">
                            <div class="meta-item">
                                <i class="fas fa-truck"></i>
                                <span><?php echo htmlspecialchars($driver_details['vehicle_type']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($driver_details['phone_number']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($driver_details['location']); ?>, <?php echo htmlspecialchars($driver_details['district']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-indian-rupee-sign"></i>
                                <span>₹<?php echo htmlspecialchars(number_format($driver_details['charge'], 2)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($driver_details['licence'])): ?>
                    <div class="license-section">
                        <a href="<?php echo htmlspecialchars($driver_details['licence']); ?>" 
                           target="_blank" 
                           class="license-link">
                            <i class="fas fa-file-pdf"></i>
                            View Driving License
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?driver_id=' . htmlspecialchars($driver_id); ?>" method="post" class="booking-form">
                <div class="form-group">
                    <label for="booking_date" class="required">
                        <i class="fas fa-calendar-day"></i> Preferred Booking Date
                    </label>
                    <input type="date" name="booking_date" id="booking_date" 
                           class="form-control <?php echo (!empty($booking_date_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo $booking_date; ?>" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           required>
                    <?php if (!empty($booking_date_err)): ?>
                        <span class="alert-danger"><?php echo $booking_date_err; ?></span>
                    <?php endif; ?>
                    <div class="field-info">
                        <i class="fas fa-info-circle"></i>
                        Select your preferred date for transportation
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_load" class="required">
                            <i class="fas fa-weight-hanging"></i> Estimated Load
                        </label>
                        <input type="text" name="estimated_load" id="estimated_load" 
                               class="form-control <?php echo (!empty($estimated_load_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($estimated_load); ?>" 
                               placeholder="e.g., 2 tons of wheat, 50 bags" 
                               required>
                        <?php if (!empty($estimated_load_err)): ?>
                            <span class="alert-danger"><?php echo $estimated_load_err; ?></span>
                        <?php endif; ?>
                        <div class="field-info">
                            <i class="fas fa-info-circle"></i>
                            Specify quantity and type of goods
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pickup_time" class="required">
                            <i class="fas fa-clock"></i> Preferred Pickup Time
                        </label>
                        <input type="time" name="pickup_time" id="pickup_time" 
                               class="form-control <?php echo (!empty($pickup_time_err)) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($pickup_time); ?>" 
                               required>
                        <?php if (!empty($pickup_time_err)): ?>
                            <span class="alert-danger"><?php echo $pickup_time_err; ?></span>
                        <?php endif; ?>
                        <div class="field-info">
                            <i class="fas fa-info-circle"></i>
                            When should the driver arrive?
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location_link" class="required">
                        <i class="fas fa-map-marked-alt"></i> Location Link
                    </label>
                    <input type="url" name="location_link" id="location_link" 
                           class="form-control <?php echo (!empty($location_link_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($location_link); ?>" 
                           placeholder="https://maps.google.com/... or https://goo.gl/maps/..." 
                           required>
                    <?php if (!empty($location_link_err)): ?>
                        <span class="alert-danger"><?php echo $location_link_err; ?></span>
                    <?php endif; ?>
                    <div class="field-info">
                        <i class="fas fa-info-circle"></i>
                        Paste Google Maps link to your exact pickup location
                    </div>
                </div>

                <div class="form-group">
                    <label for="special_instructions" class="required"> 
                        <i class="fas fa-clipboard-list"></i> Special Instructions
                    </label>
                    <textarea name="special_instructions" id="special_instructions" 
                              class="form-control <?php echo (!empty($special_instructions_err)) ? 'is-invalid' : ''; ?>" 
                              placeholder="e.g., Need help with loading, fragile items, specific vehicle requirements, contact person details" 
                              required><?php echo htmlspecialchars($special_instructions); ?></textarea>
                    <?php if (!empty($special_instructions_err)): ?>
                        <span class="alert-danger"><?php echo $special_instructions_err; ?></span>
                    <?php endif; ?>
                    <div class="field-info">
                        <i class="fas fa-info-circle"></i>
                        Any additional information the driver should know
                    </div>
                </div>

                <button type="submit" class="btn btn-book-confirm">
                    <i class="fas fa-paper-plane"></i>
                    Confirm Booking
                </button>
            </form>
        <?php endif; ?>

        <a href="transport_list.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Transport List
        </a>
    </div>

    <script>
        // Add this JavaScript code to handle location auto-fill
document.addEventListener('DOMContentLoaded', function() {
    const locationLinkInput = document.getElementById('location_link');
    
    // Create location auto-fill button
    const locationAutoFill = document.createElement('div');
    locationAutoFill.className = 'location-auto-fill';
    locationAutoFill.innerHTML = `
        <button type="button" class="btn-location" id="getLocationBtn">
            <i class="fas fa-map-marker-alt"></i>
            Use My Current Location
        </button>
        <span class="location-status" id="locationStatus"></span>
    `;
    
    // Insert the location auto-fill button before the location input field
    locationLinkInput.parentNode.insertBefore(locationAutoFill, locationLinkInput);
    
    const getLocationBtn = document.getElementById('getLocationBtn');
    const locationStatus = document.getElementById('locationStatus');
    
    // Location auto-fill functionality
    getLocationBtn.addEventListener('click', function() {
        if (!navigator.geolocation) {
            locationStatus.textContent = 'Geolocation is not supported by this browser.';
            locationStatus.style.color = '#e74c3c';
            return;
        }
        
        // Show loading state
        getLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
        getLocationBtn.disabled = true;
        locationStatus.textContent = 'Requesting location access...';
        locationStatus.style.color = '#f39c12';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Success callback
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                // Create Google Maps link
                const mapsLink = `https://www.google.com/maps?q=${latitude},${longitude}`;
                
                // Fill the input field
                locationLinkInput.value = mapsLink;
                
                // Update status
                locationStatus.textContent = 'Location auto-filled successfully!';
                locationStatus.style.color = '#27ae60';
                
                // Reset button
                getLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use My Current Location';
                getLocationBtn.disabled = false;
            },
            function(error) {
                // Error callback
                let errorMessage = 'Location access denied. Please paste manually.';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Location access denied. Please paste manually.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information unavailable. Please paste manually.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out. Please paste manually.';
                        break;
                    default:
                        errorMessage = 'An unknown error occurred. Please paste manually.';
                        break;
                }
                
                locationStatus.textContent = errorMessage;
                locationStatus.style.color = '#e74c3c';
                
                // Reset button
                getLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use My Current Location';
                getLocationBtn.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    });
    
    // Update the field-info text to mention auto-fill
    const fieldInfo = locationLinkInput.nextElementSibling;
    if (fieldInfo && fieldInfo.classList.contains('field-info')) {
        fieldInfo.innerHTML = `
            <i class="fas fa-info-circle"></i>
            Click "Use My Current Location" or paste Google Maps link to your exact pickup location
        `;
    }
});
        // Enhanced form functionality
        document.addEventListener('DOMContentLoaded', function() {
            const bookingDate = document.getElementById('booking_date');
            const pickupTime = document.getElementById('pickup_time');
            const form = document.querySelector('form');
            
            // Set minimum time based on selected date
            bookingDate.addEventListener('change', function() {
                const today = new Date().toISOString().split('T')[0];
                if (this.value === today) {
                    const now = new Date();
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    pickupTime.min = `${hours}:${minutes}`;
                } else {
                    pickupTime.removeAttribute('min');
                }
            });

            // Form submission loading state
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Processing...';
            });

            // Real-time validation
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Initialize pickup time min if booking date is today
            if (bookingDate.value === new Date().toISOString().split('T')[0]) {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                pickupTime.min = `${hours}:${minutes}`;
            }
        });
    </script>
</body>
</html>