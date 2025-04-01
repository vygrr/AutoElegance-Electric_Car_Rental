<?php
session_start(); 
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Include connection script
require_once('connection.php');

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Fetch user data based on user ID
$sql = "SELECT username, fullname FROM users WHERE uid = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $fullname = $user['fullname'];
} else {
    echo "User data not found";
}

// Fetch rented cars data
$sql_rented_cars = "SELECT o.*, v.modelname, v.price, u.fullname AS seller_name
                    FROM orders o 
                    INNER JOIN vehicles v ON o.sid = v.hid AND o.vid = v.vid 
                    INNER JOIN users u ON v.hid = u.uid 
                    WHERE o.bid = '$user_id'";
$result_rented_cars = $conn->query($sql_rented_cars);

// Fetch cars added by the user
$sql_added_cars = "SELECT *
                   FROM vehicles
                   WHERE hid = $user_id";
$result_added_cars = $conn->query($sql_added_cars);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Home | AutoElegance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --color-primary: #1a73e8;
            --color-primary-light: #4285f4;
            --color-primary-dark: #0d47a1;
            --color-secondary: #34a853;
            --color-danger: #ea4335;
            --color-warning: #fbbc05;
            --color-white: #ffffff;
            --color-light-gray: #f5f7fa;
            --color-border: #e1e5eb;
            --color-text: #202124;
            --color-text-secondary: #5f6368;
            --color-hover: #e8f0fe;
        }

        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-light-gray);
            color: var(--color-text);
            line-height: 1.5;
        }

        .container {
            max-width: 1620px;
            width: 90%;
            margin: 0 auto;
        }

        .navbar {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 15px 0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            margin: 0;
            font-size: 24px;
            font-weight: 500;
        }

        .dealer-name {
            margin: 0;
            font-weight: 500;
            font-size: 16px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin-left: 20px;
        }

        .nav-links a {
            color: var(--color-white);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .page-title {
            text-align: center;
            margin: 30px 0 15px;
            color: var(--color-primary-dark);
            font-size: 24px;
            font-weight: 500;
        }

        .table-container {
            width: 90%;
            max-height: 500px;
            overflow: auto;
            margin: 20px auto;
            background-color: var(--color-white);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border: 1px solid var(--color-border);
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 12px 15px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .order-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--color-border);
        }

        .order-table tr:hover {
            background-color: var(--color-hover);
        }

        .order-table tr:last-child td {
            border-bottom: none;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: var(--color-text-secondary);
            font-style: italic;
        }

        .action-button, .delete-button {
            padding: 8px 12px;
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .delete-button {
            background-color: var(--color-danger);
        }

        .action-button:hover, .delete-button:hover {
            opacity: 0.9;
        }

        .fixed-button {
            position: fixed;
            bottom: 30px;
            padding: 12px 28px;
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            z-index: 100;
        }

        .fixed-button:hover {
            background-color: var(--color-primary-dark);
        }

        .add-car-button {
            right: 30px;
        }

        .market-button {
            left: 30px;
        }

        .success-message {
            background-color: var(--color-secondary);
            color: var(--color-white);
            padding: 15px;
            margin: 20px auto;
            width: 90%;
            max-width: 600px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Status colors */
        .status-completed {
            color: var(--color-secondary);
            font-weight: 500;
        }
        
        .status-ongoing {
            color: var(--color-primary);
            font-weight: 500;
        }
        
        .status-expired {
            color: var(--color-danger);
            font-weight: 500;
        }
        
        .status-available {
            color: var(--color-secondary);
            font-weight: 500;
        }
        
        .status-rented {
            color: var(--color-danger);
            font-weight: 500;
        }
        
        /* Animation classes */
        .slide-in {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.5s, transform 0.5s;
        }

        .animated {
            opacity: 1;
            transform: translateY(0);
        }

        /* Staggered animation delay */
        .slide-in:nth-child(1) { transition-delay: 0.1s; }
        .slide-in:nth-child(2) { transition-delay: 0.2s; }
        .slide-in:nth-child(3) { transition-delay: 0.3s; }
        .slide-in:nth-child(4) { transition-delay: 0.4s; }
    </style>
</head>

<body>
    <nav class="navbar slide-in">
        <div class="container">
            <h1 class="logo">USER PORTAL</h1>
            <p class="dealer-name">Welcome, <?php echo $fullname; ?></p>
            <ul class="nav-links">
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <?php
    // Check if success message session variable is set
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-message slide-in">' . $_SESSION['success_message'] . '</div>';
        
        // Unset the session variable after displaying the message
        unset($_SESSION['success_message']);
    }
    ?>

    <h3 class="page-title slide-in">Rental History</h3>

    <div class="table-container slide-in">
        <table class="order-table">
            <tr>
                <th>Model Name</th>
                <th>Seller Name</th>
                <th>Rental Amount</th>
                <th>Status</th>
                <th>Time Details</th>
            </tr>
            <?php
            if ($result_rented_cars->num_rows > 0) {
                while ($row = $result_rented_cars->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['modelname'] . "</td>";
                    echo "<td>" . $row['seller_name'] . "</td>";
                    echo "<td>₹" . number_format($row['rentamnt'], 2) . "</td>";
                    
                    $expiryDate = new DateTime($row['expiry']);
                    $now = new DateTime();
                    
                    if ($row['iscomplete'] == 1) {
                        echo "<td><span class='status-completed'>Completed</span></td>";
                        echo "<td>Returned on " . $expiryDate->format('Y-m-d H:i') . "</td>";
                    } else {
                        if ($now < $expiryDate) {
                            $interval = $now->diff($expiryDate);
                            echo "<td><span class='status-ongoing'>Ongoing</span></td>";
                            echo "<td>" . $interval->format('%a days %h hours remaining') . "</td>";
                        } else {
                            echo "<td><span class='status-expired'>Expired</span></td>";
                            echo "<td>Expired on " . $expiryDate->format('Y-m-d H:i') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="5" class="empty-message">You have not yet rented any car</td></tr>';
            }
            ?>
        </table>
    </div>

    <h3 class="page-title slide-in">Added Cars</h3>
   
    <div class="table-container slide-in">
        <table class="order-table">
            <tr>
                <th>Model Name</th>
                <th>City</th>
                <th>Capacity</th>
                <th>Connector</th>
                <th>Driving Range</th>
                <th>Price/Day</th>
                <th>Availability</th>
                <th>Action</th>
            </tr>
            <?php
            if ($result_added_cars->num_rows > 0) {
                while ($row = $result_added_cars->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['modelname'] . "</td>";
                    echo "<td>" . $row['city'] . "</td>";
                    echo "<td>" . $row['capacity'] . " seats</td>";
                    echo "<td>" . $row['connector'] . "</td>";
                    echo "<td>" . $row['drange'] . " km</td>";
                    echo "<td>₹" . number_format($row['price'], 2) . "</td>";
                    
                    if ($row['availability']) {
                        echo "<td><span class='status-available'>Available</span></td>";
                        echo '<td>
                                <form method="POST" action="delete_car.php">
                                    <input type="hidden" name="vid" value="' . $row['vid'] . '">
                                    <button type="submit" name="delete_car" class="delete-button">Delete</button>
                                </form>
                              </td>';
                    } else {
                        echo "<td><span class='status-rented'>Rented</span></td>";
                        echo "<td>-</td>";
                    }
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="8" class="empty-message">You have not added any cars yet</td></tr>';
            }
            ?>
        </table>
    </div>
    
    <a href="addcar.php" class="fixed-button add-car-button slide-in">Add a Car</a>
    <a href="market.php" class="fixed-button market-button slide-in">Market</a>

    <script>
        // Add animation when page loads
        document.addEventListener("DOMContentLoaded", function() {
            const elementsToAnimate = document.querySelectorAll(".slide-in");
            elementsToAnimate.forEach((element) => element.classList.add("animated"));
            
            // Observer for dynamically added elements (like success message)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        const newElements = document.querySelectorAll(".slide-in:not(.animated)");
                        newElements.forEach((element) => element.classList.add("animated"));
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</body>
</html>