<?php
// Subscription API for handling premium subscriptions and coupons
session_start();
require_once 'config.php';

// Ensure we're always returning JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Handle PHP errors and convert them to JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'error_code' => $errno
    ];
    echo json_encode($response);
    exit;
}
set_error_handler('handleError');

// Handle uncaught exceptions
function handleException($exception) {
    $response = [
        'success' => false,
        'message' => 'Server exception: ' . $exception->getMessage(),
        'error_code' => $exception->getCode()
    ];
    echo json_encode($response);
    exit;
}
set_exception_handler('handleException');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'subscription' => null
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Connect to database
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Get action from request
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'status');
    
    // Handle different actions
    switch ($action) {
        // Check subscription status
        case 'status':
            $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $subscription = $result->fetch_assoc();
                
                // Check if subscription is still active
                $is_active = ($subscription['status'] === 'active' && strtotime($subscription['end_date']) > time());
                
                $response['subscription'] = [
                    'id' => $subscription['id'],
                    'plan_name' => $subscription['plan_name'],
                    'status' => $is_active ? 'active' : 'expired',
                    'start_date' => $subscription['start_date'],
                    'end_date' => $subscription['end_date']
                ];
                
                // If expired, update status in database
                if (!$is_active && $subscription['status'] === 'active') {
                    $update_stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
                    $update_stmt->bind_param("i", $subscription['id']);
                    $update_stmt->execute();
                }
            } else {
                $response['subscription'] = [
                    'status' => 'none',
                    'message' => 'No active subscription'
                ];
            }
            
            $response['success'] = true;
            break;
        
        // Validate coupon code
        case 'validate_coupon':
            // Get coupon code from request
            $coupon_code = isset($_GET['code']) ? trim($_GET['code']) : (isset($_POST['code']) ? trim($_POST['code']) : '');
            
            if (empty($coupon_code)) {
                $response['message'] = 'Coupon code is required';
                break;
            }
            
            $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->bind_param("s", $coupon_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $coupon = $result->fetch_assoc();
                
                // Check if coupon has expired
                $is_expired = ($coupon['expires_at'] !== null && strtotime($coupon['expires_at']) < time());
                
                // Check if coupon has reached usage limit
                $usage_limit_reached = ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']);
                
                if ($is_expired) {
                    $response['message'] = 'Coupon has expired';
                } else if ($usage_limit_reached) {
                    $response['message'] = 'Coupon usage limit reached';
                } else {
                    $response['success'] = true;
                    $response['coupon'] = [
                        'code' => $coupon['code'],
                        'description' => $coupon['description'],
                        'discount_percent' => $coupon['discount_percent'],
                        'duration_days' => $coupon['duration_days']
                    ];
                }
            } else {
                $response['message'] = 'Invalid coupon code';
            }
            break;
        
        // Activate subscription with coupon
        case 'activate':
            // Get coupon code from request
            $coupon_code = isset($_GET['code']) ? trim($_GET['code']) : (isset($_POST['code']) ? trim($_POST['code']) : '');
            
            if (empty($coupon_code)) {
                $response['message'] = 'Coupon code is required';
                break;
            }
            
            // Check if user already has an active subscription
            $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW()");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $response['message'] = 'You already have an active subscription';
                break;
            }
            
            // Validate coupon
            $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->bind_param("s", $coupon_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $coupon = $result->fetch_assoc();
                
                // Check if coupon has expired
                $is_expired = ($coupon['expires_at'] !== null && strtotime($coupon['expires_at']) < time());
                
                // Check if coupon has reached usage limit
                $usage_limit_reached = ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']);
                
                if ($is_expired) {
                    $response['message'] = 'Coupon has expired';
                } else if ($usage_limit_reached) {
                    $response['message'] = 'Coupon usage limit reached';
                } else {
                    // Calculate subscription end date based on coupon duration
                    $start_date = date('Y-m-d H:i:s');
                    $end_date = date('Y-m-d H:i:s', strtotime('+' . $coupon['duration_days'] . ' days'));
                    
                    // Insert new subscription
                    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan_name, status, start_date, end_date) VALUES (?, 'Premium', 'active', ?, ?)");
                    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
                    $stmt->execute();
                    
                    // Increment coupon usage count
                    $stmt = $conn->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
                    $stmt->bind_param("i", $coupon['id']);
                    $stmt->execute();
                    
                    $response['success'] = true;
                    $response['subscription'] = [
                        'plan_name' => 'Premium',
                        'status' => 'active',
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'message' => 'Subscription activated successfully'
                    ];
                }
            } else {
                $response['message'] = 'Invalid coupon code';
            }
            break;
        
        // Cancel subscription
        case 'cancel':
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Subscription cancelled successfully';
            } else {
                $response['message'] = 'No active subscription to cancel';
            }
            break;
        
        default:
            $response['message'] = 'Invalid action';
            break;
    }
    
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 