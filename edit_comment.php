<?php
session_start();
require_once 'PHP+DB.php';

// 1. 댓글 ID 검사
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['comment_id']) || !is_numeric($_GET['comment_id']) || (int)$_GET['comment_id'] <= 0) {
        exit("잘못된 댓글 요청입니다.");
    }
    $comment_id = (int)$_GET['comment_id'];
} else {
    if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id']) || (int)$_POST['comment_id'] <= 0) {
        exit("잘못된 댓글 요청입니다.");
    }
    $comment_id = (int)$_POST['comment_id'];
}

// 2. 댓글 데이터 조회
$stmt = $pdo->prepare("SELECT * FROM comment WHERE comment_id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    exit("존재하지 않는 댓글입니다.");
}

// 3. 로그인 사용자와 댓글 작성자 확인
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $comment['users_id']) {
    exit("수정 권한이 없습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 4. 수정 처리
    $new_content = trim($_POST['content'] ?? '');

    if ($new_content === '') {
        exit("댓글 내용을 입력해주세요.");
    }

    $stmt = $pdo->prepare("UPDATE comment SET content = ?, updated_at = NOW() WHERE comment_id = ?");
    $stmt->execute([$new_content, $comment_id]);

    // 5. 수정 완료 후 게시글 보기로 리다이렉트
    header("Location: view_post.php?id=" . urlencode($comment['post_id']));
    exit;
}

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>댓글 수정</title>
</head>
<body>

<h2>댓글 수정</h2>

<form action="edit_comment.php" method="post">
    <input type="hidden" name="comment_id" value="<?= htmlspecialchars($comment['comment_id']) ?>">
    <p>
        <textarea name="content" rows="5" cols="50" required><?= htmlspecialchars($comment['content']) ?></textarea>
    </p>
    <button type="submit">수정 완료</button>
</form>

<p><a href="view_post.php?id=<?= htmlspecialchars($comment['post_id']) ?>">취소</a></p>

</body>
</html>
