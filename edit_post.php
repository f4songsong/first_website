<?php
session_start();
require_once 'PHP+DB.php';

// 1. 요청 메서드에 따라 post_id 받기
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
        exit("잘못된 게시글 요청입니다.");
    }
    $post_id = (int)$_GET['id'];
} else {
    if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id']) || (int)$_POST['post_id'] <= 0) {
        exit("잘못된 게시글 요청입니다.");
    }
    $post_id = (int)$_POST['post_id'];
}

// 2. 게시글 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || $_SESSION['user_id'] !== $post['users_id']) {
    exit("권한이 없거나 게시글이 존재하지 않습니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. POST 요청 처리 - 수정 처리

    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    if (empty($title) || empty($body)) {
        exit("제목과 내용을 모두 입력해주세요.");
    }

    // 4. 게시글 제목 수정
    $stmt = $pdo->prepare("UPDATE post SET post_title = ?, updated_at = NOW() WHERE post_id = ?");
    $stmt->execute([$title, $post_id]);

    // 5. content 테이블에서 block_type='text'인 본문 업데이트 (최초 1개만 수정)
    $stmt_check = $pdo->prepare("SELECT content_id FROM content WHERE post_id = ? AND block_type = 'text' ORDER BY content_id ASC LIMIT 1");
    $stmt_check->execute([$post_id]);
    $content_row = $stmt_check->fetch();

    if ($content_row) {
        // 기존 본문 업데이트
        $stmt_update = $pdo->prepare("UPDATE content SET content = ?, updated_at = NOW() WHERE content_id = ?");
        $stmt_update->execute([$body, $content_row['content_id']]);
    } else {
        // 본문이 없으면 새로 삽입
        $stmt_insert = $pdo->prepare("INSERT INTO content (block_type, content, post_id, created_at, updated_at) VALUES ('text', ?, ?, NOW(), NOW())");
        $stmt_insert->execute([$body, $post_id]);
    }

    // 6. 기존 파일 삭제 처리
    if (!empty($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $file_id) {
            $stmt_file = $pdo->prepare("SELECT saved_name FROM file WHERE file_id = ? AND post_id = ?");
            $stmt_file->execute([$file_id, $post_id]);
            $file = $stmt_file->fetch();

            if ($file) {
                $path = __DIR__ . "/uploads/" . $file['saved_name'];
                if (file_exists($path)) unlink($path);
                $stmt_del = $pdo->prepare("DELETE FROM file WHERE file_id = ?");
                $stmt_del->execute([$file_id]);
            }
        }
    }

    // 7. 새 파일 업로드 처리
    if (!empty($_FILES['new_files']['name'][0])) {
        foreach ($_FILES['new_files']['tmp_name'] as $i => $tmp_name) {
            $name = $_FILES['new_files']['name'][$i];
            $tmp = $_FILES['new_files']['tmp_name'][$i];
            $error = $_FILES['new_files']['error'][$i];

            if ($error === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $saved_name = uniqid('', true) . '.' . $ext;
                $upload_path = __DIR__ . '/uploads/' . $saved_name;

                if (move_uploaded_file($tmp, $upload_path)) {
                    $stmt_file_insert = $pdo->prepare("INSERT INTO file (file_name, saved_name, post_id) VALUES (?, ?, ?)");
                    $stmt_file_insert->execute([$name, $saved_name, $post_id]);
                }
            }
        }
    }

    // 수정 완료 후 글 보기 페이지로 리다이렉트
    header("Location: view_post.php?id=$post_id");
    exit;
}

// 8. GET 요청 시 기존 본문 가져오기 (수정 폼 출력용)
$stmt2 = $pdo->prepare("SELECT content FROM content WHERE post_id = ? AND block_type = 'text' ORDER BY content_id ASC LIMIT 1");
$stmt2->execute([$post_id]);
$content_row = $stmt2->fetch();
$body = $content_row ? $content_row['content'] : '';

// 9. 기존 첨부파일 가져오기
$stmt_file = $pdo->prepare("SELECT * FROM file WHERE post_id = ?");
$stmt_file->execute([$post_id]);
$files = $stmt_file->fetchAll();

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시글 수정</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>게시글 수정</h2>

<form action="edit_post.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['post_id']) ?>">

    <p>제목: <input type="text" name="title" value="<?= htmlspecialchars($post['post_title']) ?>" required></p>

    <p>내용:<br>
        <textarea name="body" rows="10" cols="50" required><?= htmlspecialchars($body) ?></textarea>
    </p>

    <h3>기존 첨부 파일</h3>
    <?php if ($files): ?>
        <?php foreach ($files as $file): ?>
            <div>
                <?= htmlspecialchars($file['file_name']) ?>
                <label>
                    <input type="checkbox" name="delete_files[]" value="<?= $file['file_id'] ?>"> 삭제
                </label>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>첨부 파일이 없습니다.</p>
    <?php endif; ?>

    <h3>새 파일 추가</h3>
    <input type="file" name="new_files[]" multiple>

    <br><br>
    <button type="submit">수정 완료</button>
</form>

<p><a href="view_post.php?id=<?= htmlspecialchars($post['post_id']) ?>">취소</a></p>

</body>
</html>
