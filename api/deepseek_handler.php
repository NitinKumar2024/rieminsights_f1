<?php
/**
 * RiemInsights - DeepSeek API Handler
 * Handles interactions with the DeepSeek API for data analysis
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

// Get request data
$request_data = json_decode(file_get_contents('php://input'), true);

// Check if request data is valid
if (!$request_data || !isset($request_data['action'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

// Handle different actions
switch ($request_data['action']) {
    case 'analyze_data':
        handleAnalyzeData($user_id, $request_data);
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
 * Handle data analysis request
 * @param int $user_id The user ID
 * @param array $request_data The request data
 */
function handleAnalyzeData($user_id, $request_data) {
    global $conn;
    
    // Check if required fields are provided
    if (!isset($request_data['question']) || !isset($request_data['data'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        return;
    }
    
    // Get user's token balance
    $stmt = $conn->prepare("SELECT u.tokens_remaining, p.plan_name FROM users u JOIN plans p ON u.plan_type = p.plan_type WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        $stmt->close();
        return;
    }
    
    // Get user data
    $user_data = $result->fetch_assoc();
    $tokens_remaining = $user_data['tokens_remaining'];
    $plan_name = $user_data['plan_name'];
    $stmt->close();
    
    // Check if user has enough tokens
    if ($tokens_remaining <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient tokens. Please upgrade your plan.',
            'tokens_remaining' => 0
        ]);
        return;
    }
    
    // Get question and data
    $question = $request_data['question'];
    $data = json_decode($request_data['data'], true);
    $file_name = isset($request_data['file_name']) ? $request_data['file_name'] : 'Untitled';
    
    // Prepare data for DeepSeek API
    $deepseek_data = [
        'model' => 'deepseek-chat',
        'messages' => [
            [
                'role' => 'system',
                'content' => "You are a data analysis assistant. You will be provided with data from a CSV or Excel file and a question about the data. Your task is to analyze the data and provide insights, visualizations, and answers to the question. Be concise, accurate, and helpful."
            ],
            [
                'role' => 'user',
                'content' => "I have uploaded a file named '{$file_name}'. Here is a preview of the data:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\nMy question is: {$question}\n\nPlease analyze this data and answer my question. If appropriate, suggest a visualization that would help understand the data better."
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];
    
    // Call DeepSeek API
    $response = callDeepSeekAPI($deepseek_data);
    
    // Check if API call was successful
    if (!$response['success']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error calling DeepSeek API: ' . $response['message']
        ]);
        return;
    }
    
    // Get response content
    $ai_response = $response['data']['choices'][0]['message']['content'];
    
    // Calculate tokens used
    $tokens_used = $response['data']['usage']['total_tokens'];
    
    // Update user's token balance
    $new_tokens_remaining = max(0, $tokens_remaining - $tokens_used);
    $stmt = $conn->prepare("UPDATE users SET tokens_remaining = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_tokens_remaining, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Log token usage
    $stmt = $conn->prepare("INSERT INTO token_usage (user_id, tokens_used, action_type, timestamp) VALUES (?, ?, 'data_analysis', NOW())");
    $stmt->bind_param("ii", $user_id, $tokens_used);
    $stmt->execute();
    $stmt->close();
    
    // Check for visualization code in the response
    $visualization = extractVisualizationCode($ai_response);
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'response' => $ai_response,
        'tokens_used' => $tokens_used,
        'tokens_remaining' => $new_tokens_remaining,
        'visualization' => $visualization
    ]);
}

/**
 * Call the DeepSeek API
 * @param array $data The data to send to the API
 * @return array The API response
 */
function callDeepSeekAPI($data) {
    // DeepSeek API endpoint
    $api_endpoint = 'https://api.deepseek.com/v1/chat/completions';
    
    // DeepSeek API key
    $api_key = DEEPSEEK_API_KEY;
    
    // Check if API key is defined and not empty
    if (!defined('DEEPSEEK_API_KEY') || empty($api_key)) {
        return [
            'success' => false,
            'message' => 'DeepSeek API key is not configured properly'
        ];
    }
    
    try {
        // Initialize cURL session
        $ch = curl_init($api_endpoint);
        
        if ($ch === false) {
            return [
                'success' => false,
                'message' => 'Failed to initialize cURL'
            ];
        }
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // Set timeout to 30 seconds
        
        // Execute cURL session
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_message = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        // Get HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL session
        curl_close($ch);
        
        // Check if response is empty
        if (empty($response)) {
            return [
                'success' => false,
                'message' => 'Empty response from API (HTTP code: ' . $http_code . ')'
            ];
        }
        
        // Decode response
        $response_data = json_decode($response, true);
        
        // Check if response is valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Check if response starts with HTML (PHP error)
            if (substr($response, 0, 1) === '<') {
                return [
                    'success' => false,
                    'message' => 'Server returned HTML instead of JSON. This usually indicates a PHP error. First 100 characters: ' . substr($response, 0, 200) . '...'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid JSON response: ' . json_last_error_msg() . '. Response: ' . substr($response, 0, 100) . '...'
            ];
        }
        
        // Check if response is successful
        if ($http_code !== 200 || !$response_data) {
            $error_message = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'Unknown API error (HTTP code: ' . $http_code . ')';
            return [
                'success' => false,
                'message' => 'API error: ' . $error_message
            ];
        }
        
        // Return success response
        return [
            'success' => true,
            'data' => $response_data
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Extract visualization code from AI response
 * @param string $response The AI response
 * @return array|null The visualization configuration or null if none found
 */
function extractVisualizationCode($response) {
    // Check if response contains visualization code
    if (preg_match('/```javascript\s*\n(.+?)\n```/s', $response, $matches) || 
        preg_match('/```js\s*\n(.+?)\n```/s', $response, $matches)) {
        
        $code = $matches[1];
        
        // Check if code contains Chart.js configuration
        if (strpos($code, 'new Chart') !== false || strpos($code, 'Chart.') !== false) {
            // Extract chart configuration
            if (preg_match('/\{\s*type:\s*[\'"](\w+)[\'"]/s', $code, $type_match) && 
                preg_match('/data:\s*(\{.+?\})/s', $code, $data_match) && 
                preg_match('/options:\s*(\{.+?\})/s', $code, $options_match)) {
                
                // Clean up the matches to make them valid JSON
                $type = $type_match[1];
                
                // Convert JavaScript object to JSON
                $data_str = preg_replace('/([\w]+)\s*:/s', '"$1":', $data_match[1]);
                $data_str = preg_replace('/[\'](\w+)[\']\s*:/s', '"$1":', $data_str);
                $data_str = preg_replace('/\`(.+?)\`/s', '"$1"', $data_str);
                
                $options_str = preg_replace('/([\w]+)\s*:/s', '"$1":', $options_match[1]);
                $options_str = preg_replace('/[\'](\w+)[\']\s*:/s', '"$1":', $options_str);
                $options_str = preg_replace('/\`(.+?)\`/s', '"$1"', $options_str);
                
                // Try to decode the data and options
                try {
                    $data = json_decode($data_str, true);
                    $options = json_decode($options_str, true);
                    
                    // If successful, return the chart configuration
                    if ($data && $options) {
                        return [
                            'type' => $type,
                            'data' => $data,
                            'options' => $options
                        ];
                    }
                } catch (Exception $e) {
                    // If there's an error, return null
                    return null;
                }
            }
        }
    }
    
    return null;
}