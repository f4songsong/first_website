<?php
// 1. 세션 시작
session_start();

// 2. DB 연결
require_once 'PHP+DB.php';

// 3. 로그인 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    if (empty($title) || empty($body)) {
        $error = '제목과 내용을 모두 입력해 주세요.';
    } else {
        try {
            // 4. 새 post_id 계산
            $stmt = $pdo->query("SELECT MAX(post_id) AS max_id FROM post");
            $row = $stmt->fetch();
            $new_post_id = $row['max_id'] + 1;
            if ($new_post_id === null) {
                $new_post_id = 1;
            }

            // 5. post 테이블에 제목 삽입
            $stmt1 = $pdo->prepare("INSERT INTO post (post_id, post_title, users_id) VALUES (?, ?, ?)");
            $stmt1->execute([$new_post_id, $title, $_SESSION['user_id']]);

            // 6. content 테이블에 본문 삽입 (block_type은 'text')
            $stmt2 = $pdo->prepare("INSERT INTO content (block_type, content, post_id) VALUES ('text', ?, ?)");
            $stmt2->execute([$body, $new_post_id]);

            $success = '✅ 글이 성공적으로 작성되었습니다!';
        } catch (PDOException $e) {
            $error = 'DB 오류: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>글 작성</title>
</head>
<body>
    <h2>✏️ 글 작성</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="post" action="create_post.php">
        <p>제목: <input type="text" name="title" required></p>
        <p>내용:<br>
            <textarea name="body" rows="10" cols="50" required></textarea>
        </p>
        <p><button type="submit">작성하기</button></p>
    </form>

    <p><a href="index.php">← 메인으로 돌아가기</a></p>
</body>
</html>
