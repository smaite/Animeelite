<?php
// Comments API for handling comments
session_start();
require_once '../config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is logged in for actions that require authentication
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'get';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($method) {
        case 'GET':
            // Get comments
            $animeId = isset($_GET['anime_id']) ? (int)$_GET['anime_id'] : null;
            $episodeId = isset($_GET['episode_id']) ? (int)$_GET['episode_id'] : null;
            $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
            
            if (!$animeId && !$episodeId) {
                throw new Exception("Either anime_id or episode_id is required");
            }
            
            // Build query based on parameters
            $query = "
                SELECT c.id, c.content, c.created_at, c.parent_id,
                       u.id as user_id, u.username, u.display_name, u.avatar
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE 1=1
            ";
            $params = [];
            
            if ($animeId) {
                $query .= " AND c.anime_id = ?";
                $params[] = $animeId;
            }
            
            if ($episodeId) {
                $query .= " AND c.episode_id = ?";
                $params[] = $episodeId;
            }
            
            if ($parentId === null) {
                // Get top-level comments
                $query .= " AND c.parent_id IS NULL";
            } else {
                // Get replies to a specific comment
                $query .= " AND c.parent_id = ?";
                $params[] = $parentId;
            }
            
            $query .= " ORDER BY c.created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // For each comment, get the reply count
            foreach ($comments as &$comment) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE parent_id = ?");
                $stmt->execute([$comment['id']]);
                $comment['reply_count'] = $stmt->fetchColumn();
                
                // Format the display name
                $comment['display_name'] = $comment['display_name'] ?? $comment['username'];
            }
            
            $response['success'] = true;
            $response['data'] = $comments;
            break;
            
        case 'POST':
            // Add comment
            if (!isAuthenticated()) {
                $response['message'] = 'Authentication required';
                http_response_code(401);
                break;
            }
            
            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST; // Fallback to form data
            }
            
            $animeId = isset($data['anime_id']) ? (int)$data['anime_id'] : null;
            $episodeId = isset($data['episode_id']) ? (int)$data['episode_id'] : null;
            $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
            $content = isset($data['content']) ? trim($data['content']) : '';
            
            // Validate input
            if (empty($content)) {
                throw new Exception("Comment content cannot be empty");
            }
            
            if (!$animeId && !$episodeId) {
                throw new Exception("Either anime_id or episode_id is required");
            }
            
            // Insert comment
            $stmt = $pdo->prepare("
                INSERT INTO comments (user_id, anime_id, episode_id, parent_id, content)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $animeId,
                $episodeId,
                $parentId,
                $content
            ]);
            
            // Get the newly created comment with user data
            $commentId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                SELECT c.id, c.content, c.created_at, c.parent_id,
                       u.id as user_id, u.username, u.display_name, u.avatar
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format the display name
            $comment['display_name'] = $comment['display_name'] ?? $comment['username'];
            $comment['reply_count'] = 0;
            
            $response['success'] = true;
            $response['message'] = 'Comment added successfully';
            $response['data'] = $comment;
            break;
            
        case 'DELETE':
            // Delete comment
            if (!isAuthenticated()) {
                $response['message'] = 'Authentication required';
                http_response_code(401);
                break;
            }
            
            // Get comment ID
            $commentId = isset($_GET['id']) ? (int)$_GET['id'] : null;
            
            if (!$commentId) {
                throw new Exception("Comment ID is required");
            }
            
            // Check if user owns the comment or is admin
            $stmt = $pdo->prepare("
                SELECT c.*, u.role FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$comment) {
                throw new Exception("Comment not found");
            }
            
            if ($comment['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                $response['message'] = 'You do not have permission to delete this comment';
                http_response_code(403);
                break;
            }
            
            // Delete comment and its replies
            $pdo->beginTransaction();
            
            // First delete replies
            $stmt = $pdo->prepare("DELETE FROM comments WHERE parent_id = ?");
            $stmt->execute([$commentId]);
            
            // Then delete the comment itself
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'Comment deleted successfully';
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Return JSON response
echo json_encode($response);
?> 