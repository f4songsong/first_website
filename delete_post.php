<?php
session_start();
require_once 'PHP+DB.php';

if (!isset($_SESSION['user_id'])) {
    echo "로그인 후 이용 가능합니다.";
    exit;
}

if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo "잘못된 접근입니다.";
    exit;
}

$post_id = (int)$_GET['post_id'];

try {
    // 1. 작성자인지 확인
    $stmt = $pdo->prepare("SELECT users_id FROM post WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo "존재하지 않는 게시글입니다.";
        exit;
    }

    if ($post['users_id'] !== $_SESSION['user_id']) {
        echo "삭제 권한이 없습니다.";
        exit;
    }

    // 2. 트랜잭션 시작
    $pdo->beginTransaction();

    // 3. 파일 삭제 (서버에서)
    $stmt = $pdo->prepare("SELECT saved_name FROM file WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $files = $stmt->fetchAll();
    foreach ($files as $file) {
        $file_path = __DIR__ . '/uploads/' . $file['saved_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // 4. DB 삭제
    $pdo->prepare("DELETE FROM file WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM comment WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM content WHERE post_id = ?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM post WHERE post_id = ?")->execute([$post_id]);

    // 5. 완료
    $pdo->commit();
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "삭제 중 오류: " . htmlspecialchars($e->getMessage());
}
?>
