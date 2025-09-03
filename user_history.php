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

        /* Specific styles for booking history page */
        .history-container {
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

        .history-container h2 {
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
        .history-container h2 i {
            font-size: 1.2em;
        }

        .booking-card {
            background-color: var(--light-green); /* Light green background for cards */
            border: 1px solid rgba(40, 167, 69, 0.1); /* Subtle green border */
            border-radius: var(--card-radius); /* Consistent border-radius */
            padding: 25px; /* Increased padding */
            margin-bottom: 20px;
            box-shadow: var(--shadow); /* Consistent shadow */
            transition: var(--transition); /* Consistent transition */
        }

        .booking-card:hover {
            transform: translateY(-5px); /* Consistent hover effect */
            box-shadow: var(--shadow-hover); /* Consistent hover shadow */
        }

        .booking-card h3 {
            color: var(--primary-blue); /* Themed blue for booking ID/date */
            margin-top: 0;
            margin-bottom: 15px; /* Increased margin */
            font-size: 1.8em; /* Slightly larger heading */
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }

        .booking-card .status {
            font-size: 0.95em; /* Slightly larger font */
            padding: 6px 12px; /* Adjusted padding */
            border-radius: 20px; /* More rounded pill shape */
            color: var(--white); /* White text for statuses */
            font-weight: bold;
            text-transform: capitalize;
            min-width: 90px; /* Ensure consistent width for statuses */
            text-align: center;
            margin-top: 5px; /* Space if wrapping */
        }

        .status.pending { background-color: var(--warning); color: var(--dark); } /* Yellow background, dark text */
        .status.confirmed { background-color: var(--success); }
        .status.completed { background-color: var(--dark-gray); } /* Dark gray for completed */
        .status.cancelled { background-color: var(--danger); }

        .booking-card p {
            margin: 8px 0; /* Adjusted margin */
            color: var(--dark); /* Consistent dark text */
            line-height: 1.6; /* Improved readability */
            font-size: 1.05em; /* Slightly larger text */
        }

        .booking-card p strong {
            color: var(--dark); /* Stronger dark color */
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
        .no-bookings a {
            color: var(--primary-blue); /* Consistent link color */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .no-bookings a:hover {
            text-decoration: underline;
            color: var(--secondary-blue);
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
            margin: 5px; /* Space between buttons */
        }

        .btn-back-dashboard {
            background-color: var(--dark-gray); /* Neutral gray */
            color: var(--white);
        }

        .btn-back-dashboard:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .logout-btn { /* Specific class for logout button */
            background-color: var(--danger); /* Red for logout */
            color: var(--white);
        }

        .logout-btn:hover {
            background-color: #c82333; /* Darker red */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .call-driver-btn {
            background-color: var(--info); /* Blue for call action */
            color: var(--white);
            padding: 8px 15px; /* Slightly smaller for inline action */
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            margin-left: 15px; /* Spacing from phone number */
            white-space: nowrap; /* Prevent breaking for "Call Driver" */
        }
        .call-driver-btn:hover {
            background-color: #117a8b;
        }

        .button-group {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 15px; /* Space between buttons */
            flex-wrap: wrap; /* Allow wrapping on small screens */
        }


        /* Alert messages (using themed colors) */
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


        /* Responsive adjustments for booking cards */
        @media (max-width: 768px) {
            .history-container {
                margin: 15px auto;
                padding: 20px;
            }
            .history-container h2 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }
            .booking-card {
                padding: 20px;
            }
            .booking-card h3 {
                flex-direction: column;
                align-items: flex-start;
                font-size: 1.5em;
            }
            .booking-card .status {
                margin-top: 8px;
                margin-left: 0;
            }
            .call-driver-btn {
                margin-left: 0;
                margin-top: 10px; /* Separate from phone number on smaller screens */
                display: block; /* Make button full width */
                width: 100%;
            }
            .button-group {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .btn {
                width: 100%; /* Full width buttons */
            }
        }

        @media (max-width: 480px) {
            .history-container {
                padding: 15px;
                margin: 10px auto;
            }
            .history-container h2 {
                font-size: 1.5em;
            }
            .booking-card {
                padding: 15px;
            }
            .booking-card h3 {
                font-size: 1.3em;
            }
            .booking-card p {
                font-size: 1em;
                margin: 6px 0;
            }
            .call-driver-btn {
                font-size: 0.85em;
                padding: 6px 12px;
            }
            .no-bookings {
                padding: 20px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container history-container">
        <h2><i class="fas fa-history"></i> My Booking History</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <h3>
                        Booking #<?php echo htmlspecialchars($booking['booking_id']); ?> - <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                        <span class="status <?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </span>
                    </h3>
                    <p><strong>Driver:</strong> <?php echo htmlspecialchars($booking['driver_name']); ?></p>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['driver_location']); ?>, <?php echo htmlspecialchars($booking['driver_district']); ?></p>
                    <p><strong>Charge:</strong> ₹<?php echo htmlspecialchars(number_format($booking['charge'], 2)); ?></p>
                    <p>
                        <strong>Driver Phone:</strong> <?php echo htmlspecialchars($booking['driver_phone']); ?>
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

        <div class="button-group">
            <a href="user_dashboard.php" class="btn btn-back-dashboard"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</body>
</html>
