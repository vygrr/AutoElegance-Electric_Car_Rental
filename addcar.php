<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Include connection script
require_once('connection.php');

// Debug variables
$debug_info = [];
$submission_attempted = false;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submission_attempted = true;
    $debug_info[] = "Form submission detected";
    
    // Retrieve form data
    $user_id = $_SESSION['user_id'];
    $debug_info[] = "User ID: " . $user_id;
    
    // Check if all required fields are present
    $required_fields = ['descr', 'modelname', 'city', 'capacity', 'price', 'connector', 'drange'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $debug_info[] = "Missing required fields: " . implode(', ', $missing_fields);
        $error_message = "Please fill in all required fields.";
    } else {
        $debug_info[] = "All required form fields present";
        
        // Safely get form data
        $descr = mysqli_real_escape_string($conn, $_POST['descr']);
        $modelname = mysqli_real_escape_string($conn, $_POST['modelname']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $capacity = mysqli_real_escape_string($conn, $_POST['capacity']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $connector = mysqli_real_escape_string($conn, $_POST['connector']);
        $drange = mysqli_real_escape_string($conn, $_POST['drange']);
        
        $debug_info[] = "Form data escaped for SQL";
        
        // File upload checking
        if (!isset($_FILES['image'])) {
            $debug_info[] = "No file uploaded";
            $error_message = "No image file was uploaded.";
        } else {
            $debug_info[] = "File upload detected";
            $debug_info[] = "File upload error code: " . $_FILES['image']['error'];
            
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                // Translate error codes to messages
                switch($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $error_message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error_message = "The uploaded file was only partially uploaded";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_message = "No file was uploaded";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error_message = "Missing a temporary folder";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error_message = "Failed to write file to disk";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $error_message = "File upload stopped by extension";
                        break;
                    default:
                        $error_message = "Unknown upload error";
                        break;
                }
            } else {
                $file_name = $_FILES['image']['name'];
                $tempname = $_FILES['image']['tmp_name'];
                $folder = "images/".$file_name;
                
                $debug_info[] = "File name: " . $file_name;
                $debug_info[] = "Temp location: " . $tempname;
                $debug_info[] = "Target folder: " . $folder;
                
                // Check if images directory exists and is writable
                if (!is_dir("images/")) {
                    $debug_info[] = "Images directory does not exist";
                    $error_message = "Images directory does not exist.";
                } elseif (!is_writable("images/")) {
                    $debug_info[] = "Images directory is not writable";
                    $error_message = "Images directory is not writable.";
                } else {
                    $debug_info[] = "Images directory exists and is writable";
                    
                    // Construct SQL query
                    $sql = "INSERT INTO vehicles (hid, descr, modelname, city, capacity, connector, drange, price, file, availability) 
                            VALUES ('$user_id', '$descr', '$modelname', '$city', '$capacity', '$connector', '$drange', '$price', '$file_name', 1)";
                    
                    $debug_info[] = "SQL Query: " . $sql;
                    
                    // Try executing the query
                    if ($conn->query($sql)) {
                        $debug_info[] = "Database insert successful";
                        
                        // Try moving the uploaded file
                        if (move_uploaded_file($tempname, $folder)) {
                            $debug_info[] = "File upload successful";
                            $_SESSION['success_message'] = "New car added successfully!";
                            
                            // Store debug info in session before redirect
                            $_SESSION['debug_info'] = $debug_info;
                            
                            header("Location: home.php");
                            exit;
                        } else {
                            $debug_info[] = "File upload failed. Error: " . error_get_last()['message'];
                            $error_message = "Error moving uploaded file. Please check folder permissions.";
                        }
                    } else {
                        $debug_info[] = "Database error: " . $conn->error;
                        $error_message = "Database error: " . $conn->error;
                    }
                }
            }
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
    <title>List you Car for Rent | AutoElegance</title>
    <link rel="stylesheet" href="./style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        .page-title {
            color: #2c3e50;
            font-size: 18px;
            margin: 15px 0;
            padding-left: 20px;
        }

        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            max-width: 700px;
            width: 100%;
            margin: 0 auto 30px;
            box-sizing: border-box;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: 90px;
            resize: vertical;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-col {
            flex: 1;
        }

        .file-input-container {
            margin: 15px 0;
        }

        .file-input-label {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .file-input-label:hover {
            background-color: #2980b9;
        }

        input[type="file"] {
            display: none;
        }

        .file-name {
            margin-top: 6px;
            font-size: 13px;
            color: #666;
        }

        .submit-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #27ae60;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .main-content {
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h2 class="page-title">List Your Car Details</h2>
        
        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($submission_attempted): ?>
                <div class="debug-info">
                    <h4>Debug Information:</h4>
                    <ul>
                        <?php foreach ($debug_info as $info): ?>
                            <li><?php echo htmlspecialchars($info); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="modelname">Car Model Name</label>
                    <input type="text" id="modelname" name="modelname" placeholder="e.g., Tesla Model 3" required>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="price">Price per Day (₹)</label>
                            <input type="text" id="price" name="price" placeholder="e.g., 2500" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="drange">Driving Range (km)</label>
                            <input type="text" id="drange" name="drange" placeholder="e.g., 350" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descr">Car Description</label>
                    <textarea id="descr" name="descr" placeholder="Describe your car's features and condition" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="connector">Connector Type</label>
                            <select id="connector" name="connector">
                                <option value="IEC60309 Mennekes">IEC60309 Mennekes</option>
                                <option value="IEC62196-2">IEC62196-2</option>
                                <option value="GB/T">GB/T</option>
                                <option value="CCS2">CCS2</option>
                                <option value="CHAdeM0">CHAdeM0</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="capacity">Seating Capacity</label>
                            <select id="capacity" name="capacity">
                                <option value="2">2 Seats</option>
                                <option value="4">4 Seats</option>
                                <option value="5">5 Seats</option>
                                <option value="7">7 Seats</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="city">Available Location</label>
                    <select id="city" name="city">
                        <option value="Belapur">Belapur</option>
                        <option value="Nerul">Nerul</option>
                        <option value="Sanpada">Sanpada</option>
                        <option value="Vashi">Vashi</option>
                        <option value="Kopar Kharaine">Kopar Kharaine</option>
                        <option value="Ghansoli">Ghansoli</option>
                        <option value="Airoli">Airoli</option>
                    </select>
                </div>

                <div class="file-input-container">
                    <label for="imageInput" class="file-input-label">Upload Car Image</label>
                    <input type="file" name="image" id="imageInput" accept=".jpg, .jpeg, .png" required>
                    <div class="file-name" id="fileName">No file selected</div>
                </div>

                <button type="submit" class="submit-btn">Add Your Car</button>
            </form>
        </div>
    </div>

    <script>
        // Show selected filename
        document.getElementById('imageInput').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file selected';
            document.getElementById('fileName').textContent = fileName;
        });
    </script>
</body>
</html>