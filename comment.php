<?php
session_start();
require_once 'PHP+DB.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }

    $comment = trim($_POST['comment'] ?? '');
    $post_id = intval($_POST['post_id'] ?? 0);

    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => '댓글 내용을 입력해주세요.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comment (content, users_id, post_id) VALUES (?, ?, ?)");
        $stmt->execute([$comment, $_SESSION['user_id'], $post_id]);
        echo json_encode(['success' => true, 'message' => '✅ 댓글이 등록되었습니다.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
}
