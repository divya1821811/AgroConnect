<?php
// Start the session
session_start();

// Check if the user is logged in, if not then redirect to login page
// Only users (not drivers) should ideally view market prices
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables for filtering
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$selected_location = isset($_GET['location']) ? $_GET['location'] : '';
$search_item = isset($_GET['search_item']) ? $_GET['search_item'] : '';

// Fetch distinct categories and locations for filter dropdowns
$categories = [];
$locations = [];

$sql_categories = "SELECT DISTINCT category FROM market_prices ORDER BY category ASC";
$result_categories = mysqli_query($conn, $sql_categories);
if ($result_categories) {
    while ($row = mysqli_fetch_assoc($result_categories)) {
        $categories[] = $row['category'];
    }
}

$sql_locations = "SELECT DISTINCT location FROM market_prices ORDER BY location ASC";
$result_locations = mysqli_query($conn, $sql_locations);
if ($result_locations) {
    while ($row = mysqli_fetch_assoc($result_locations)) {
        $locations[] = $row['location'];
    }
}

// Build the SQL query for market prices
$sql = "SELECT id, category, item_name, price, unit, location, date FROM market_prices WHERE 1=1";
$params = [];
$types = '';

if (!empty($selected_category)) {
    $sql .= " AND category = ?";
    $types .= 's';
    $params[] = $selected_category;
}
if (!empty($selected_location)) {
    $sql .= " AND location = ?";
    $types .= 's';
    $params[] = $selected_location;
}
if (!empty($search_item)) {
    $sql .= " AND item_name LIKE ?";
    $types .= 's';
    $params[] = '%' . $search_item . '%';
}

$sql .= " ORDER BY date DESC, item_name ASC"; // Order by latest date, then item name

$market_prices = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $market_prices[] = $row;
        }
    } else {
        echo "ERROR: Could not execute query: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    echo "ERROR: Could not prepare query: " . mysqli_error($conn);
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Prices - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific styles for market prices page */
        .market-prices-container {
            max-width: 900px;
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
            align-items: flex-end; /* Align filter inputs at the bottom */
        }

        .filter-group {
            flex: 1 1 200px; /* Allows flexibility in width */
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }

        .filters-section select,
        .filters-section input[type="text"],
        .filters-section button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding in width */
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

        .prices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }

        .prices-table th,
        .prices-table td {
            border: 1px solid #eee;
            padding: 12px 15px;
            text-align: left;
        }

        .prices-table th {
            background-color: #e9ecef;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .prices-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .prices-table tr:hover {
            background-color: #f1f1f1;
            /* Animation effect for table rows on hover */
            transform: scale(1.005);
            transition: transform 0.2s ease;
        }

        .price-value {
            font-weight: bold;
            color: #28a745; /* Green color for price */
            font-size: 1.1em;
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
            height:20px;
            width:auto;
            padding:10px;
        color:white;
        font-weight:bold;
         border-radius: 10px;
            background-color:rgb(13, 209, 6); /* Grey for back button */
        }
        a:link {
  text-decoration: none;
}

        .btn-back-dashboard:hover {
            background-color:rgb(5, 94, 27);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .market-prices-container {
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
            .prices-table, .prices-table thead, .prices-table tbody, .prices-table th, .prices-table td, .prices-table tr {
                display: block;
            }
            .prices-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .prices-table tr {
                border: 1px solid #ddd;
                margin-bottom: 15px;
                border-radius: 5px;
            }
            .prices-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            .prices-table td:before {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                content: attr(data-label); /* Use data-label for responsive headers */
            }
            .prices-table td:last-child {
                border-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container market-prices-container">
        <h2>Daily Agricultural Market Prices</h2>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="filters-section">
            <div class="filter-group">
                <label for="category">Filter by Category:</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selected_category == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="location">Filter by Location:</label>
                <select name="location" id="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($selected_location == $loc) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="search_item">Search Item:</label>
                <input type="text" name="search_item" id="search_item" placeholder="e.g., Tomato, Wheat" value="<?php echo htmlspecialchars($search_item); ?>">
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="submit" class="btn">Apply Filters</button>
            </div>
            <div class="filter-group" style="flex: 0 0 auto;">
                <button type="button" class="btn" onclick="window.location.href='market_prices.php'">Clear Filters</button>
            </div>
        </form>

        <?php if (!empty($market_prices)): ?>
            <table class="prices-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Item Name</th>
                        <th>Price</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($market_prices as $price): ?>
                        <tr>
                            <td data-label="Date"><?php echo htmlspecialchars($price['date']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($price['category']); ?></td>
                            <td data-label="Item Name"><?php echo htmlspecialchars($price['item_name']); ?></td>
                            <td data-label="Price" class="price-value">₹ <?php echo htmlspecialchars(number_format($price['price'], 2)); ?> / <?php echo htmlspecialchars($price['unit']); ?></td>
                            <td data-label="Location"><?php echo htmlspecialchars($price['location']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No market prices found matching your criteria. Try different filters.</p>
        <?php endif; ?>

        <p>
            <a href="user_dashboard.php" class="btn btn-back-dashboard">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-back-dashboard" style="background-color:rgb(202, 18, 37);">Logout</a>
        </p>
    </div>
</body>
</html>