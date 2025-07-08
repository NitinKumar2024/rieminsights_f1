<?php
/**
 * RiemInsights - File Handler API
 * Handles file upload, storage, and retrieval operations
 */

// Include configuration and database connection
require_once '../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Handle different actions
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'save_file':
        handleSaveFile($user_id);
        break;
    case 'get_file':
        handleGetFile($user_id);
        break;
    case 'list_files':
        handleListFiles($user_id);
        break;
    case 'delete_file':
        handleDeleteFile($user_id);
        break;
    default:
        // Invalid action
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

/**
 * Handle saving a file
 * @param int $user_id The user ID
 */
function handleSaveFile($user_id) {
    global $conn;
    
    // Check if file name and data are provided
    if (!isset($_POST['file_name']) || !isset($_POST['file_data'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Missing file name or data'
        ]);
        return;
    }
    
    // Get file name and data
    $file_name = sanitize_input($_POST['file_name']);
    $file_data = $_POST['file_data']; // This is already a JSON string
    $column_headers = isset($_POST['column_headers']) ? $_POST['column_headers'] : null;
    $data_preview = isset($_POST['data_preview']) ? $_POST['data_preview'] : null;
    
    // Generate a unique file ID
    $file_id = uniqid('file_');
    
    // Get file size
    $file_size = strlen($file_data);
    
    // Get file type from extension
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $file_type = strtolower($file_extension);
    
    // Count rows and columns
    $data_array = json_decode($file_data, true);
    $total_rows = count($data_array);
    $total_columns = 0;
    if ($total_rows > 0 && isset($data_array[0])) {
        $total_columns = count((array)$data_array[0]);
    }
    
    // Prepare the query
    $stmt = $conn->prepare("INSERT INTO uploaded_files (filename, user_id, original_name, file_path, file_type, file_size, total_rows, total_columns, column_headers, data_preview, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $file_path = 'uploads/' . $user_id . '/' . $file_id . '.json';
    $stmt->bind_param("sisssiiiss", $file_id, $user_id, $file_name, $file_path, $file_type, $file_size, $total_rows, $total_columns, $column_headers, $data_preview);
    
    // Execute the query
    if ($stmt->execute()) {
        // Save the file data to disk
        $upload_dir = '../uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Create user directory if it doesn't exist
        $user_dir = $upload_dir . $user_id . '/';
        if (!file_exists($user_dir)) {
            mkdir($user_dir, 0755, true);
        }
        
        // Save the file
        $file_path = $user_dir . $file_id . '.json';
        file_put_contents($file_path, $file_data);
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'File saved successfully',
            'file_id' => $file_id
        ]);
    } else {
        // Return error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error saving file: ' . $stmt->error
        ]);
    }
    
    // Close the statement
    $stmt->close();
}

/**
 * Handle retrieving a file
 * @param int $user_id The user ID
 */
function handleGetFile($user_id) {
    global $conn;
    
    // Check if file ID is provided
    if (!isset($_POST['file_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Missing file ID'
        ]);
        return;
    }
    
    // Get file ID
    $file_id = sanitize_input($_POST['file_id']);
    
    // Prepare the query to check if the file belongs to the user
    $stmt = $conn->prepare("SELECT filename, file_type FROM uploaded_files WHERE filename = ? AND user_id = ?");
    $stmt->bind_param("si", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // File not found or doesn't belong to the user
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        $stmt->close();
        return;
    }
    
    // Get file details
    $file = $result->fetch_assoc();
    $stmt->close();
    
    // Get file path
    $file_path = '../uploads/' . $user_id . '/' . $file_id . '.json';
    
    if (!file_exists($file_path)) {
        // File not found on disk
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'File data not found'
        ]);
        return;
    }
    
    // Read file data
    $file_data = file_get_contents($file_path);
    
    // Return file data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'file_name' => $file['filename'],
        'file_type' => $file['file_type'],
        'file_data' => json_decode($file_data)
    ]);
}

/**
 * Handle listing user's files
 * @param int $user_id The user ID
 */
function handleListFiles($user_id) {
    global $conn;
    
    // Prepare the query
    $stmt = $conn->prepare("SELECT filename, file_type, file_size, upload_date FROM uploaded_files WHERE user_id = ? ORDER BY upload_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all files
    $files = [];
    while ($row = $result->fetch_assoc()) {
        // Format file size
        $size_kb = round($row['file_size'] / 1024, 2);
        $size_mb = round($size_kb / 1024, 2);
        
        if ($size_mb >= 1) {
            $formatted_size = $size_mb . ' MB';
        } else {
            $formatted_size = $size_kb . ' KB';
        }
        
        // Format date
        $upload_date = new DateTime($row['upload_date']);
        $formatted_date = $upload_date->format('M d, Y h:i A');
        
        // Add to files array
        $files[] = [
            'file_id' => $row['filename'],
            'file_name' => $row['filename'],
            'file_type' => $row['file_type'],
            'file_size' => $formatted_size,
            'upload_date' => $formatted_date
        ];
    }
    
    // Close the statement
    $stmt->close();
    
    // Return files
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
}

/**
 * Handle deleting a file
 * @param int $user_id The user ID
 */
function handleDeleteFile($user_id) {
    global $conn;
    
    // Check if file ID is provided
    if (!isset($_POST['file_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Missing file ID'
        ]);
        return;
    }
    
    // Get file ID
    $file_id = sanitize_input($_POST['file_id']);
    
    // Prepare the query to check if the file belongs to the user
    $stmt = $conn->prepare("SELECT filename FROM uploaded_files WHERE filename = ? AND user_id = ?");
    $stmt->bind_param("si", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // File not found or doesn't belong to the user
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        $stmt->close();
        return;
    }
    
    $stmt->close();
    
    // Delete file from database
    $stmt = $conn->prepare("DELETE FROM uploaded_files WHERE filename = ? AND user_id = ?");
    $stmt->bind_param("si", $file_id, $user_id);
    
    if ($stmt->execute()) {
        // Delete file from disk
        $file_path = '../uploads/' . $user_id . '/' . $file_id . '.json';
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    } else {
        // Return error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting file: ' . $stmt->error
        ]);
    }
    
    // Close the statement
    $stmt->close();
}