<?php
// Database connection details (replace with your actual credentials)
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

// Function to close the connection (optional)
function closeConnection($conn) {
  $conn->close();
}
?>