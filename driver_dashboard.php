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
$sql_accepted = "SELECT b.id AS booking_id, b.booking_date, b.message, b.status,
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
            .btn-accept, .btn-call-user {
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
            .btn-accept, .btn-call-user {
                font-size: 0.9em;
                padding: 8px 15px;
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

        <h3 class="section-title"><i class="fas fa-clock"></i> New Booking Requests (Pending)</h3>
        <?php if (!empty($pending_bookings)): ?>
            <?php foreach ($pending_bookings as $booking): ?>
                <div class="booking-request-card">
                    <h3>Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> for <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></h3>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    <?php if (!empty($booking['message'])): ?>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
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

        <h3 class="section-title"><i class="fas fa-list-alt"></i> Your Accepted Bookings</h3>
        <?php if (!empty($accepted_bookings)): ?>
            <?php foreach ($accepted_bookings as $booking): ?>
                <div class="accepted-booking-card">
                    <h3>
                        Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> on <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                        <span class="status-badge <?php echo strtolower(htmlspecialchars($booking['status'])); ?>"><?php echo htmlspecialchars($booking['status']); ?></span>
                    </h3>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    <?php if (!empty($booking['message'])): ?>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                    <?php endif; ?>
                    <div class="booking-actions">
                         <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" class="btn btn-call-user"><i class="fas fa-envelope"></i> Email User</a>
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
</body>
</html>
