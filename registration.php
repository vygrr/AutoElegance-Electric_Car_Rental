<?php
// Database configuration
$db_config = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '13579Qe@',  // Consider using environment variables for credentials
    'name' => 'user'
];

// Initialize variables
$error = '';
$success = '';
$formData = [
    'username' => '',
    'email' => '',
    'phone' => '',
    'fullname' => ''
];

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to safely connect to database
function connectDatabase($config) {
    try {
        $conn = new mysqli(
            $config['host'], 
            $config['user'], 
            $config['pass'], 
            $config['name'], 
            $config['port']
        );
        
        if ($conn->connect_error) {
            error_log("MySQL Connection Failed: " . $conn->connect_error);
            throw new Exception("Database connection error");
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Database maintenance in progress. Please try again later.");
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to database
    $conn = connectDatabase($db_config);
    
    // Get and sanitize input
    $formData = [
        'username' => filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_NUMBER_INT),
        'fullname' => filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS)
    ];
    
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($formData['username']) || empty($password) || empty($formData['email']) || 
        empty($formData['phone']) || empty($formData['fullname'])) {
        $error = "All fields are required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!preg_match('/^\d{10}$/', $formData['phone'])) {
        $error = "Phone number must be 10 digits";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Check for duplicate values using prepared statements
        $duplicateChecks = [
            'username' => "SELECT * FROM users WHERE username = ?",
            'email' => "SELECT * FROM users WHERE email = ?",
            'phone' => "SELECT * FROM users WHERE phone = ?"
        ];
        
        $duplicates = [];
        
        foreach ($duplicateChecks as $field => $query) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $formData[$field]);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $duplicates[] = $field;
            }
            
            $stmt->close();
        }
        
        if (!empty($duplicates)) {
            $error = ucfirst(implode(', ', $duplicates)) . " already exists. Please login if you have an account.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare insert statement
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, fullname) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $formData['username'], 
                $hashed_password, 
                $formData['email'], 
                $formData['phone'], 
                $formData['fullname']
            );
            
            if ($stmt->execute()) {
                $success = "Account registered successfully! You can now login.";
                // Clear form data on success
                $formData = [
                    'username' => '',
                    'email' => '',
                    'phone' => '',
                    'fullname' => ''
                ];
            } else {
                $error = "Registration failed: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | AutoElegance</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --primary-hover: #3a5982;
            --secondary-color: #6c757d;
            --secondary-hover: #5a6268;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #212529;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .registration-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 450px;
            transition: transform 0.2s;
        }
        
        .registration-container:hover {
            transform: translateY(-5px);
        }
        
        .registration-header {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .registration-header h1 {
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .registration-form .form-group {
            margin-bottom: 16px;
        }
        
        .registration-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .registration-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .registration-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
            outline: none;
        }
        
        .error-message {
            color: var(--error-color);
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 4px;
            text-align: center;
        }
        
        .success-message {
            color: var(--success-color);
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
            text-align: center;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.2s;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider hr {
            flex-grow: 1;
            border: none;
            height: 1px;
            background-color: var(--border-color);
        }
        
        .divider-text {
            padding: 0 15px;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .terms-container {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .terms-container input {
            width: auto;
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .terms-container label {
            font-size: 14px;
            font-weight: normal;
        }
        
        .terms-container a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .terms-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h1>Create Account</h1>
            <p>Please fill in the details to register</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
            <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
        <?php else: ?>
            <form class="registration-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($formData['username']); ?>" 
                        required 
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($formData['email']); ?>" 
                        required 
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                        required 
                        pattern="^\d{10}$" 
                        title="Please enter a 10-digit phone number"
                        autocomplete="tel"
                    >
                </div>
                
                <div class="form-group">
                    <label for="fullname">Full Name / Dealership Name</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?php echo htmlspecialchars($formData['fullname']); ?>" 
                        required 
                        autocomplete="name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>
                
                <div class="terms-container">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
                
                <a href="login.php" class="btn btn-secondary">Go to Login</a>
                <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Phone number validation
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                const phoneNumber = this.value.replace(/\D/g, ''); // Remove non-digits
                this.value = phoneNumber; // Update the input value
                
                // Update validation
                if (phoneNumber.length !== 10) {
                    this.setCustomValidity('Please enter a 10-digit phone number');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    </script>
</body>
</html>