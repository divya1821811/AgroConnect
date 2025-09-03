<?php
// Start the session ONLY ONCE at the very top of the main entry file (user_dashboard.php)
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file ONLY ONCE
require_once 'config.php';

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// --- Fetch Dashboard Statistics ---
$total_vehicles_platform = 0; // Total vehicles registered on the platform
$total_bookings_platform = 0; // Total confirmed/completed bookings on the platform

// Count total registered vehicles (drivers) on the platform
$sql_vehicles_count_platform = "SELECT COUNT(id) AS total_vehicles FROM drivers";
if ($result_vehicles_platform = mysqli_query($conn, $sql_vehicles_count_platform)) {
    $row_vehicles_platform = mysqli_fetch_assoc($result_vehicles_platform);
    $total_vehicles_platform = $row_vehicles_platform['total_vehicles'];
    mysqli_free_result($result_vehicles_platform); // Free result set
} else {
    // Handle error or log it
    error_log("Error fetching total vehicles: " . mysqli_error($conn));
}

// Count total confirmed/completed bookings (for the whole platform)
$sql_bookings_count_platform = "SELECT COUNT(id) AS total_bookings FROM bookings WHERE status = 'confirmed' OR status = 'completed'";
if ($result_bookings_platform = mysqli_query($conn, $sql_bookings_count_platform)) {
    $row_bookings_platform = mysqli_fetch_assoc($result_bookings_platform);
    $total_bookings_platform = $row_bookings_platform['total_bookings'];
    mysqli_free_result($result_bookings_platform); // Free result set
} else {
    error_log("Error fetching total bookings: " . mysqli_error($conn));
}


// --- User-Specific Dashboard Statistics ---
$my_total_bookings = 0; // Bookings made by the current user

