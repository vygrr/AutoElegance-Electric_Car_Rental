<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    // Get form data
    $vid = $_POST['vid'];
    $days = $_POST['days'];
    
    // Process the data (e.g., insert into the database)
    // Modify the database connection code as needed

    // Example database connection and query
    $db_host = "localhost"; // or "127.0.0.1"
    $db_port = 3306;        // Default MySQL port
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

    // Example query to insert data into a table
    $sql = "INSERT INTO your_table_name (vid, days) VALUES ('$vid', '$days')";

    if ($conn->query($sql) === TRUE) {
        echo "Form data inserted successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
} else {
    // Handle unauthorized access or missing session
    echo "Unauthorized access";
}
?>
