<?php
// Start the session
session_start();

// Check if the user is logged in, if not then redirect to login page
// Only users (not drivers) should view transport services
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables for filtering
$selected_district = isset($_GET['district']) ? $_GET['district'] : '';
$selected_vehicle_type = isset($_GET['vehicle_type']) ? $_GET['vehicle_type'] : '';

// Fetch distinct districts and vehicle types for filter dropdowns
$districts = [];
$vehicle_types = [];

$sql_districts = "SELECT DISTINCT district FROM drivers ORDER BY district ASC";
$result_districts = mysqli_query($conn, $sql_districts);
if ($result_districts) {
    while ($row = mysqli_fetch_assoc($result_districts)) {
        $districts[] = $row['district'];
    }
}

$sql_vehicle_types = "SELECT DISTINCT vehicle_type FROM drivers ORDER BY vehicle_type ASC";
$result_vehicle_types = mysqli_query($conn, $sql_vehicle_types);
if ($result_vehicle_types) {
    while ($row = mysqli_fetch_assoc($result_vehicle_types)) {
        $vehicle_types[] = $row['vehicle_type'];
    }
}

// Build the SQL query for drivers
$sql = "SELECT id, name, age, phone_number, vehicle_type, location, district, charge FROM drivers WHERE 1=1";
$params = [];
$types = '';

if (!empty($selected_district)) {
    $sql .= " AND district = ?";
    $types .= 's';
    $params[] = $selected_district;
}
if (!empty($selected_vehicle_type)) {
    $sql .= " AND vehicle_type = ?";
    $types .= 's';
    $params[] = $selected_vehicle_type;
}

$sql .= " ORDER BY name ASC"; // Order drivers by name

$drivers = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $drivers[] = $row;
        }
    } else {
        echo "ERROR: Could not execute query: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    echo "ERROR: Could not prepare query: " . mysqli_error($conn);
}

// Close connection (only if not doing more queries)
// mysqli_close($conn); // Keeping connection open for now if user_history.php might need it on redirect
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Services - AgroConnect</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General page styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container.transport-list-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: left;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 30px;
            font-weight: 700;
        }

        /* Filter section */
        .filters-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background-color: #ecf0f1;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            align-items: flex-end;
        }

        .filter-group {
            flex: 1 1 200px;
            min-width: 180px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }

        .filters-section select,
        .filters-section .btn {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .filters-section select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 18px;
            cursor: pointer;
        }

        .filters-section .btn {
            background-color: #27ae60;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            font-weight: bold;
            text-transform: uppercase;
        }

        .filters-section .btn:hover {
            background-color: #229954;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .filters-section .btn:active {
            transform: translateY(0);
        }

        /* Driver cards */
        .driver-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .driver-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .driver-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .driver-card h3 {
            color: #16a085;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.8em;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .driver-card p {
            margin: 8px 0;
            color: #555;
            font-size: 1em;
        }

        .driver-card .details span {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .vehicle-icon {
             font-size: 1.2em;
             margin-right: 8px;
             color: #3498db;
        }

        .location-icon {
            font-size: 1.2em;
            margin-right: 8px;
            color: #e67e22;
        }
        
        .charge {
            font-size: 1.4em;
            font-weight: bold;
            color: #3498db;
            margin-top: 15px;
            margin-bottom: 20px;
            border-top: 2px solid #ecf0f1;
            padding-top: 15px;
        }

        .card-actions {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .card-actions .btn {
            flex: 1;
            min-width: 120px;
            padding: 12px 18px;
            font-size: 0.95em;
            text-align: center;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-call {
            background-color: #e67e22;
            color: white;
        }

        .btn-call:hover {
            background-color: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-book {
            background-color: #2ecc71;
            color: white;
        }

        .btn-book:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-results {
            text-align: center;
            padding: 50px;
            font-size: 1.2em;
            color: #95a5a6;
            grid-column: 1 / -1;
            background-color: #ecf0f1;
            border-radius: 8px;
        }
        a{
            text-decoration:none;
        }
        .back-buttons-container {
            text-align: center;
            margin-top: 30px;
        }

        .btn-back-dashboard {
            background-color: #34495e;
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            margin: 0 10px;
        }

        .btn-back-dashboard:hover {
            background-color: #2c3e50;
        }
        
        .btn-logout {
            background-color: #e74c3c;
        }

        .btn-logout:hover {
            background-color: #c0392b;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container.transport-list-container {
                margin: 20px;
                padding: 20px;
            }
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                min-width: unset;
            }
            .driver-cards-grid {
                grid-template-columns: 1fr;
            }
            .card-actions .btn {
                width: 100%;
                min-width: unset;
            }
        }
    </style>
</head>
<body>
    <div class="container transport-list-container">
        <h2>Available Transport Services</h2>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="filters-section">
            <div class="filter-group">
                <label for="district">Filter by District:</label>
                <select name="district" id="district">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?php echo htmlspecialchars($district); ?>" <?php echo ($selected_district == $district) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($district); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="vehicle_type">Filter by Vehicle Type:</label>
                <select name="vehicle_type" id="vehicle_type">
                    <option value="">All Vehicle Types</option>
                    <?php foreach ($vehicle_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($selected_vehicle_type == $type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="submit" class="btn">Apply Filters</button>
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="button" class="btn" onclick="window.location.href='transport_list.php'">Clear Filters</button>
            </div>
        </form>

        <div class="driver-cards-grid">
            <?php if (!empty($drivers)): ?>
                <?php foreach ($drivers as $driver): ?>
                    <div class="driver-card">
                        <div>
                            <h3><?php echo htmlspecialchars($driver['name']); ?></h3>
                            <p class="details"><i class="fas fa-truck vehicle-icon"></i>Vehicle: <span><?php echo htmlspecialchars($driver['vehicle_type']); ?></span></p>
                            <p class="details"><i class="fas fa-map-marker-alt location-icon"></i>Location: <span><?php echo htmlspecialchars($driver['location']); ?>, <?php echo htmlspecialchars($driver['district']); ?></span></p>
                            <p class="details">Age: <span><?php echo htmlspecialchars($driver['age']); ?> years</span></p>
                            <p class="charge">Service Charge: ₹<?php echo htmlspecialchars(number_format($driver['charge'], 2)); ?></p>
                        </div>
                        <div class="card-actions">
                            <a href="tel:<?php echo htmlspecialchars($driver['phone_number']); ?>" class="btn btn-call">📞 Call Driver</a>
                            <a href="book_vehicle.php?driver_id=<?php echo htmlspecialchars($driver['id']); ?>" class="btn btn-book">✅ Book Vehicle</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    <p>No transport services found matching your criteria. Try different filters.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-buttons-container">
            <a href="user_dashboard.php" class="btn btn-back-dashboard">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-back-dashboard btn-logout">Logout</a>
        </div>
    </div>
</body>
</html>
