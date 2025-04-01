<?php
// Database configuration
$db_host = "localhost";    // or "127.0.0.1"
$db_port = 3306;           // Default MySQL port
$db_user = "root";
$db_pass = "13579Qe@";
$db_name = "user";

// Create connection with explicit port
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($conn->connect_error) {
    error_log("MySQL Connection Failed: " . $conn->connect_error);
    die("Database maintenance in progress. Please try again later.");
}

// Update iscomplete column based on expiry date
$currentDateTime = date('Y-m-d H:i:s');
$sql_update = "UPDATE orders SET iscomplete = 1 WHERE expiry < ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("s", $currentDateTime);
$stmt_update->execute();
$stmt_update->close();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoElegance - Rent Electric Cars</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            text-decoration: none;
            list-style: none;
            font-family: "Trebuchet MS", "Lucida Sans Unicode", "Lucida Grande",
                "Lucida Sans", Arial, sans-serif;
        }

        :root {
            --color-primary: #1743e3;
            --color-white: #eaeaea;
            --color-dark: #333;
            --color-black: #222;
        }

        body {
            overflow-x: hidden;
        }

        .container {
            max-width: 1620px;
            width: 90%;
            margin: 0 auto;
        }

        /* Navigation Styles */
        nav {
            width: 100%;
            height: 80px;
            position: absolute;
            left: 0;
            top: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
        }

        .nav-container ul {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .logo h3 {
            font-size: 28px;
            color: var(--color-black);
            opacity: 0;
            animation: logoAni 1s ease forwards;
        }

        .nav-link li a {
            color: var(--color-black);
            font-size: 17px;
            transition: 0.4s ease;
            display: inline-block;
            animation: NavliaAni forwards 1s ease;
            animation-delay: calc(0.2s * var(--i));
            opacity: 0;
            padding: 8px 15px;
            border-radius: 4px;
        }

        .nav-link li a:hover {
            color: var(--color-primary);
            background-color: rgba(23, 67, 227, 0.1);
        }

        .nav-link li .active {
            color: var(--color-primary);
            font-weight: bold;
        }

        /* Main Content Sections */
        section {
            margin: 40px 0;
            padding: 20px 0;
        }

        section h2 {
            font-size: 32px;
            text-align: center;
            margin-bottom: 30px;
            color: var(--color-dark);
            position: relative;
            padding-bottom: 10px;
        }

        section h2:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--color-primary);
        }

        /* Video Section */
        .video-container {
            position: relative;
            width: 100%;
            height: 60vh;
            margin-top: 80px;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Info Boxes */
        .info-section {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .infobox {
            width: 30%;
            padding: 25px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .infobox:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .box-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .box-content img {
            max-width: 80px;
            max-height: 80px;
            transition: transform 0.3s ease;
        }

        .infobox:hover img {
            transform: scale(1.1);
        }

        .box-content p {
            color: var(--color-dark);
            line-height: 1.6;
            font-size: 16px;
        }

        /* Footer */
        footer {
            background-color: var(--color-primary);
            color: white;
            padding: 40px 0 60px;
            position: relative;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            width: 90%;
            margin: 0 auto;
            gap: 30px;
        }

        .contact-info {
            width: 40%;
        }

        .contact-info h3 {
            font-size: 24px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .contact-info h3:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: white;
        }

        .contact-info p {
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.6;
        }

        #map-frame {
            width: 50%;
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
        }

        #map-frame iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .footer-copyright {
            position: absolute;
            bottom: 20px;
            left: 5%;
            font-size: 14px;
        }

        /* Animations */
        @keyframes logoAni {
            0% {
                transform: translateX(-100px);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes NavliaAni {
            0% {
                transform: translateY(100px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media only screen and (max-width: 1024px) {
            .infobox {
                width: 45%;
            }
            
            .contact-info, #map-frame {
                width: 100%;
            }
        }

        @media only screen and (max-width: 768px) {
            .video-container {
                height: 40vh;
            }
            
            section h2 {
                font-size: 28px;
            }
        }

        @media only screen and (max-width: 591px) {
            .infobox {
                width: 100%;
            }
            
            .footer-container {
                flex-direction: column;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
            
            nav {
                height: auto;
                padding: 15px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <h3>AutoElegance</h3>
            </a>
            <ul class="nav-link">
                <li><a href="index.php" style="--i:1;" class="active">Home</a></li>
                <li><a href="login.php" style="--i:3;">Login</a></li>
                <li><a href="registration.php" style="--i:4;">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Video Section -->
    <section>
        <div class="video-container">
            <video src="images/WP-FINAL.mp4" autoplay muted loop></video>
        </div>
    </section>

    <!-- Electric Car Section -->
    <section>
        <h2>Why Electric Cars?</h2>
        <div class="info-section">
            <div class="infobox">
                <div class="box-content">
                    <img src="images/high-performance.png" alt="High Performance">
                    <p>Experience high-performance with lightning-fast acceleration, making every drive exhilarating and efficient.</p>
                </div>
            </div>
            <div class="infobox">
                <div class="box-content">
                    <img src="images/green-economy.png" alt="Green Economy">
                    <p>Embrace a green economy with pocket-friendly electric cars, reducing carbon footprint while saving money.</p>
                </div>
            </div>
            <div class="infobox">
                <div class="box-content">
                    <img src="images/eco-energy.png" alt="Eco Energy">
                    <p>Choose eco-energy vehicles for a planet-friendly ride, contributing to sustainability.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why AutoElegance Section -->
    <section>
        <h2>Why AutoElegance?</h2>
        <div class="info-section">
            <div class="infobox">
                <div class="box-content">
                    <img src="images/car.png" alt="Premium Cars">
                    <p>AutoElegance offers top-notch products, providing customers with an exceptional driving experience.</p>
                </div>
            </div>
            <div class="infobox">
                <div class="box-content">
                    <img src="images/rating_3773000.png" alt="High Ratings">
                    <p>Trust our stellar reviews, ensuring the best-in-class service and satisfaction.</p>
                </div>
            </div>
            <div class="infobox">
                <div class="box-content">
                    <img src="images/networking_1239608.png" alt="Community">
                    <p>Join a community of enthusiasts, connecting through a shared passion for eco-friendly transportation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="contact-info">
                <h3>Contact Us</h3>
                <p>Email: info@autoelegance.com</p>
                <p>Phone: +1 123-456-7890</p>
                <p>Address: 123 Main St, City, Country</p>
            </div>
            <!-- Google Maps API Integration -->
            <div id="map-frame">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15090.677740708084!2d73.1173691153526!3d18.990200947700366!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7e866de88667f%3A0xc1c5d5badc610f5f!2sPillai%20College%20of%20Engineering%2C%20New%20Panvel%20(Autonomous)!5e0!3m2!1sen!2sin!4v1707241870504!5m2!1sen!2sin"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
        <div class="footer-copyright">
            <p>&copy; 2024 AutoElegance. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>