<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "13579Qe@", "user", 3306);
if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

// Handle payment success
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['razorpay_payment_id'])) {
    $user_id = $_SESSION['user_id'];
    $totalprice = $_POST['days'] * $_POST['carPrice'];
    $vid = $_POST['vid'];
    
    $stmt = $conn->prepare("SELECT hid FROM vehicles WHERE vid = ?");
    $stmt->bind_param("i", $vid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) die("Vehicle not found");
    
    $row = $result->fetch_assoc();
    $hid = $row['hid'];
    $rentEndDate = date('Y-m-d H:i:s', strtotime("+".$_POST['days']." days"));
    
    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("INSERT INTO orders (vid, bid, sid, rentamnt, expiry) VALUES (?, ?, ?, ?, ?)");
        $stmt1->bind_param("iiids", $vid, $user_id, $hid, $totalprice, $rentEndDate);
        $stmt1->execute();
        
        $stmt2 = $conn->prepare("UPDATE vehicles SET availability = 0 WHERE vid = ?");
        $stmt2->bind_param("i", $vid);
        $stmt2->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Car rented successfully!";
        header("Location: home.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Transaction failed. Please try again.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market | AutoElegance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.1);
            --hover-shadow: 0 6px 16px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .filters {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-right: 10px;
            white-space: nowrap;
        }
        
        select, input {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
            gap: 25px;
        }
        
        .car-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .car-image-container {
            height: 220px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-gray);
            position: relative;
        }
        
        .car-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .car-card:hover .car-image {
            transform: scale(1.05);
        }
        
        .car-location {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .car-location i {
            margin-right: 5px;
        }
        
        .car-details {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .car-title {
            font-size: 1.5rem;
            margin: 0 0 15px 0;
            color: #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .car-title-text {
            flex: 1;
        }
        
        .car-availability {
            display: inline-block;
            padding: 3px 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 4px;
            background-color: var(--success-color);
            color: white;
        }
        
        .car-specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 20px;
            margin-bottom: 20px;
        }
        
        .spec-item {
            display: flex;
            align-items: center;
        }
        
        .spec-icon {
            color: var(--primary-color);
            font-size: 1rem;
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .spec-label {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-right: 5px;
        }
        
        .spec-value {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .car-owner {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .owner-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
            font-size: 0.8rem;
        }
        
        .owner-name {
            font-weight: 600;
        }
        
        .car-description {
            margin: 15px 0;
            color: #555;
            font-size: 0.95rem;
            flex-grow: 1;
        }
        
        .price-rent {
            margin-top: 15px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .price small {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .rent-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .rent-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .rent-btn i {
            margin-right: 6px;
        }
        
        .no-cars {
            text-align: center;
            padding: 50px;
            grid-column: 1 / -1;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }
        
        .no-cars i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            padding: 30px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.2);
            animation: modalFade 0.3s ease;
        }
        
        @keyframes modalFade {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .modal-title {
            font-size: 1.6rem;
            margin: 0;
            color: #222;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .total-display {
            font-size: 1.4rem;
            font-weight: 600;
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 6px;
            color: var(--primary-dark);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--secondary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--light-gray);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .car-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                margin-bottom: 10px;
            }
            
            .car-specs {
                grid-template-columns: 1fr;
            }
            
            .price-rent {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .modal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <div class="filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="city"><i class="fas fa-map-marker-alt"></i> City:</label>
                    <select id="city">
                        <option value="all">All Cities</option>
                        <option value="Belapur">Belapur</option>
                        <option value="Nerul">Nerul</option>
                        <option value="Sanpada">Sanpada</option>
                        <option value="Vashi">Vashi</option>
                        <option value="Kopar Kharaine">Kopar Kharaine</option>
                        <option value="Ghansoli">Ghansoli</option>
                        <option value="Airoli">Airoli</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort"></i> Sort By:</label>
                    <select id="sort">
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                        <option value="range-desc">Range: Highest First</option>
                        <option value="capacity-desc">Capacity: Highest First</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="car-grid">
            <?php
            $stmt = $conn->prepare("SELECT v.*, u.fullname as owner_name 
                                   FROM vehicles v
                                   JOIN users u ON v.hid = u.uid
                                   WHERE v.hid != ? AND v.availability = 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Get owner initials for avatar
                    $words = explode(" ", $row['owner_name']);
                    $initials = "";
                    foreach ($words as $w) {
                        $initials .= strtoupper(substr($w, 0, 1));
                    }
                    $initials = substr($initials, 0, 2);
                    
                    echo '<div class="car-card" data-city="'.$row['city'].'" data-price="'.$row['price'].'" data-range="'.$row['drange'].'" data-capacity="'.$row['capacity'].'">';
                    
                    // Car Image
                    echo '<div class="car-image-container">';
                    echo '<div class="car-location"><i class="fas fa-map-marker-alt"></i> '.$row['city'].'</div>';
                    if (!empty($row['file'])) {
                        echo '<img src="images/'.$row['file'].'" alt="'.$row['modelname'].'" class="car-image">';
                    } else {
                        echo '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;">';
                        echo '<i class="fas fa-car-side" style="font-size:3.5rem;"></i>';
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    // Car Details
                    echo '<div class="car-details">';
                    
                    echo '<h3 class="car-title">';
                    echo '<span class="car-title-text">'.$row['modelname'].'</span>';
                    echo '<span class="car-availability">Available</span>';
                    echo '</h3>';
                    
                    echo '<div class="car-owner">';
                    echo '<div class="owner-avatar">'.$initials.'</div>';
                    echo '<div class="owner-name">'.$row['owner_name'].'</div>';
                    echo '</div>';
                    
                    echo '<div class="car-specs">';
                    echo '<div class="spec-item"><span class="spec-icon"><i class="fas fa-road"></i></span><span class="spec-label">Range:</span><span class="spec-value">'.$row['drange'].' km</span></div>';
                    echo '<div class="spec-item"><span class="spec-icon"><i class="fas fa-users"></i></span><span class="spec-label">Seats:</span><span class="spec-value">'.$row['capacity'].'</span></div>';
                    echo '<div class="spec-item"><span class="spec-icon"><i class="fas fa-plug"></i></span><span class="spec-label">Connector:</span><span class="spec-value">'.$row['connector'].'</span></div>';
                    // Free cancellation removed as requested
                    echo '</div>';
                    
                    echo '<div class="car-description">';
                    echo '<p>'.nl2br(htmlspecialchars($row['descr'])).'</p>';
                    echo '</div>';
                    
                    echo '<div class="price-rent">';
                    echo '<div class="price">₹'.number_format($row['price'], 2).'<small>/ day</small></div>';
                    
                    echo '<button class="rent-btn" 
                            data-vid="'.$row['vid'].'" 
                            data-price="'.$row['price'].'"
                            onclick="openRentForm('.$row['vid'].', '.$row['price'].', \''.addslashes($row['modelname']).'\')">
                            <i class="fas fa-calendar-alt"></i> Rent Now
                          </button>';
                    echo '</div>'; // Close price-rent
                    
                    echo '</div>'; // Close car-details
                    echo '</div>'; // Close car-card
                }
            } else {
                echo '<div class="no-cars">';
                echo '<i class="fas fa-car-side"></i>';
                echo '<h3>No electric vehicles available for rent</h3>';
                echo '<p>Please check back later or try a different city</p>';
                echo '</div>';
            }
            
            $conn->close();
            ?>
        </div>
    </div>
    
    <!-- Rent Modal -->
    <div id="rentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalCarTitle"></h3>
            </div>
            
            <form id="rentForm" method="post">
                <input type="hidden" id="modalCarVid" name="vid">
                <input type="hidden" id="modalCarPrice" name="carPrice">
                
                <div class="form-group">
                    <label for="rentDays"><i class="fas fa-calendar-day"></i> Number of Days</label>
                    <input type="number" id="rentDays" name="days" class="form-control" min="1" value="1" oninput="calculateTotal()">
                </div>
                
                <div class="total-display">
                    Total: ₹<span id="totalAmount">0.00</span>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // City filter
        document.getElementById('city').addEventListener('change', function() {
            const selectedCity = this.value;
            filterCars();
        });
        
        // Sort functionality
        document.getElementById('sort').addEventListener('change', function() {
            sortCars();
        });
        
        function filterCars() {
            const selectedCity = document.getElementById('city').value;
            const cards = document.querySelectorAll('.car-card');
            
            cards.forEach(card => {
                if (selectedCity === 'all' || card.dataset.city === selectedCity) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // After filtering, re-sort
            sortCars();
        }
        
        function sortCars() {
            const sortBy = document.getElementById('sort').value;
            const container = document.querySelector('.car-grid');
            const cards = Array.from(container.querySelectorAll('.car-card:not([style*="display: none"])'));
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'price-asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'range-desc':
                        return parseFloat(b.dataset.range) - parseFloat(a.dataset.range);
                    case 'capacity-desc':
                        return parseFloat(b.dataset.capacity) - parseFloat(a.dataset.capacity);
                    default:
                        return 0;
                }
            });
            
            // Remove all cards and re-append in sorted order
            cards.forEach(card => container.appendChild(card));
        }
        
        // Modal functions
        function openRentForm(vid, price, carName) {
            document.getElementById('modalCarTitle').textContent = carName;
            document.getElementById('modalCarVid').value = vid;
            document.getElementById('modalCarPrice').value = price;
            document.getElementById('rentDays').value = 1;
            calculateTotal();
            document.getElementById('rentModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('rentModal').style.display = 'none';
        }
        
        function calculateTotal() {
            const days = parseInt(document.getElementById('rentDays').value) || 0;
            const price = parseFloat(document.getElementById('modalCarPrice').value) || 0;
            const total = days * price;
            document.getElementById('totalAmount').textContent = total.toFixed(2);
        }
        
        function processPayment() {
            const days = parseInt(document.getElementById('rentDays').value);
            const price = parseFloat(document.getElementById('modalCarPrice').value);
            const carName = document.getElementById('modalCarTitle').textContent;
            
            if (!days || days < 1) {
                alert("Please enter a valid number of days");
                return;
            }
            
            const amount = days * price * 100; // Razorpay uses paise
            
            const options = {
                key: "rzp_test_es0KPgPxomh8Lr",
                amount: amount,
                currency: "INR",
                name: "ElectriRent",
                description: `Rental: ${carName} for ${days} days`,
                handler: function(response) {
                    // Add payment ID to form and submit
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'razorpay_payment_id';
                    input.value = response.razorpay_payment_id;
                    document.getElementById('rentForm').appendChild(input);
                    document.getElementById('rentForm').submit();
                },
                theme: { color: "#3498db" }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('rentModal')) {
                closeModal();
            }
        });
        
        // Initialize page on load
        document.addEventListener('DOMContentLoaded', function() {
            sortCars();
        });
    </script>
</body>
</html>