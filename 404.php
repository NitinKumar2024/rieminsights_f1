<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 100px;
            text-align: center;
        }
        .error-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 50px 30px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #4e73df;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
            color: #5a5c69;
        }
        .btn-home {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 10px 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">Page Not Found</div>
            <p class="mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-home">Go to Homepage</a>
        </div>
    </div>
</body>
</html>