// Count total bookings made by the current user
$sql_my_bookings_count = "SELECT COUNT(id) AS my_bookings_count FROM bookings WHERE user_id = ?";
if ($stmt_my_bookings_count = mysqli_prepare($conn, $sql_my_bookings_count)) {
    mysqli_stmt_bind_param($stmt_my_bookings_count, 'i', $user_id);
    if (mysqli_stmt_execute($stmt_my_bookings_count)) {
        $result_my_bookings_count = mysqli_stmt_get_result($stmt_my_bookings_count);
        $row_my_bookings_count = mysqli_fetch_assoc($result_my_bookings_count);
        $my_total_bookings = $row_my_bookings_count['my_bookings_count'];
        mysqli_free_result($result_my_bookings_count);
    } else {
        error_log("Error executing my bookings count query: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt_my_bookings_count);
} else {
    error_log("Error preparing my bookings count query: " . mysqli_error($conn));
}

// Count available vehicles (drivers) for the user to book.
// For simplicity, this currently uses the total platform vehicles.
// In a real application, this might be filtered by location, type, etc.
$available_vehicles_for_me = $total_vehicles_platform;


// --- Fetch Data for Dashboard Lists (Sample Data - Replace with actual DB fetches for real data) ---
// Note: These arrays contain sample data. For a live application, you'd fetch this from your database.

$marketPrices = [
    ['product_name' => 'Wheat', 'price' => '2100', 'price_change' => '+2.5%', 'last_updated' => '2023-08-15 10:00:00'],
    ['product_name' => 'Corn', 'price' => '1950', 'price_change' => '-1.2%', 'last_updated' => '2023-08-15 09:30:00'],
    ['product_name' => 'Soybeans', 'price' => '4300', 'price_change' => '+3.8%', 'last_updated' => '2023-08-14 17:00:00'],
    ['product_name' => 'Rice', 'price' => '3200', 'price_change' => '+0.7%', 'last_updated' => '2023-08-14 16:00:00']
];

$transportOptions = [
    ['type' => 'Open Truck', 'availability' => '12 available', 'rate' => '₹12/km'],
    ['type' => 'Refrigerated', 'availability' => '5 available', 'rate' => '₹21/km'],
    ['type' => 'Flatbed', 'availability' => '8 available', 'rate' => '₹15/km']
];

// DO NOT close the connection here. It needs to be open for included files.
// PHP will automatically close the connection when the script finishes.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-green: #28a745; /* Adjusted to match your existing button green */
            --secondary-green: #218838; /* Darker green on hover */
            --light-green: #e9f5e9;
            --primary-blue: #007bff; /* Standard Blue */
            --secondary-blue: #0056b3; /* Darker Blue */
            --light-blue: #e6f7ff;
            --white: #ffffff;
            --dark: #333333;
            --light-gray: #f0f5f0; /* Body background from previous style */
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
            display: flex; /* For dashboard-wrapper */
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            height: 100vh; /* Full viewport height */
            overflow: hidden; /* Prevent body scroll, content areas will scroll */
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-green);
            color: var(--white);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            transition: width 0.3s ease;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            text-align: center; /* Center header text */
        }

        .sidebar-header h3 {
            font-size: 1.8em; /* Adjusted to match your original plan */
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 5px; /* Added margin */
        }

        .sidebar-header p {
            font-size: 0.9em; /* Smaller role text */
            opacity: 0.9;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
            font-size: 1.05em; /* Slightly adjusted font size */
            border-radius: 0 25px 25px 0; /* Rounded right edge */
        }

        .sidebar-menu a:hover {
            background-color: var(--secondary-green);
            transform: translateX(5px);
        }

        /* The 'active' class logic removed as the dashboard is no longer including dynamic content */
        /* .sidebar-menu a.active {
            background-color: var(--primary-blue);
            box-shadow: inset 3px 0 0 rgba(255, 255, 255, 0.5);
        } */

        .sidebar-menu i {
            margin-right: 15px; /* Increased margin */
            width: 25px; /* Fixed width for icons */
            text-align: center;
            font-size: 1.3em;
        }

        .sidebar-footer {
            margin-top: auto; /* Pushes logout to bottom */
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .logout-btn {
            background-color: var(--danger); /* Red for logout */
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            display: block; /* Full width */
            transition: var(--transition);
        }
        .logout-btn:hover {
            background-color: #c82333; /* Darker red on hover */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }


        /* Main Content Styles */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: var(--white);
            border-radius: 12px;
            margin: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            max-height: calc(100vh - 40px); /* Adjust height to fit wrapper */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .header h1 {
            color: var(--dark);
            font-size: 2.2em; /* Adjusted font size */
            margin: 0; /* Reset margin */
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        /* Removed .user-profile img as per the profile page design (just name/role) */

        .user-profile .name {
            font-weight: 600;
            font-size: 1.1em;
            color: var(--primary-green);
        }

        .user-profile .role {
            font-size: 0.85em;
            color: var(--dark-gray);
        }

        /* Dashboard Cards for Stats (total vehicles, total bookings) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Adjusted minmax */
            gap: 20px; /* Reduced gap */
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--light-green); /* Use light green for stats background */
            border: 1px solid rgba(40, 167, 69, 0.1); /* Subtle border matching green */
            border-radius: var(--card-radius);
            padding: 20px; /* Reduced padding */
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .icon {
            font-size: 2.5em; /* Reduced icon size */
            color: var(--primary-green); /* Use primary green for icons */
            margin-bottom: 10px; /* Reduced margin */
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .stat-card .value {
            font-size: 2em; /* Reduced value font size */
            font-weight: bold;
            color: var(--primary-blue); /* Use primary blue for values */
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 1em; /* Reduced label font size */
            color: var(--dark-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dashboard Sections (Recent Bookings, Market Prices, Transport Availability) */
        .dashboard-section-grid {
            display: grid;
            grid-template-columns: 1fr; /* Default to single column */
            gap: 20px;
            margin-top: 30px;
        }

        /* For larger screens, split into two columns if desired for Market Prices / Transport Availability */
        @media (min-width: 992px) {
            .dashboard-section-grid.two-columns {
                grid-template-columns: 1fr 1fr; /* Example: Two columns for side-by-side tables */
            }
            .dashboard-section-grid.main-and-side {
                 grid-template-columns: 2fr 1fr; /* For main table and quick actions */
            }
        }


        .section-card {
            background-color: var(--white);
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin:auto;
        }

        .section-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .section-title {
            font-size: 1.4em; /* Adjusted font size */
            font-weight: 600;
            margin-bottom: 15px; /* Reduced margin */
            color: var(--primary-green);
            display: flex;
            align-items: center;
            border-bottom: 1px dashed var(--medium-gray); /* Subtle border */
            padding-bottom: 10px;
        }

        .section-title i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 15px; /* Space between title and table */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px; /* Ensure table is not too squished on small screens */
        }

        th, td {
            padding: 10px 12px; /* Reduced padding */
            text-align: left;
            border-bottom: 1px solid var(--medium-gray);
            font-size: 0.95em; /* Slightly smaller font */
        }

        th {
            background-color: var(--light-green);
            color: var(--primary-green);
            font-weight: 600;
        }

        tr:hover {
            background-color: var(--light-gray);
        }

        .status {
            padding: 4px 8px; /* Reduced padding */
            border-radius: 15px; /* Slightly less rounded */
            font-size: 0.8em; /* Smaller font */
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .status.completed {
            background-color: rgba(40, 167, 69, 0.15); /* Stronger tint */
            color: var(--success);
        }

        .status.pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning);
        }

        .status.cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
        }

        .card-change {
            font-size: 0.9em; /* Smaller change text */
            display: flex;
            align-items: center;
            gap: 5px; /* Space between icon and text */
        }

        .card-change.positive {
            color: var(--success);
        }

        .card-change.negative {
            color: var(--danger);
        }
        .card-change i {
            font-size: 0.8em; /* Smaller icon in change text */
        }


        .btn {
            padding: 8px 15px; /* Reduced padding */
            border-radius: 6px; /* Slightly less rounded */
            font-size: 0.9em; /* Reduced font size */
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            border: none;
        }

        .btn-sm {
            padding: 4px 8px; /* Even smaller for sm */
            font-size: 0.8em;
        }

        .btn-primary {
            background-color: var(--primary-green);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--secondary-green);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-green);
            color: var(--primary-green);
        }

        .btn-outline:hover {
            background-color: var(--primary-green);
            color: var(--white);
        }

        .view-all {
            display: block;
            text-align: right;
            margin-top: 10px; /* Reduced margin */
            color: var(--primary-blue); /* Changed to blue for consistency with links */
            font-size: 0.9em;
            font-weight: 500;
            text-decoration: none;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); /* Adjusted for smaller cards */
            gap: 25px;
        }

        .action-card {
            background-color: var(--white);
            border-radius: var(--card-radius);
            padding: 15px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .action-icon {
            width: 45px; /* Smaller icon container */
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: var(--white);
            font-size: 1.5em; /* Smaller icon font size */
        }

        .action-icon.market { background-color: var(--success); }
        .action-icon.transport { background-color: var(--info); }
        .action-icon.history { background-color: #8d6e63; /* Primary Brown */ }
        .action-icon.profile { background-color: #ffc107; color: var(--dark); /* Warning color */ }

        .action-title {
            font-size: 0.9em; /* Smaller title */
            font-weight: 600;
            color: var(--dark);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                flex-direction: column;
                height: auto;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px 0;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                border-radius: 0;
            }
            .sidebar-header {
                margin-bottom: 15px;
            }
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
            }
            .sidebar-menu li {
                margin-bottom: 0;
                width: auto;
            }
            .sidebar-menu a {
                border-radius: 8px;
                padding: 8px 10px;
                flex-direction: column;
                font-size: 0.8em;
                text-align: center;
                gap: 3px;
            }
            .sidebar-menu i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1.1em;
            }
            .sidebar-footer {
                margin-top: 15px;
                padding: 10px 15px;
            }
            .logout-btn {
                padding: 10px 15px;
            }
            .main-content {
                margin: 15px;
                padding: 20px;
                max-height: none;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            .header h1 {
                font-size: 1.8em;
            }
            .user-profile {
                margin-top: 10px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .stat-card {
                padding: 15px;
            }
            .stat-card .icon {
                font-size: 2em;
            }
            .stat-card .value {
                font-size: 1.8em;
            }
            .stat-card .label {
                font-size: 0.9em;
            }
            .dashboard-section-grid {
                grid-template-columns: 1fr;
            }
            .section-card {
                padding: 15px;
            }
            .section-title {
                font-size: 1.2em;
            }
            table {
                min-width: unset; /* Allow table to shrink on very small screens */
            }
        }

        @media (max-width: 576px) {
            .main-content {
                margin: 10px;
                padding: 15px;
            }
            .header h1 {
                font-size: 1.6em;
            }
            .quick-actions-grid {
                grid-template-columns: 1fr 1fr; /* 2 columns on small phones */
            }
            .action-card {
                padding: 10px;
            }
            .action-icon {
                width: 40px;
                height: 40px;
                font-size: 1.3em;
            }
            .action-title {
                font-size: 0.8em;
            }
            th, td {
                padding: 8px 10px;
                font-size: 0.9em;
            }
            .status {
                font-size: 0.75em;
                padding: 3px 6px;
            }
            .btn {
                font-size: 0.8em;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>AgroConnect</h3>
                <p>User Dashboard</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="market_prices.php"><i class="fas fa-chart-line"></i> Market Prices</a></li>
                <li><a href="transport_list.php"><i class="fas fa-truck"></i> Find Transport</a></li>
                <li><a href="user_history.php"><i class="fas fa-history"></i> My Bookings</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <div class="user-profile">
                    <div>
                        <div class="name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="role">User</div>
                    </div>
                </div>
            </div>

            <p class="welcome-message">Here's a quick overview of your AgroConnect activity and the platform's reach.</p>

            <!-- Dashboard Statistics Cards (Platform-wide) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="value"><?php echo $total_vehicles_platform; ?></div>
                    <div class="label">Registered Vehicles (Platform)</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="value"><?php echo $total_bookings_platform; ?></div>
                    <div class="label">Confirmed Bookings (Platform)</div>
                </div>
            </div>

            <!-- User-Specific Statistics Cards -->
            <div class="stats-grid" style="margin-top: 20px;">
                <div class="stat-card">
                    <div class="icon" style="color: var(--primary-blue);"><i class="fas fa-truck-ramp-box"></i></div>
                    <div class="value"><?php echo $available_vehicles_for_me; ?></div>
                    <div class="label">Vehicles Available</div>
                </div>
                <div class="stat-card">
                    <div class="icon" style="color: var(--primary-blue);"><i class="fas fa-calendar-check"></i></div>
                    <div class="value"><?php echo $my_total_bookings; ?></div>
                    <div class="label">My Total Bookings</div>
                </div>
            </div>

            <!-- Main Dashboard Sections with Tables and Quick Actions -->
            <div class="dashboard-section-grid main-and-side">
                <!-- Left Column (now holds tables) -->
                <div>
                    <!-- Current Market Prices
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-shopping-basket"></i> Current Market Prices
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Change</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($marketPrices)): ?>
                                        <?php foreach ($marketPrices as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td>₹<?php echo htmlspecialchars(number_format($item['price'], 2)); ?>/quintal</td>
                                            <td class="price-change <?php echo (strpos($item['price_change'], '+') !== false) ? 'positive' : 'negative'; ?>">
                                                <?php echo (strpos($item['price_change'], '+') !== false) ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>'; ?>
                                                <?php echo htmlspecialchars($item['price_change']); ?>
                                            </td>
                                            <td><?php echo date('M j, Y, g:i a', strtotime($item['last_updated'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4">No market prices available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="market_prices.php" class="view-all">View All Prices →</a>
                    </div> -->

                    <!-- Transport Availability 
                    <div class="section-card" style="margin-top: 20px;">
                        <div class="section-title">
                            <i class="fas fa-truck-moving"></i> Transport Availability (Sample)
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Availability</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transportOptions)): ?>
                                        <?php foreach ($transportOptions as $option): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($option['type']); ?></td>
                                            <td><?php echo htmlspecialchars($option['availability']); ?></td>
                                            <td><?php echo htmlspecialchars($option['rate']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3">No transport options available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="transport_list.php" class="view-all">Book Transport →</a>
                    </div>
                </div>-->

                <!-- Right Column: Quick Actions and potentially other smaller widgets -->
                <div>
                    <!-- Quick Actions -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="quick-actions-grid">
                            <div class="action-card" onclick="window.location.href='market_prices.php'">
                                <div class="action-icon market">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="action-title">Market Prices</div>
                            </div>
                            <div class="action-card" onclick="window.location.href='transport_list.php'">
                                <div class="action-icon transport">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="action-title">Book Transport</div>
                            </div>
                            <div class="action-card" onclick="window.location.href='user_history.php'">
                                <div class="action-icon history">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="action-title">Booking History</div>
                            </div>
                            <div class="action-card" onclick="window.location.href='profile.php'">
                                <div class="action-icon profile">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="action-title">My Profile</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
