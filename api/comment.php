<?php
/**
 * TRENDYPOST - Comment API
 * Handles comment submissions via AJAX
 */

// Allow cross-origin requests for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Define TRENDYPOST if not defined
if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
}

// Load required files
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';
require_once INCLUDES_PATH . 'Models.php';
require_once INCLUDES_PATH . 'Auth.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

// If no JSON data, try regular POST
if (empty($postData)) {
    $postData = $_POST;
}

// Validate CSRF token
if (!isset($postData['_token']) || !verify_csrf($postData['_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Get comment data
$postId = intval($postData['post_id'] ?? 0);
$content = trim($postData['content'] ?? '');
$parentId = intval($postData['parent_id'] ?? 0);
$authorName = trim($postData['author_name'] ?? '');
$authorEmail = trim($postData['author_email'] ?? '');
$authorWebsite = trim($postData['author_website'] ?? '');

// Validate required fields
if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required.']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Comment content is required.']);
    exit;
}

// Check if post exists and allows comments
$post = Database::fetchOne("SELECT id, allow_comments FROM posts WHERE id = ? AND status = 'published'", [$postId]);
if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
}

if (!$post['allow_comments']) {
    echo json_encode(['success' => false, 'message' => 'Comments are disabled for this post.']);
    exit;
}

// Check if user is logged in
$userId = null;
$displayName = '';
$userEmail = '';

if (Auth::check()) {
    $user = Auth::user();
    $userId = $user['id'];
    $displayName = $user['display_name'] ?? $user['username'] ?? 'User';
    $userEmail = $user['email'] ?? '';
} else {
    // For guest comments, validate name and email
    if (empty($authorName)) {
        echo json_encode(['success' => false, 'message' => 'Name is required for guest comments.']);
        exit;
    }
    if (empty($authorEmail) || !is_valid_email($authorEmail)) {
        echo json_encode(['success' => false, 'message' => 'Valid email is required for guest comments.']);
        exit;
    }
    $displayName = $authorName;
    $userEmail = $authorEmail;
}

// Check if moderation is enabled
$moderation = Setting::get('comment_moderation', 1);
$status = $moderation ? 'pending' : 'approved';

// Prepare comment data
$commentData = [
    'post_id' => $postId,
    'parent_id' => $parentId ?: null,
    'user_id' => $userId,
    'author_name' => sanitize($displayName),
    'author_email' => sanitize($userEmail),
    'author_website' => sanitize($authorWebsite),
    'content' => sanitize($content),
    'author_ip' => client_ip(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'status' => $status
];

try {
    // Insert comment
    $commentId = Database::insert('comments', $commentData);
    
    if (!$commentId) {
        echo json_encode(['success' => false, 'message' => 'Failed to save comment. Please try again.']);
        exit;
    }
    
    // Update comment count on post
    Database::query(
        "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved') WHERE id = ?",
        [$postId, $postId]
    );
    
    // Return success response
    $response = [
        'success' => true,
        'message' => $moderation ? 'Your comment is awaiting moderation.' : 'Comment posted successfully!',
        'comment' => [
            'id' => $commentId,
            'author_name' => $displayName,
            'content' => nl2br(htmlspecialchars($content)),
            'created_at' => 'Just now',
            'status' => $status
        ]
    ];
    
    if ($moderation) {
        $response['moderation'] = true;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Comment Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while posting your comment. Please try again.'
    ]);
}