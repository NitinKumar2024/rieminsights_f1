<?php
// Database initialization script

// Database configuration
$db_host = 'localhost';
$db_user = 'root'; // Change this to your MySQL username
$db_pass = ''; // Change this to your MySQL password
$db_name = 'rieminsights_mvp';

// Connect to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if database exists
$db_exists = mysqli_select_db($conn, $db_name);

if (!$db_exists) {
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (mysqli_query($conn, $sql)) {
        echo "<p>Database created successfully.</p>";
    } else {
        die("Error creating database: " . mysqli_error($conn));
    }
    
    // Select the database
    mysqli_select_db($conn, $db_name);
} else {
    echo "<p>Database already exists.</p>";
    mysqli_select_db($conn, $db_name);
}

// Read SQL file
$sql_file = file_get_contents('database.sql');

// Split SQL file into individual statements
$statements = explode(';', $sql_file);

// Execute each statement
$success = true;
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!mysqli_query($conn, $statement)) {
            echo "<p>Error executing statement: " . mysqli_error($conn) . "</p>";
            echo "<p>Statement: $statement</p>";
            $success = false;
        }
    }
}

if ($success) {
    echo "<p>Database initialized successfully.</p>";
    echo "<p>You can now <a href='index.php'>access the application</a>.</p>";
} else {
    echo "<p>There were errors initializing the database. Please check the error messages above.</p>";
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RiemInsights - Installation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding: 50px 0;
            background-color: #f8f9fa;
        }
        .install-container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #4e73df;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <h1>RiemInsights</h1>
                <p>AI-Powered Data Analytics</p>
            </div>
            
            <h2>Installation</h2>
            <hr>
            
            <div class="alert alert-info">
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Make sure your web server (Apache) and MySQL server are running.</li>
                    <li>Update the database configuration in <code>config.php</code> if needed.</li>
                    <li>Delete this file (<code>install.php</code>) after successful installation for security reasons.</li>
                </ol>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary">Go to Application</a>
            </div>
        </div>
    </div>
</body>
</html>