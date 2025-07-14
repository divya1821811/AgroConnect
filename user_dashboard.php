<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'user') {
    header('location: user_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - AgroConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-green: #4a6f28;
            --secondary-green: #6b8c42;
            --light-green: #e8f5e9;
            --primary-brown: #8d6e63;
            --secondary-brown: #a1887f;
            --light-brown: #d7ccc8;
            --white: #ffffff;
            --dark: #333333;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-green);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            background-image: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 30px;
            width: 100%;
            max-width: 600px;
            transition: var(--transition);
            border: 1px solid var(--light-brown);
            text-align: center;
        }

        .container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        h2 {
            color: var(--primary-green);
            margin-bottom: 20px;
            font-size: 28px;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-brown);
            border-radius: 3px;
        }

        p {
            margin-bottom: 20px;
            color: var(--dark);
        }

        .btn {
            display: inline-block;
            width: 100%;
            max-width: 250px;
            padding: 12px 20px;
            margin: 10px 0;
            background-color: var(--primary-green);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
        }

        .btn:hover {
            background-color: var(--secondary-green);
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-market {
            background-color: #007bff;
        }

        .btn-market:hover {
            background-color: #0069d9;
        }

        .btn-transport {
            background-color: #17a2b8;
        }

        .btn-transport:hover {
            background-color: #138496;
        }

        .btn-history {
            background-color: var(--primary-brown);
        }

        .btn-history:hover {
            background-color: var(--secondary-brown);
        }

        .btn-logout {
            background-color: #dc3545;
        }

        .btn-logout:hover {
            background-color: #c82333;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 30px;
        }

        /* Animation for buttons */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-grid a {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .dashboard-grid a:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-grid a:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-grid a:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-grid a:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive adjustments */
        @media (min-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 24px;
            }

            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        /* Welcome animation */
        @keyframes welcomeFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .welcome-message {
            animation: welcomeFadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>This is your user dashboard. What would you like to do today?</p>
        </div>
        
        <div class="dashboard-grid">
            <a href="market_prices.php" class="btn btn-market">
                <i class="fas fa-chart-line"></i> View Market Prices
            </a>
            <a href="transport_list.php" class="btn btn-transport">
                <i class="fas fa-truck"></i> Find Transport
            </a>
            <a href="user_history.php" class="btn btn-history">
                <i class="fas fa-history"></i> My Booking History
            </a>
            <a href="logout.php" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>