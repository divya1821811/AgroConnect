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
$message = '';
$booking_date_err = $message_err = '';
$success_message = $error_message = '';

// Check if driver_id is provided in the URL
if (isset($_GET['driver_id']) && !empty(trim($_GET['driver_id']))) {
    $driver_id = trim($_GET['driver_id']);

    // Fetch driver details to display on the booking page
    $sql_driver = "SELECT id, name, phone_number, vehicle_type, location, district, charge FROM drivers WHERE id = ?";
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
        // Optional: Add more robust date validation (e.g., not past dates)
        if (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
            $booking_date_err = 'Booking date cannot be in the past.';
        }
    }

    // Validate message (optional)
    $message = trim($_POST['message']);

    // If no validation errors, proceed with booking
    if (empty($booking_date_err) && empty($message_err)) {
        $user_id = $_SESSION['user_id'];
        $driver_id_to_book = $driver_details['id']; // Use the ID fetched from DB

        $sql_insert = "INSERT INTO bookings (user_id, driver_id, booking_date, message, status) VALUES (?, ?, ?, ?, 'pending')";
        if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, 'iiss', $user_id, $driver_id_to_book, $booking_date, $message);
            if (mysqli_stmt_execute($stmt_insert)) {
                $success_message = 'Vehicle booked successfully! Redirecting to booking history...';
                // Redirect after a short delay to show success message
                header('refresh:3;url=user_history.php'); // Redirect after 3 seconds
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
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for booking page */
        body {
            background-color: #f0f5f0; /* Lighter green tint for background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px; /* Add padding for small screens */
            box-sizing: border-box;
        }

        .booking-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px; /* More rounded corners */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); /* Stronger shadow */
            width: 100%;
            max-width: 600px;
            text-align: left;
            animation: fadeIn 0.8s ease-out; /* Fade in animation for the container */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #28a745; /* AgroConnect Green */
            margin-bottom: 25px;
            text-align: center;
            font-size: 2.2em;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
        }

        .driver-info-box {
            background-color: #e6f7ff; /* Light blue background */
            border: 1px solid #b3e0ff;
            border-radius: 10px; /* Slightly more rounded */
            padding: 20px 25px; /* More padding */
            margin-bottom: 30px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.08); /* Subtle inner shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Hover effect */
        }

        .driver-info-box:hover {
            transform: translateY(-3px);
            box-shadow: inset 0 4px 8px rgba(0,0,0,0.1);
        }

        .driver-info-box h3 {
            color: #007bff; /* Primary Blue */
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.6em;
            border-bottom: 1px dashed #cceeff; /* Subtle separator */
            padding-bottom: 8px;
        }

        .driver-info-box p {
            margin: 8px 0; /* More spacing */
            color: #444;
            font-size: 1.05em;
        }

        .driver-info-box p strong {
            color: #0056b3; /* Darker Blue */
        }

        .form-group {
            margin-bottom: 25px; /* More spacing between form groups */
        }

        .form-group label {
            display: block;
            margin-bottom: 10px; /* More spacing for labels */
            color: #333;
            font-weight: bold;
            font-size: 1.1em;
        }

        .form-group input[type="date"],
        .form-group textarea {
            width: calc(100% - 24px); /* Adjust for padding and border */
            padding: 12px;
            border: 1px solid #ced4da; /* Lighter border */
            border-radius: 8px; /* More rounded inputs */
            font-size: 16px;
            min-height: 100px; /* Make textarea larger */
            resize: vertical;
            transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Focus animation */
            box-sizing: border-box; /* Include padding in width */
            background-color: #fdfdfd; /* Slightly off-white background */
        }

        .form-group input[type="date"]:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); /* Blue glow on focus */
            outline: none;
        }

        /* Specific styling for the date input */
        #booking_date {
            color: #333; /* Ensure text color is readable */
            height: 45px; /* Consistent height */
        }

        .btn {
            display: inline-block; /* For better button spacing */
            padding: 12px 25px;
            border: none;
            border-radius: 8px; /* More rounded buttons */
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            text-decoration: none; /* Remove underline for links */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-3px); /* Lift effect */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15); /* Enhanced shadow */
        }

        .btn-book-confirm {
            background-color: #28a745; /* AgroConnect Green */
            color: white;
            width: 100%; /* Full width button */
            margin-top: 20px; /* Spacing from textarea */
        }

        .btn-book-confirm:hover {
            background-color: #218838; /* Darker green on hover */
        }

        .btn-back {
            background-color: #6c757d; /* Muted Grey */
            color: white;
            width: 90%; /* Full width button */
            margin-top: 20px; /* Spacing from confirm button */
        }

        .btn-back:hover {
            background-color: #5a6268; /* Darker grey on hover */
        }

        /* Alert messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 1em;
            text-align: center;
            font-weight: bold;
            animation: slideIn 0.5s ease-out; /* Animation for alerts */
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .booking-container {
                margin: 15px;
                padding: 20px;
            }
            h2 {
                font-size: 1.8em;
            }
            .driver-info-box {
                padding: 15px;
            }
            .driver-info-box h3 {
                font-size: 1.4em;
            }
            .form-group label {
                font-size: 1em;
            }
            .btn {
                font-size: 1em;
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .booking-container {
                padding: 15px;
            }
            h2 {
                font-size: 1.6em;
            }
            .driver-info-box {
                padding: 12px;
            }
            .driver-info-box h3 {
                font-size: 1.3em;
            }
            .form-group input[type="date"],
            .form-group textarea {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container booking-container">
        <h2>Book Transport Vehicle</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($driver_details): ?>
            <div class="driver-info-box">
                <h3>Booking with: <?php echo htmlspecialchars($driver_details['name']); ?></h3>
                <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($driver_details['vehicle_type']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($driver_details['phone_number']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($driver_details['location']); ?>, <?php echo htmlspecialchars($driver_details['district']); ?></p>
                <p><strong>Service Charge:</strong> ₹<?php echo htmlspecialchars(number_format($driver_details['charge'], 2)); ?></p>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?driver_id=' . htmlspecialchars($driver_id); ?>" method="post">
                <div class="form-group">
                    <label for="booking_date">Preferred Booking Date:</label>
                    <input type="date" name="booking_date" id="booking_date" class="form-control <?php echo (!empty($booking_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $booking_date; ?>" min="<?php echo date('Y-m-d'); ?>">
                    <span class="alert-danger"><?php echo $booking_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="message">Your Message to Driver (Optional):</label>
                    <textarea name="message" id="message" class="form-control <?php echo (!empty($message_err)) ? 'is-invalid' : ''; ?>" placeholder="e.g., Type of produce, estimated load, pick-up time, special instructions."><?php echo htmlspecialchars($message); ?></textarea>
                    <span class="alert-danger"><?php echo $message_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-book-confirm" value="Confirm Booking">
                </div>
            </form>
        <?php endif; ?>

        <p style="text-align: center;">
            <a href="transport_list.php" class="btn btn-back">Back to Transport List</a>
        </p>
    </div>
</body>
</html>
