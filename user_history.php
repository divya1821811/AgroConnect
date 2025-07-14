<?php
// Start the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$bookings = [];
$error_message = '';

// SQL to fetch bookings for the current user, joining with drivers table
$sql = "SELECT b.id AS booking_id, b.booking_date, b.message, b.status,
               d.name AS driver_name, d.phone_number AS driver_phone,
               d.vehicle_type, d.location AS driver_location, d.district AS driver_district, d.charge
        FROM bookings b
        JOIN drivers d ON b.driver_id = d.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.created_at DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }
    } else {
        $error_message = 'ERROR: Could not fetch booking history. ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = 'ERROR: Could not prepare query. ' . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booking History - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for booking history page */
        .history-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 25px;
            background-color: #fcfcfc;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: left;
        }

        .booking-card {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .booking-card h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.6em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-card .status {
            font-size: 0.9em;
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status.pending { background-color: #ffc107; color: #333; }
        .status.confirmed { background-color: #28a745; }
        .status.completed { background-color: #6c757d; }
        .status.cancelled { background-color: #dc3545; }

        .booking-card p {
            margin: 5px 0;
            color: #555;
            line-height: 1.5;
        }

        .booking-card p strong {
            color: #333;
        }

        .no-bookings {
            text-align: center;
            padding: 30px;
            font-size: 1.1em;
            color: #666;
        }

        .btn-back-dashboard {
            display: inline-block;
            margin-top: 20px;
            background-color: #6c757d;
        }

        .btn-back-dashboard:hover {
            background-color: #5a6268;
        }

        .call-driver-btn {
            background-color: #ffc107;
            color: #333;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            margin-left: 15px;
        }
        .call-driver-btn:hover {
            background-color: #e0a800;
        }

        /* Responsive adjustments for booking cards */
        @media (max-width: 600px) {
            .history-container {
                margin: 15px;
                padding: 15px;
            }
            .booking-card h3 {
                flex-direction: column;
                align-items: flex-start;
            }
            .booking-card .status {
                margin-top: 10px;
            }
            .call-driver-btn {
                display: block;
                width: calc(100% - 30px); /* Adjust for padding */
                margin-left: 0;
                margin-top: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container history-container">
        <h2>My Booking History</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <h3>
                        Booking for <?php echo htmlspecialchars($booking['booking_date']); ?>
                        <span class="status <?php echo htmlspecialchars($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                    </h3>
                    <p><strong>Driver:</strong> <?php echo htmlspecialchars($booking['driver_name']); ?></p>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['driver_location']); ?>, <?php echo htmlspecialchars($booking['driver_district']); ?></p>
                    <p><strong>Charge:</strong> ₹<?php echo htmlspecialchars(number_format($booking['charge'], 2)); ?></p>
                    <p><strong>Driver Phone:</strong> <?php echo htmlspecialchars($booking['driver_phone']); ?>
                        <a href="tel:<?php echo htmlspecialchars($booking['driver_phone']); ?>" class="call-driver-btn">📞 Call Driver</a>
                    </p>
                    <?php if (!empty($booking['message'])): ?>
                        <p><strong>Your Message:</strong> <?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-bookings">You have no booking history yet. <a href="transport_list.php">Book a vehicle now!</a></p>
        <?php endif; ?>

        <p>
            <a href="user_dashboard.php" class="btn btn-back-dashboard">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-back-dashboard" style="background-color: #dc3545;">Logout</a>
        </p>
    </div>
   
</span>
</body>
</html>