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
            --light-gray: #f8f9fa; /* Lighter background for better contrast */
            --medium-gray: #e0e0e0;
            --dark-gray: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
            --card-radius: 12px;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-green) 0%, #1e7e34 100%);
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
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar.collapsed .sidebar-header h3,
        .sidebar.collapsed .sidebar-header p,
        .sidebar.collapsed .sidebar-menu span,
        .sidebar.collapsed .logout-btn span {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 12px 0;
            border-radius: 0;
        }

        .sidebar.collapsed .sidebar-menu i {
            margin-right: 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }

        .sidebar-header h3 {
            font-size: 1.8em;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 5px;
            transition: opacity 0.3s ease;
        }

        .sidebar-header p {
            font-size: 0.9em;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }

        .toggle-sidebar {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(180deg);
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
            font-size: 1.05em;
            border-radius: 0 25px 25px 0;
            position: relative;
            overflow: hidden;
        }

        .sidebar-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .sidebar-menu a:hover::before {
            left: 100%;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: inset 3px 0 0 rgba(255, 255, 255, 0.8);
        }

        .sidebar-menu i {
            margin-right: 15px;
            width: 25px;
            text-align: center;
            font-size: 1.3em;
            transition: margin 0.3s ease;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .logout-btn {
            background-color: rgba(220, 53, 69, 0.8);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            gap: 10px;
        }

        .logout-btn:hover {
            background-color: var(--danger);
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
            max-height: calc(100vh - 40px);
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
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
            font-size: 2.2em;
            margin: 0;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 15px;
            background: var(--light-green);
            border-radius: 8px;
            transition: var(--transition);
        }

        .user-profile:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .user-profile .name {
            font-weight: 600;
            font-size: 1.1em;
            color: var(--primary-green);
        }

        .user-profile .role {
            font-size: 0.85em;
            color: var(--dark-gray);
        }

        .welcome-message {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--light-blue) 100%);
            padding: 15px 20px;
            border-radius: var(--card-radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-green);
            box-shadow: var(--shadow);
        }

        /* Dashboard Cards for Stats (total vehicles, total bookings) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--white) 0%, #f8f9fa 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: var(--card-radius);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-green), var(--primary-blue));
        }

        .stat-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .icon {
            font-size: 2.8em;
            color: var(--primary-green);
            margin-bottom: 15px;
            transition: var(--transition);
        }

        .stat-card:hover .icon {
            transform: scale(1.1);
            color: var(--primary-blue);
        }

        .stat-card .value {
            font-size: 2.2em;
            font-weight: bold;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 1em;
            color: var(--dark-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dashboard Sections (Recent Bookings, Market Prices, Transport Availability) */
        .dashboard-section-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        @media (min-width: 992px) {
            .dashboard-section-grid.two-columns {
                grid-template-columns: 1fr 1fr;
            }
            .dashboard-section-grid.main-and-side {
                 grid-template-columns: 2fr 1fr;
            }
        }

        .section-card {
            background-color: var(--white);
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .section-title {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-green);
            display: flex;
            align-items: center;
            border-bottom: 1px dashed var(--medium-gray);
            padding-bottom: 10px;
        }

        .section-title i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--medium-gray);
            font-size: 0.95em;
        }

        th {
            background-color: var(--light-green);
            color: var(--primary-green);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: rgba(40, 167, 69, 0.05);
            transform: scale(1.01);
        }

        .price-change {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .price-change.positive {
            color: var(--success);
        }

        .price-change.negative {
            color: var(--danger);
        }

        .view-all {
            display: block;
            text-align: right;
            margin-top: 15px;
            color: var(--primary-blue);
            font-size: 0.9em;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            padding: 5px 0;
        }

        .view-all:hover {
            text-decoration: underline;
            color: var(--secondary-blue);
            transform: translateX(5px);
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 20px;
        }

        .action-card {
            background-color: var(--white);
            border-radius: var(--card-radius);
            padding: 20px 15px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-card:hover {
            transform: translateY(-7px) scale(1.03);
            box-shadow: var(--shadow-hover);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--white);
            font-size: 1.8em;
            transition: var(--transition);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .action-card:hover .action-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .action-icon.market { 
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
        }
        .action-icon.transport { 
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
        }
        .action-icon.history { 
            background: linear-gradient(135deg, #8d6e63 0%, #6d4c41 100%);
        }
        .action-icon.profile { 
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: var(--dark);
        }

        .action-title {
            font-size: 0.95em;
            font-weight: 600;
            color: var(--dark);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px 0;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                border-radius: 0;
            }
            .sidebar.collapsed {
                width: 100%;
            }
            .sidebar.collapsed .sidebar-header h3,
            .sidebar.collapsed .sidebar-header p,
            .sidebar.collapsed .sidebar-menu span,
            .sidebar.collapsed .logout-btn span {
                display: block;
            }
            .sidebar.collapsed .sidebar-menu a {
                justify-content: flex-start;
                padding: 12px 20px;
                border-radius: 0 25px 25px 0;
            }
            .sidebar.collapsed .sidebar-menu i {
                margin-right: 15px;
            }
            .sidebar-header {
                margin-bottom: 15px;
            }
            .toggle-sidebar {
                display: none;
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
                padding: 20px;
            }
            .stat-card .icon {
                font-size: 2.2em;
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
                padding: 20px;
            }
            .section-title {
                font-size: 1.2em;
            }
            table {
                min-width: unset;
            }
            .quick-actions-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
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
                grid-template-columns: 1fr 1fr;
            }
            .action-card {
                padding: 15px 10px;
            }
            .action-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5em;
            }
            .action-title {
                font-size: 0.85em;
            }
            th, td {
                padding: 10px 12px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h3>AgroConnect</h3>
                <p>User Dashboard</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li><a href="market_prices.php"><i class="fas fa-chart-line"></i> <span>Market Prices</span></a></li>
                <li><a href="transport_list.php"><i class="fas fa-truck"></i> <span>Find Transport</span></a></li>
                <li><a href="user_history.php"><i class="fas fa-history"></i> <span>My Bookings</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content" id="mainContent">
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
                    <div class="icon"><i class="fas fa-truck-ramp-box"></i></div>
                    <div class="value"><?php echo $available_vehicles_for_me; ?></div>
                    <div class="label">Vehicles Available</div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="value"><?php echo $my_total_bookings; ?></div>
                    <div class="label">My Total Bookings</div>
                </div>
            </div>

            <!-- Main Dashboard Sections with Tables and Quick Actions -->
           
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

    <script>
        // Toggle sidebar collapse/expand
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Change icon based on state
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-bars');
            }
        });

        // Add subtle animation to stat cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in-up');
            });
        });
    </script>
</body>
</html>