<?php
// pages/api/blog_interact.php
// Handles AJAX actions: 'like' and 'comment' for blog posts.
// Returns JSON. Requires user to be logged in.

require_once '../../database/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action  = trim($_POST['action']  ?? '');
$post_id = (int)($_POST['post_id'] ?? 0);

if (!$action || !$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$db     = getDB();
$userId = currentUserId();

// -----------------------------------------------------------------
// ACTION: like — toggle like on/off
// -----------------------------------------------------------------
if ($action === 'like') {
    // Check if already liked
    $st = $db->prepare("SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?");
    $st->execute([$userId, $post_id]);
    $alreadyLiked = (bool)$st->fetchColumn();

    if ($alreadyLiked) {
        $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?")->execute([$userId, $post_id]);
        $liked = false;
    } else {
        $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)")->execute([$userId, $post_id]);
        $liked = true;
    }

    $count = (int)$db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?")->execute([$post_id]) ? 0 : 0;
    $st2   = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $st2->execute([$post_id]);
    $count = (int)$st2->fetchColumn();

    echo json_encode(['liked' => $liked, 'count' => $count]);
    exit;
}

// -----------------------------------------------------------------
// ACTION: comment — submit a new comment or reply
// -----------------------------------------------------------------
if ($action === 'comment') {
    $body      = trim($_POST['body']      ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;

    if (strlen($body) < 2 || strlen($body) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment must be between 2 and 2000 characters.']);
        exit;
    }

    // Verify post exists
    $stPost = $db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
    $stPost->execute([$post_id]);
    if (!$stPost->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    // If has parent, verify parent belongs to same post
    if ($parent_id) {
        $stParent = $db->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ?");
        $stParent->execute([$parent_id, $post_id]);
        if (!$stParent->fetch()) {
            $parent_id = null; // fallback to top-level
        }
    }

    $st = $db->prepare("
        INSERT INTO comments (post_id, user_id, parent_id, body)
        VALUES (?, ?, ?, ?)
    ");
    $st->execute([$post_id, $userId, $parent_id, $body]);
    $commentId = (int)$db->lastInsertId();

    // Fetch user info to render the HTML snippet
    $stUser = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stUser->execute([$userId]);
    $userName = htmlspecialchars($stUser->fetchColumn() ?: 'User');
    $initial  = strtoupper(mb_substr($userName, 0, 1));
    $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
    $timeAgo  = 'Just now';

    $isReply = $parent_id ? 'comment-reply' : '';

    // Build the rendered HTML snippet
    $html = <<<HTML
<div class="comment-item {$isReply}" id="comment-{$commentId}" data-id="{$commentId}">
  <div class="comment-avatar">{$initial}</div>
  <div class="comment-body">
    <div class="comment-meta">
      <span class="comment-author">{$userName}</span>
      <span class="comment-time">{$timeAgo}</span>
    </div>
    <div class="comment-text">{$safeBody}</div>
    <div class="comment-actions">
      <button class="btn-reply-toggle" data-comment-id="{$commentId}">Reply</button>
    </div>
  </div>
</div>
HTML;

    echo json_encode(['success' => true, 'html' => $html, 'comment_id' => $commentId]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
