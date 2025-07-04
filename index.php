<?php
// 1. 세션 시작
session_start();

// 2. DB 연결 (이미 존재하는 db.php를 사용)
require_once 'PHP+DB.php'; // 예: includes/db.php 경로에 DB 연결 코드가 있음
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>대파 게시판 메인</title>
</head>
<body>
    <h2>📋대파 게시판 메인</h2>

    <!-- 3. 로그인 상태에 따른 화면 -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <p>
            👋 안녕하세요, <?php echo htmlspecialchars($_SESSION['user_name']); ?>님!
        </p>
        <p>
            <a href="create_post.php">✏️ 글 작성</a> |
            <a href="mypage.php">👤 마이페이지</a> |
            <a href="logout.php">🚪 로그아웃</a>
        </p>
    <?php else: ?>
        <p>
            <a href="login.php">🔐 로그인</a> |
            <a href="register.php">📝 회원가입</a>
        </p>
    <?php endif; ?>

    <hr>

    <!-- 4. 글 목록 출력 -->
    <h3>📝 글 목록</h3>
    <?php
    try {
        $sql = "SELECT post_id, post_title, created_at FROM post ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);

        while ($row = $stmt->fetch()) {
            $post_id = $row['post_id'];
            $title = htmlspecialchars($row['post_title']);
            $date = $row['created_at'];

            echo "<p><a href='view_post.php?id=$post_id'>$title</a> <small>($date)</small></p>";
        }
    } catch (PDOException $e) {
        echo "⚠️ 글 목록을 불러오는 데 실패했습니다: " . $e->getMessage();
    }
    ?>
</body>
</html>
