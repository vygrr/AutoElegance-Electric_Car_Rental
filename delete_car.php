<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Include database connection
require_once('connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_car'])) {
    // Get the vehicle ID (vid) from the form
    $vid = $_POST['vid'];

    // Prepare and execute SQL DELETE statement
    $sql_delete = "DELETE FROM vehicles WHERE vid = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    // Bind the parameter
    $stmt_delete->bind_param("i", $vid);
    
    // Execute the statement
    if ($stmt_delete->execute()) {
        $_SESSION['success_message'] = "Car deleted successfully!";
    
        // Redirect to home page
        header("Location: home.php");
        exit;
    } else {
        // Deletion failed
        $_SESSION['error_message'] = "Error deleting car. Please try again.";
    }

    // Close statement
    $stmt_delete->close();
}

// Close database connection
$conn->close();


?>
