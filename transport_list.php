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
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for transport list page */
        .transport-list-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 25px;
            background-color: #fcfcfc;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: left;
        }

        .filters-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1 1 200px;
            min-width: 180px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }

        .filters-section select,
        .filters-section button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .filters-section button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .filters-section button:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .driver-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .driver-card {
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Animation on hover */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Pushes buttons to the bottom */
        }

        .driver-card:hover {
            transform: translateY(-5px); /* Subtle lift */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .driver-card h3 {
            color: #28a745;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.6em;
        }

        .driver-card p {
            margin: 5px 0;
            color: #555;
            font-size: 0.95em;
        }

        .driver-card .details span {
            font-weight: bold;
            color: #333;
        }

        .driver-card .charge {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .card-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .card-actions .btn {
            flex: 1; /* Make buttons expand to fill space */
            min-width: 120px; /* Ensure minimum width */
            padding: 10px 15px;
            font-size: 0.95em;
            text-align: center;
        }

        .btn-call {
            background-color: #ffc107; /* Yellow for call */
            color: #333;
        }

        .btn-call:hover {
            background-color: #e0a800;
        }

        .btn-book {
            background-color: #28a745; /* Green for book */
        }

        .btn-book:hover {
            background-color: #218838;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            font-size: 1.1em;
            color: #666;
        }

        .btn-back-dashboard {
            display: inline-block;
            margin-top: 20px;
            background-color:rgb(6, 226, 17);
            height:20px;
            width:auto;
            padding:10px;
             border-radius: 10px;
             color:white;
        }
        a:link {
  text-decoration: none;
}

        .btn-back-dashboard:hover {
            background-color:rgb(4, 102, 4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .transport-list-container {
                margin: 20px;
                padding: 15px;
            }
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                min-width: unset;
            }
            .driver-cards-grid {
                grid-template-columns: 1fr; /* Single column for small screens */
            }
            .card-actions .btn {
                width: 100%; /* Full width buttons on small screens */
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
                            <p class="details">Vehicle: <span><?php echo htmlspecialchars($driver['vehicle_type']); ?></span></p>
                            <p class="details">Location: <span><?php echo htmlspecialchars($driver['location']); ?>, <?php echo htmlspecialchars($driver['district']); ?></span></p>
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

        <p>
            <a href="user_dashboard.php" class="btn btn-back-dashboard">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-back-dashboard" style="background-color: #dc3545;">Logout</a>
        </p>
    </div>
</body>
</html>