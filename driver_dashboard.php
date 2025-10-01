<?php
// Start the session
session_start();

// Check if the driver is logged in, if not then redirect to driver login page
if (!isset($_SESSION['driver_id']) || $_SESSION['user_type'] != 'driver') {
    header('location: driver_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

$driver_id = $_SESSION['driver_id']; // Get the logged-in driver's ID
$pending_bookings = [];
$accepted_bookings = [];
$success_message = '';
$error_message = '';

// --- Fetch Driver Profile Data ---
$driver_profile = [];
$sql_driver = "SELECT name, email, phone_number, location, district, licence, driver_photo, charge 
               FROM drivers WHERE id = ?";
if ($stmt_driver = mysqli_prepare($conn, $sql_driver)) {
    mysqli_stmt_bind_param($stmt_driver, 'i', $driver_id);
    if (mysqli_stmt_execute($stmt_driver)) {
        $result_driver = mysqli_stmt_get_result($stmt_driver);
        $driver_profile = mysqli_fetch_assoc($result_driver);
        if (!$driver_profile) {
            $error_message .= 'Error fetching driver profile.';
        }
    } else {
        $error_message .= 'Error fetching driver profile: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt_driver);
} else {
    $error_message .= 'Error preparing driver profile query: ' . mysqli_error($conn);
}

// --- Handle Booking Acceptance ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accept_booking'])) {
    $booking_id_to_accept = trim($_POST['booking_id']);

    // Update the booking status to 'confirmed'
    $sql_update = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND driver_id = ?";
    if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
        mysqli_stmt_bind_param($stmt_update, 'ii', $booking_id_to_accept, $driver_id);
        if (mysqli_stmt_execute($stmt_update)) {
            if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                $success_message = 'Booking accepted successfully!';
            } else {
                $error_message = 'Booking not found or already accepted.';
            }
        } else {
            $error_message = 'Error accepting booking: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $error_message = 'Error preparing update statement: ' . mysqli_error($conn);
    }
}

// --- Handle Booking Cancellation ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id_to_cancel = trim($_POST['booking_id']);
    
    // Check if booking is within 24 hours and is confirmed
    $sql_check_time = "SELECT created_at, status FROM bookings WHERE id = ? AND driver_id = ?";
    if ($stmt_check = mysqli_prepare($conn, $sql_check_time)) {
        mysqli_stmt_bind_param($stmt_check, 'ii', $booking_id_to_cancel, $driver_id);
        if (mysqli_stmt_execute($stmt_check)) {
            $result_check = mysqli_stmt_get_result($stmt_check);
            $booking = mysqli_fetch_assoc($result_check);
            
            if ($booking) {
                $created_at = strtotime($booking['created_at']);
                $current_time = time();
                $time_diff = $current_time - $created_at;
                $hours_diff = $time_diff / (60 * 60); // Convert to hours
                
                if ($hours_diff <= 24 && $booking['status'] == 'confirmed') {
                    // Update the booking status to 'cancelled'
                    $sql_cancel = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND driver_id = ?";
                    if ($stmt_cancel = mysqli_prepare($conn, $sql_cancel)) {
                        mysqli_stmt_bind_param($stmt_cancel, 'ii', $booking_id_to_cancel, $driver_id);
                        if (mysqli_stmt_execute($stmt_cancel)) {
                            if (mysqli_stmt_affected_rows($stmt_cancel) > 0) {
                                $success_message = 'Booking cancelled successfully!';
                                // Refresh the page to show updated status
                                header('Location: ' . $_SERVER['PHP_SELF']);
                                exit();
                            } else {
                                $error_message = 'Booking not found or already cancelled.';
                            }
                        } else {
                            $error_message = 'Error cancelling booking: ' . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_cancel);
                    } else {
                        $error_message = 'Error preparing cancel statement: ' . mysqli_error($conn);
                    }
                } else {
                    if ($hours_diff > 24) {
                        $error_message = 'Cannot cancel booking. 24-hour cancellation period has expired.';
                    } else {
                        $error_message = 'Cannot cancel booking. Booking is not in confirmed status.';
                    }
                }
            } else {
                $error_message = 'Booking not found.';
            }
        } else {
            $error_message = 'Error checking booking time: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_check);
    } else {
        $error_message = 'Error preparing time check statement: ' . mysqli_error($conn);
    }
}

// --- Fetch Pending Bookings for the Logged-in Driver ---
$sql_pending = "SELECT b.id AS booking_id, b.booking_date, b.message,
                        u.name AS user_name, u.email AS user_email
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.driver_id = ? AND b.status = 'pending'
                ORDER BY b.booking_date ASC, b.created_at ASC"; // Order by oldest first

if ($stmt_pending = mysqli_prepare($conn, $sql_pending)) {
    mysqli_stmt_bind_param($stmt_pending, 'i', $driver_id);
    if (mysqli_stmt_execute($stmt_pending)) {
        $result_pending = mysqli_stmt_get_result($stmt_pending);
        while ($row = mysqli_fetch_assoc($result_pending)) {
            $pending_bookings[] = $row;
        }
    } else {
        $error_message .= 'Error fetching pending bookings: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt_pending);
} else {
    $error_message .= 'Error preparing pending bookings query: ' . mysqli_error($conn);
}

// --- Fetch Accepted/Confirmed Bookings for the Logged-in Driver ---
$sql_accepted = "SELECT b.id AS booking_id, b.booking_date, b.message, b.status, b.created_at,
                        u.name AS user_name, u.email AS user_email
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.driver_id = ? AND (b.status = 'confirmed' OR b.status = 'completed' OR b.status = 'cancelled')
                ORDER BY b.booking_date DESC, b.created_at DESC"; // Order by newest first

if ($stmt_accepted = mysqli_prepare($conn, $sql_accepted)) {
    mysqli_stmt_bind_param($stmt_accepted, 'i', $driver_id);
    if (mysqli_stmt_execute($stmt_accepted)) {
        $result_accepted = mysqli_stmt_get_result($stmt_accepted);
        while ($row = mysqli_fetch_assoc($result_accepted)) {
            $accepted_bookings[] = $row;
        }
    } else {
        $error_message .= 'Error fetching confirmed bookings: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt_accepted);
} else {
    $error_message .= 'Error preparing confirmed bookings query: ' . mysqli_error($conn);
}

// Add this function to parse booking details
function parseBookingDetails($message) {
    $details = [
        'estimated_load' => '',
        'pickup_time' => '',
        'special_instructions' => '',
        'location_link' => ''
    ];
    
    if (empty($message)) return $details;
    
    $lines = explode("\n", $message);
    
    foreach ($lines as $line) {
        if (strpos($line, 'Estimated Load:') !== false) {
            $details['estimated_load'] = trim(str_replace('Estimated Load:', '', $line));
        } elseif (strpos($line, 'Pickup Time:') !== false) {
            $details['pickup_time'] = trim(str_replace('Pickup Time:', '', $line));
        } elseif (strpos($line, 'Special Instructions:') !== false) {
            $details['special_instructions'] = trim(str_replace('Special Instructions:', '', $line));
        } elseif (strpos($line, 'Location Link:') !== false) {
            $details['location_link'] = trim(str_replace('Location Link:', '', $line));
        }
    }
    
    return $details;
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - AgroConnect</title>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Shared theme variables for consistency */
        :root {
            --primary-green: #28a745;
            --secondary-green: #218838;
            --light-green: #e9f5e9;
            --primary-blue: #007bff;
            --secondary-blue: #0056b3;
            --light-blue: #e6f7ff;
            --white: #ffffff;
            --dark: #333333;
            --light-gray: #f0f5f0; /* Consistent body background */
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
            --card-radius: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif; /* Consistent font */
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex; /* For centering content */
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Specific styles for driver dashboard */
        .driver-dashboard-container {
            max-width: 900px;
            width: 100%; /* Ensure it takes full width up to max */
            background-color: var(--white);
            padding: 30px; /* Increased padding */
            border-radius: var(--card-radius); /* Consistent border-radius */
            box-shadow: var(--shadow); /* Consistent shadow */
            text-align: left;
            animation: fadeIn 0.8s ease-out; /* Add a fade-in animation */
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .driver-dashboard-container h2 {
            color: var(--primary-green); /* Themed green */
            margin-top: 0;
            margin-bottom: 25px; /* Increased margin */
            font-size: 2.2em; /* Larger heading */
            border-bottom: 2px solid var(--medium-gray); /* Themed border */
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .driver-dashboard-container h2 i {
            font-size: 1.2em;
        }

        .section-title {
            color: var(--primary-green); /* Themed green */
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--medium-gray); /* Themed green border */
            padding-bottom: 8px;
            display: flex; /* Use flex for icon alignment */
            align-items: center;
            gap: 10px; /* Space between icon and text */
        }
        .section-title i {
            font-size: 1.2em;
        }

        .booking-request-card,
        .accepted-booking-card {
            background-color: var(--light-green); /* Light green background for cards */
            border: 1px solid rgba(40, 167, 69, 0.1); /* Subtle green border */
            border-radius: var(--card-radius); /* Consistent border-radius */
            padding: 25px; /* Increased padding */
            margin-bottom: 20px;
            box-shadow: var(--shadow); /* Consistent shadow */
            transition: var(--transition); /* Consistent transition */
        }

        .booking-request-card:hover,
        .accepted-booking-card:hover {
            transform: translateY(-5px); /* Consistent hover effect */
            box-shadow: var(--shadow-hover); /* Consistent hover shadow */
        }

        .booking-request-card h3,
        .accepted-booking-card h3 {
            color: var(--primary-blue); /* Themed blue for booking ID/date */
            margin-top: 0;
            margin-bottom: 15px; /* Increased margin */
            font-size: 1.6em; /* Slightly larger heading */
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }

        .booking-request-card p,
        .accepted-booking-card p {
            margin: 8px 0; /* Adjusted margin */
            color: var(--dark); /* Consistent dark text */
            line-height: 1.6; /* Improved readability */
            font-size: 1.05em; /* Slightly larger text */
        }

        .booking-request-card p strong,
        .accepted-booking-card p strong {
            color: var(--dark); /* Stronger dark color */
        }

        .booking-actions {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 10px; /* Space between buttons */
            justify-content: flex-end; /* Align actions to the right */
        }

        /* Buttons consistent with dashboard */
        .btn {
            padding: 10px 20px;
            border-radius: 8px; /* Consistent border-radius */
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            border: none;
            text-align: center;
        }

        .btn-accept {
            background-color: var(--success); /* Green for accept */
            color: var(--white);
        }
        .btn-accept:hover {
            background-color: var(--secondary-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-call-user {
            background-color: var(--info); /* Blue for call/email */
            color: var(--white);
        }
        .btn-call-user:hover {
            background-color: #117a8b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .no-bookings {
            text-align: center;
            padding: 30px;
            font-size: 1.1em;
            color: var(--dark-gray); /* Themed gray */
            background-color: var(--light-gray);
            border-radius: var(--card-radius);
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05); /* Subtle inner shadow */
        }

        .logout-btn {
            background-color: var(--danger); /* Red for logout */
            color: var(--white);
            margin-top: 30px; /* Space from last section */
            width: auto; /* Allow button to size itself */
            display: block; /* Make it block level */
            margin-left: auto; /* Center horizontally if block */
            margin-right: auto;
            max-width: 200px; /* Max width for button */
        }
        .logout-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .status-badge {
            padding: 6px 12px; /* Consistent padding */
            border-radius: 20px; /* More rounded pill shape */
            font-weight: bold;
            text-transform: capitalize;
            font-size: 0.95em; /* Slightly larger font */
            display: inline-block;
            margin-left: 10px; /* Space from text */
            color: var(--white); /* White text for all status badges by default */
        }
        .status-badge.pending { background-color: var(--warning); color: var(--dark); } /* Yellow background, dark text */
        .status-badge.confirmed { background-color: var(--success); }
        .status-badge.completed { background-color: var(--dark-gray); } /* Dark gray for completed */
        .status-badge.cancelled { background-color: var(--danger); }

        /* Profile Section Styles */
        .profile-section {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: var(--white);
            padding: 20px;
            border-radius: var(--card-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .profile-section:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h3 {
            margin-bottom: 5px;
            font-size: 1.5em;
        }

        .profile-info p {
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .btn-profile {
            background-color: var(--white);
            color: var(--primary-green);
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 20px;
            transition: var(--transition);
        }

        .btn-profile:hover {
            background-color: var(--light-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
            animation: fadeInModal 0.3s ease-out;
        }

        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--card-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .modal.active .modal-content {
            transform: scale(1);
            animation: zoomIn 0.3s ease-out;
        }

        @keyframes zoomIn {
            from { transform: scale(0.9); }
            to { transform: scale(1); }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: var(--white);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5em;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5em;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 20px;
        }

        .profile-detail {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .profile-detail-label {
            font-weight: bold;
            width: 120px;
            color: var(--dark);
        }

        .profile-detail-value {
            flex: 1;
            color: var(--dark-gray);
        }

        .modal-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 3px solid var(--primary-green);
        }

        /* Alert messages */
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

        /* NEW STYLES FOR LOCATION LINKS AND CANCELLATION */
        .btn-cancel {
            background-color: var(--danger);
            color: var(--white);
        }
        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-cancel:disabled {
            background-color: var(--dark-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .cancellation-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 10px 15px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .cancellation-info i {
            color: var(--warning);
            margin-right: 8px;
        }

        .time-remaining {
            font-weight: bold;
            color: var(--danger);
        }

        .booking-time-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: rgba(52, 152, 219, 0.05);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .location-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            word-break: break-all;
        }

        .location-link:hover {
            color: var(--secondary-blue);
            background: rgba(52, 152, 219, 0.15);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
            text-decoration: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .driver-dashboard-container {
                margin: 15px auto;
                padding: 20px;
            }
            .driver-dashboard-container h2 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }
            .section-title {
                font-size: 1.5em;
                margin-top: 25px;
            }
            .booking-request-card,
            .accepted-booking-card {
                padding: 20px;
            }
            .booking-request-card h3,
            .accepted-booking-card h3 {
                flex-direction: column;
                align-items: flex-start;
                font-size: 1.4em;
                margin-bottom: 10px;
            }
            .booking-request-card .status-badge,
            .accepted-booking-card .status-badge {
                margin-top: 8px;
                margin-left: 0;
            }
            .booking-actions {
                flex-direction: column;
                align-items: flex-start; /* Align actions to the left */
                gap: 10px;
            }
            .btn-accept, .btn-call-user, .btn-cancel {
                width: 100%; /* Full width buttons */
                text-align: center;
            }
            .no-bookings {
                padding: 20px;
                font-size: 1em;
            }
            .logout-btn {
                width: 100%;
                max-width: none; /* Remove max-width on smaller screens */
            }
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            .profile-photo {
                width: 80px;
                height: 80px;
            }
            .modal-content {
                width: 95%;
            }
            .booking-time-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .driver-dashboard-container {
                padding: 15px;
                margin: 10px auto;
            }
            .driver-dashboard-container h2 {
                font-size: 1.6em;
            }
            .section-title {
                font-size: 1.3em;
            }
            .booking-request-card,
            .accepted-booking-card {
                padding: 15px;
            }
            .booking-request-card h3,
            .accepted-booking-card h3 {
                font-size: 1.2em;
            }
            .booking-request-card p,
            .accepted-booking-card p {
                font-size: 1em;
                margin: 6px 0;
            }
            .btn-accept, .btn-call-user, .btn-cancel {
                font-size: 0.9em;
                padding: 8px 15px;
            }
            .profile-section {
                padding: 15px;
            }
            .modal-body {
                padding: 15px;
            }
            .profile-detail {
                flex-direction: column;
                margin-bottom: 10px;
            }
            .profile-detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container driver-dashboard-container">
        <h2><i class="fas fa-id-badge"></i> Welcome, Driver <?php echo htmlspecialchars($_SESSION['driver_name']); ?>!</h2>
        <p>Manage your booking requests and view your accepted jobs here.</p>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="profile-section">
            <?php if (!empty($driver_profile['driver_photo'])): ?>
                <img src="<?php echo htmlspecialchars($driver_profile['driver_photo']); ?>" alt="Driver Photo" class="profile-photo">
            <?php else: ?>
                <img src="https://via.placeholder.com/100" alt="Driver Photo" class="profile-photo">
            <?php endif; ?>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($driver_profile['name'] ?? 'Driver Name'); ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(($driver_profile['location'] ?? '') . ', ' . ($driver_profile['district'] ?? '')); ?></p>
                <p><i class="fas fa-money-bill-wave"></i> $<?php echo htmlspecialchars($driver_profile['charge'] ?? '0'); ?>/hour</p>
            </div>
            <button class="btn btn-profile" id="checkProfileBtn"><i class="fas fa-user-circle"></i> Check Profile</button>
        </div>

        <!-- Pending Bookings Section -->
        <h3 class="section-title"><i class="fas fa-clock"></i> New Booking Requests (Pending)</h3>
        <?php if (!empty($pending_bookings)): ?>
            <?php foreach ($pending_bookings as $booking): ?>
                <div class="booking-request-card">
                    <h3>Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> for <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></h3>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    
                    <?php 
                    $bookingDetails = parseBookingDetails($booking['message'] ?? '');
                    ?>
                    
                    <?php if (!empty($bookingDetails['estimated_load'])): ?>
                        <p><strong>Estimated Load:</strong> <?php echo htmlspecialchars($bookingDetails['estimated_load']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['pickup_time'])): ?>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($bookingDetails['pickup_time']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['location_link'])): ?>
                        <p><strong>Pickup Location:</strong> 
                            <a href="<?php echo htmlspecialchars($bookingDetails['location_link']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="location-link">
                                <i class="fas fa-external-link-alt"></i>
                                View on Google Maps
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['special_instructions'])): ?>
                        <p><strong>Special Instructions:</strong> <?php echo nl2br(htmlspecialchars($bookingDetails['special_instructions'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="booking-actions">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <button type="submit" name="accept_booking" class="btn btn-accept"><i class="fas fa-check-circle"></i> Accept Booking</button>
                        </form>
                        <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" class="btn btn-call-user"><i class="fas fa-envelope"></i> Email User</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-bookings">No new pending booking requests at the moment. Good job!</p>
        <?php endif; ?>

        <!-- Accepted Bookings Section -->
        <h3 class="section-title"><i class="fas fa-list-alt"></i> Your Accepted Bookings</h3>
        <?php if (!empty($accepted_bookings)): ?>
            <?php foreach ($accepted_bookings as $booking): ?>
                <div class="accepted-booking-card">
                    <div class="booking-time-info">
                        <span><i class="fas fa-calendar-plus"></i> Booked on: <?php echo date('F j, Y g:i A', strtotime($booking['created_at'])); ?></span>
                        <span class="status-badge <?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                    </div>
                    
                    <h3>
                        Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> on <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                    </h3>
                    
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    
                    <?php 
                    $bookingDetails = parseBookingDetails($booking['message'] ?? '');
                    ?>
                    
                    <?php if (!empty($bookingDetails['estimated_load'])): ?>
                        <p><strong>Estimated Load:</strong> <?php echo htmlspecialchars($bookingDetails['estimated_load']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['pickup_time'])): ?>
                        <p><strong>Pickup Time:</strong> <?php echo htmlspecialchars($bookingDetails['pickup_time']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['location_link'])): ?>
                        <p><strong>Pickup Location:</strong> 
                            <a href="<?php echo htmlspecialchars($bookingDetails['location_link']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="location-link">
                                <i class="fas fa-external-link-alt"></i>
                                View on Google Maps
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($bookingDetails['special_instructions'])): ?>
                        <p><strong>Special Instructions:</strong> <?php echo nl2br(htmlspecialchars($bookingDetails['special_instructions'])); ?></p>
                    <?php endif; ?>
                    
                    <!-- Cancellation Information -->
                    <?php
                    $created_time = strtotime($booking['created_at']);
                    $current_time = time();
                    $time_diff = $current_time - $created_time;
                    $hours_remaining = 24 - ($time_diff / (60 * 60));
                    $can_cancel = ($hours_remaining > 0 && $booking['status'] == 'confirmed');
                    ?>
                    
                    <?php if ($booking['status'] == 'confirmed'): ?>
                        <div class="cancellation-info">
                            <i class="fas fa-info-circle"></i>
                            <?php if ($can_cancel): ?>
                                You can cancel this booking within <span class="time-remaining"><?php echo number_format($hours_remaining, 1); ?> hours</span>
                            <?php else: ?>
                                <span class="time-remaining">Cancellation period has expired</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="booking-actions">
                        <?php if ($booking['status'] == 'confirmed'): ?>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" 
                                  onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                                <button type="submit" name="cancel_booking" class="btn btn-cancel" 
                                        <?php echo !$can_cancel ? 'disabled' : ''; ?>>
                                    <i class="fas fa-times-circle"></i>
                                    <?php echo $can_cancel ? 'Cancel Booking' : 'Cancellation Expired'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" class="btn btn-call-user">
                            <i class="fas fa-envelope"></i> Email Customer
                        </a>
                        
                        <?php if (!empty($bookingDetails['location_link'])): ?>
                            <a href="<?php echo htmlspecialchars($bookingDetails['location_link']); ?>" 
                               target="_blank" 
                               class="btn btn-call-user">
                                <i class="fas fa-directions"></i> Get Directions
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-bookings">You have no accepted or past bookings yet. Go accept some requests!</p>
        <?php endif; ?>

        <p>
            <a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </p>
    </div>

    <!-- Profile Modal -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> Driver Profile</h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($driver_profile['driver_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($driver_profile['driver_photo']); ?>" alt="Driver Photo" class="modal-photo">
                <?php else: ?>
                    <img src="https://via.placeholder.com/120" alt="Driver Photo" class="modal-photo">
                <?php endif; ?>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">Name:</div>
                    <div class="profile-detail-value"><?php echo htmlspecialchars($driver_profile['name'] ?? 'Not provided'); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">Email:</div>
                    <div class="profile-detail-value"><?php echo htmlspecialchars($driver_profile['email'] ?? 'Not provided'); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">Phone:</div>
                    <div class="profile-detail-value"><?php echo htmlspecialchars($driver_profile['phone_number'] ?? 'Not provided'); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">Address:</div>
                    <div class="profile-detail-value"><?php echo htmlspecialchars(($driver_profile['location'] ?? '') . ', ' . ($driver_profile['district'] ?? '')); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">License Number:</div>
                    <div class="profile-detail-value"><?php echo htmlspecialchars($driver_profile['licence'] ?? 'Not provided'); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="profile-detail-label">Hourly Rate:</div>
                    <div class="profile-detail-value">$<?php echo htmlspecialchars($driver_profile['charge'] ?? '0'); ?>/hour</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('profileModal');
            const checkProfileBtn = document.getElementById('checkProfileBtn');
            const closeModal = document.getElementById('closeModal');
            
            checkProfileBtn.addEventListener('click', function() {
                modal.classList.add('active');
            });
            
            closeModal.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
            
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            });

            // Enhanced cancellation confirmation
            const cancelForms = document.querySelectorAll('form[onsubmit]');
            cancelForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('⚠️ Are you sure you want to cancel this booking?\n\nThis action cannot be undone and the customer will be notified.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>