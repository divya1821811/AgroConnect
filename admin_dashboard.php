<?php
// Start the session
session_start();

// Check if admin is logged in, if not then redirect to admin login page
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] != 'admin') {
    header('location: admin_login.php');
    exit();
}

// Include database connection file
require_once 'config.php';

// Initialize variables for Add/Edit form
$item_id = $category = $item_name = $price = $unit = $location = $date = '';
$category_err = $item_name_err = $price_err = $unit_err = $location_err = $date_err = '';
$success_message = $error_message = '';

// --- Handle Add/Edit Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_price'])) {
    // Validate inputs
    if (empty(trim($_POST['category']))) { $category_err = 'Please enter category.'; } else { $category = trim($_POST['category']); }
    if (empty(trim($_POST['item_name']))) { $item_name_err = 'Please enter item name.'; } else { $item_name = trim($_POST['item_name']); }
    if (empty(trim($_POST['price']))) { $price_err = 'Please enter price.'; } elseif (!is_numeric(trim($_POST['price'])) || trim($_POST['price']) <= 0) { $price_err = 'Price must be a positive number.'; } else { $price = trim($_POST['price']); }
    if (empty(trim($_POST['unit']))) { $unit_err = 'Please enter unit (e.g., Kg, Quintal).'; } else { $unit = trim($_POST['unit']); }
    if (empty(trim($_POST['location']))) { $location_err = 'Please enter location.'; } else { $location = trim($_POST['location']); }
    if (empty(trim($_POST['date']))) { $date_err = 'Please enter date.'; } else { $date = trim($_POST['date']); }

    // Check if it's an edit or add operation
    $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : '';

    if (empty($category_err) && empty($item_name_err) && empty($price_err) && empty($unit_err) && empty($location_err) && empty($date_err)) {
        if (!empty($item_id)) { // Edit existing item
            $sql = "UPDATE market_prices SET category = ?, item_name = ?, price = ?, unit = ?, location = ?, date = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, 'ssdsssi', $param_category, $param_item_name, $param_price, $param_unit, $param_location, $param_date, $param_id);

                $param_category = $category;
                $param_item_name = $item_name;
                $param_price = $price;
                $param_unit = $unit;
                $param_location = $location;
                $param_date = $date;
                $param_id = $item_id;

                if (mysqli_stmt_execute($stmt)) {
                    $success_message = 'Market price updated successfully!';
                } else {
                    $error_message = 'Error updating market price: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        } else { // Add new item
            $sql = "INSERT INTO market_prices (category, item_name, price, unit, location, date) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, 'ssdsss', $param_category, $param_item_name, $param_price, $param_unit, $param_location, $param_date);

                $param_category = $category;
                $param_item_name = $item_name;
                $param_price = $price;
                $param_unit = $unit;
                $param_location = $location;
                $param_date = $date;

                if (mysqli_stmt_execute($stmt)) {
                    $success_message = 'Market price added successfully!';
                    // Clear form fields after successful add
                    $category = $item_name = $price = $unit = $location = $date = '';
                } else {
                    $error_message = 'Error adding market price: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// --- Handle Delete Operation ---
if (isset($_GET['delete_id']) && !empty(trim($_GET['delete_id']))) {
    $delete_id = trim($_GET['delete_id']);
    $sql = "DELETE FROM market_prices WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $param_id);
        $param_id = $delete_id;
        if (mysqli_stmt_execute($stmt)) {
            $success_message = 'Market price deleted successfully!';
        } else {
            $error_message = 'Error deleting market price: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
    // Redirect to prevent re-submission on refresh
    header('location: admin_dashboard.php');
    exit();
}

// --- Handle Edit Data Population ---
if (isset($_GET['edit_id']) && !empty(trim($_GET['edit_id']))) {
    $edit_id = trim($_GET['edit_id']);
    $sql = "SELECT id, category, item_name, price, unit, location, date FROM market_prices WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $param_id);
        $param_id = $edit_id;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $item = mysqli_fetch_assoc($result);
                $item_id = $item['id'];
                $category = $item['category'];
                $item_name = $item['item_name'];
                $price = $item['price'];
                $unit = $item['unit'];
                $location = $item['location'];
                $date = $item['date'];
            } else {
                $error_message = 'Market price not found for editing.';
            }
        } else {
            $error_message = 'Error fetching market price for editing.';
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Fetch all Market Prices for Display ---
$market_prices = [];
$sql_fetch_all = "SELECT id, category, item_name, price, unit, location, date FROM market_prices ORDER BY date DESC, item_name ASC";
$result_all = mysqli_query($conn, $sql_fetch_all);
if ($result_all) {
    while ($row = mysqli_fetch_assoc($result_all)) {
        $market_prices[] = $row;
    }
} else {
    $error_message .= 'Could not retrieve market prices from database.';
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgroConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Admin specific styles */
        body {
            background-color: #f0f2f5;
        }
        .admin-dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .admin-header h2 {
            color: #343a40;
            font-size: 2.2em;
            margin: 0;
        }
        .admin-header .logout-btn {
            background-color: #dc3545;
            padding: 8px 20px;
            font-size: 1em;
        }
        .admin-header .logout-btn:hover {
            background-color: #c82333;
        }

        .section-title {
            color: #495057;
            margin-top: 40px;
            margin-bottom: 25px;
            font-size: 1.8em;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            display: inline-block;
        }

        .form-add-edit {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-add-edit .form-group {
            margin-bottom: 15px;
        }
        .form-add-edit label {
            font-weight: 600;
            color: #333;
        }
        .form-add-edit input[type="text"],
        .form-add-edit input[type="number"],
        .form-add-edit input[type="date"],
        .form-add-edit select {
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 15px;
        }
        .form-add-edit .btn {
            width: auto;
            padding: 10px 25px;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-reset {
            background-color: #6c757d;
        }
        .btn-reset:hover {
            background-color: #5a6268;
        }


        .market-prices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .market-prices-table th,
        .market-prices-table td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
            font-size: 0.95em;
        }
        .market-prices-table th {
            background-color: #e9ecef;
            color: #343a40;
            font-weight: 600;
        }
        .market-prices-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .market-prices-table tr:hover {
            background-color: #e2e6ea;
            transition: background-color 0.2s ease;
        }
        .market-prices-table .actions a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .market-prices-table .actions a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .market-prices-table .actions .delete-link {
            color: #dc3545;
        }
        .market-prices-table .actions .delete-link:hover {
            color: #c82333;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-size: 1.1em;
        }

        /* Responsive adjustments for table */
        @media (max-width: 768px) {
            .admin-dashboard-container {
                margin: 15px;
                padding: 15px;
            }
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .market-prices-table, .market-prices-table thead, .market-prices-table tbody, .market-prices-table th, .market-prices-table td, .market-prices-table tr {
                display: block;
            }
            .market-prices-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .market-prices-table tr {
                border: 1px solid #dee2e6;
                margin-bottom: 10px;
                border-radius: 8px;
            }
            .market-prices-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
                font-size: 1em;
            }
            .market-prices-table td:last-child {
                border-bottom: 0;
            }
            .market-prices-table td:before {
                position: absolute;
                top: 12px;
                left: 12px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                content: attr(data-label);
                color: #555;
            }
            .form-add-edit .btn {
                width: 100%;
                margin-top: 10px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container admin-dashboard-container">
        <div class="admin-header">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?> (Admin)</h2>
            <a href="logout.php" class="btn logout-btn">Logout</a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h3 class="section-title"><?php echo (!empty($item_id)) ? 'Edit' : 'Add New'; ?> Market Price</h3>
        <div class="form-add-edit">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($category); ?>" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                    <span class="alert-danger"><?php echo $category_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="item_name">Item Name:</label>
                    <input type="text" name="item_name" id="item_name" value="<?php echo htmlspecialchars($item_name); ?>" class="form-control <?php echo (!empty($item_name_err)) ? 'is-invalid' : ''; ?>">
                    <span class="alert-danger"><?php echo $item_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($price); ?>" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>">
                    <span class="alert-danger"><?php echo $price_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="unit">Unit (e.g., Kg, Quintal, Ton):</label>
                    <input type="text" name="unit" id="unit" value="<?php echo htmlspecialchars($unit); ?>" class="form-control <?php echo (!empty($unit_err)) ? 'is-invalid' : ''; ?>">
                    <span class="alert-danger"><?php echo $unit_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location); ?>" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>">
                    <span class="alert-danger"><?php echo $location_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>" class="form-control <?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" max="<?php echo date('Y-m-d'); ?>">
                    <span class="alert-danger"><?php echo $date_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" name="submit_price" class="btn" value="<?php echo (!empty($item_id)) ? 'Update Price' : 'Add Price'; ?>">
                    <?php if (!empty($item_id)): ?>
                        <a href="admin_dashboard.php" class="btn btn-reset">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h3 class="section-title">Current Market Prices</h3>
        <?php if (!empty($market_prices)): ?>
            <table class="market-prices-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Item Name</th>
                        <th>Price</th>
                        <th>Unit</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($market_prices as $item): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($item['id']); ?></td>
                            <td data-label="Date"><?php echo htmlspecialchars($item['date']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($item['category']); ?></td>
                            <td data-label="Item Name"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td data-label="Price">₹<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                            <td data-label="Unit"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td data-label="Location"><?php echo htmlspecialchars($item['location']); ?></td>
                            <td class="actions" data-label="Actions">
                                <a href="admin_dashboard.php?edit_id=<?php echo htmlspecialchars($item['id']); ?>">Edit</a> |
                                <a href="admin_dashboard.php?delete_id=<?php echo htmlspecialchars($item['id']); ?>" onclick="return confirm('Are you sure you want to delete this price?');" class="delete-link">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">No market prices available. Add new prices using the form above.</p>
        <?php endif; ?>
    </div>
</body>
</html>