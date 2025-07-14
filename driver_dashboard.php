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
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for driver dashboard */
        .driver-dashboard-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 25px;
            background-color: #fcfcfc;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: left;
        }

        .section-title {
            color: #343a40;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 2px solid #28a745;
            padding-bottom: 8px;
            display: inline-block;
        }

        .booking-request-card,
        .accepted-booking-card {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .booking-request-card h3,
        .accepted-booking-card h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .booking-request-card p,
        .accepted-booking-card p {
            margin: 5px 0;
            color: #555;
        }

        .booking-request-card p strong,
        .accepted-booking-card p strong {
            color: #333;
        }

        .booking-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-accept {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .btn-accept:hover {
            background-color: #218838;
        }

        .btn-call-user {
            background-color: #ffc107;
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            text-decoration: none; /* For the <a> tag */
            display: inline-block; /* To allow padding */
        }
        .btn-call-user:hover {
            background-color: #e0a800;
        }

        .no-bookings {
            text-align: center;
            padding: 30px;
            font-size: 1.1em;
            color: #666;
        }

        .logout-btn {
            background-color: #dc3545;
            margin-top: 20px;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: capitalize;
            font-size: 0.9em;
            display: inline-block;
            margin-left: 10px;
        }
        .status-badge.pending { background-color: #ffc107; color: #333; }
        .status-badge.confirmed { background-color: #28a745; color: white; }
        .status-badge.completed { background-color: #6c757d; color: white; }
        .status-badge.cancelled { background-color: #dc3545; color: white; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .driver-dashboard-container {
                margin: 20px;
                padding: 15px;
            }
            .booking-actions {
                flex-direction: column;
                gap: 10px;
            }
            .btn-accept, .btn-call-user {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container driver-dashboard-container">
        <h2>Welcome, Driver <?php echo htmlspecialchars($_SESSION['driver_name']); ?>!</h2>
        <p>Manage your booking requests and view your accepted jobs here.</p>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h3 class="section-title">New Booking Requests (Pending)</h3>
        <?php if (!empty($pending_bookings)): ?>
            <?php foreach ($pending_bookings as $booking): ?>
                <div class="booking-request-card">
                    <h3>Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> for <?php echo htmlspecialchars($booking['booking_date']); ?></h3>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    <?php if (!empty($booking['message'])): ?>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                    <?php endif; ?>
                    <div class="booking-actions">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <button type="submit" name="accept_booking" class="btn-accept">Accept Booking</button>
                        </form>
                        <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" class="btn-call-user" style="background-color: #17a2b8;">✉️ Email User</a>
                        </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-bookings">No new pending booking requests at the moment.</p>
        <?php endif; ?>

        <h3 class="section-title">Your Accepted Bookings</h3>
        <?php if (!empty($accepted_bookings)): ?>
            <?php foreach ($accepted_bookings as $booking): ?>
                <div class="accepted-booking-card">
                    <h3>
                        Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?> on <?php echo htmlspecialchars($booking['booking_date']); ?>
                        <span class="status-badge <?php echo htmlspecialchars($booking['status']); ?>"><?php echo htmlspecialchars($booking['status']); ?></span>
                    </h3>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                    <p><strong>User Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                    <?php if (!empty($booking['message'])): ?>
                        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                    <?php endif; ?>
                     <div class="booking-actions">
                        <a href="mailto:<?php echo htmlspecialchars($booking['user_email']); ?>" class="btn-call-user" style="background-color: #17a2b8;">✉️ Email User</a>
                        </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-bookings">You have no accepted or past bookings yet.</p>
        <?php endif; ?>

        <p>
            <a href="logout.php" class="btn logout-btn">Logout</a>
        </p>
    </div>
</body>
</html>