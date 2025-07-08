<?php
session_start();
require_once 'PHP+DB.php';

if (!isset($_SESSION['user_id'])) {
    echo "로그인 후 이용 가능합니다.";
    exit;
}

if (!isset($_GET['comment_id']) || !is_numeric($_GET['comment_id'])) {
    echo "잘못된 접근입니다.";
    exit;
}

$comment_id = (int)$_GET['comment_id'];
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

try {
    // 댓글 작성자인지 확인
    $stmt = $pdo->prepare("SELECT users_id FROM comment WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        echo "존재하지 않는 댓글입니다.";
        exit;
    }

    if ($comment['users_id'] !== $_SESSION['user_id']) {
        echo "삭제 권한이 없습니다.";
        exit;
    }

    // 삭제 (논리 삭제: deleted_at 설정)
    $stmt = $pdo->prepare("UPDATE comment SET deleted_at = NOW() WHERE comment_id = ?");
    $stmt->execute([$comment_id]);

    header("Location: view_post.php?id=$post_id");
    exit;

} catch (PDOException $e) {
    echo "삭제 중 오류 발생: " . htmlspecialchars($e->getMessage());
}
?>
