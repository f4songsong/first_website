<?php
session_start();
require_once 'PHP+DB.php';

// 1. URL 파라미터 'id' 검사
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "잘못된 접근입니다.";
    exit;
}

$post_id = (int)$_GET['id'];

try {
    // 2. post 테이블에서 제목 가져오기
    $stmt = $pdo->prepare("SELECT post_title FROM post WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo "존재하지 않는 글입니다.";
        exit;
    }

    // 3. content 테이블에서 본문 내용 가져오기 (block_type='text'만)
    $stmt2 = $pdo->prepare("SELECT content FROM content WHERE post_id = ? AND block_type = 'text' ORDER BY content_id ASC");
    $stmt2->execute([$post_id]);
    $contents = $stmt2->fetchAll();

} catch (PDOException $e) {
    echo "DB 오류: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['post_title']); ?></title>
</head>
<body>
    <h2><?php echo htmlspecialchars($post['post_title']); ?></h2>

    <?php if ($contents): ?>
        <?php foreach ($contents as $content): ?>
            <p><?php echo nl2br(htmlspecialchars($content['content'])); ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <p>내용이 없습니다.</p>
    <?php endif; ?>

    <p><a href="index.php">← 목록으로 돌아가기</a></p>
</body>
</html>
