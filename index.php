<?php
// 1. 세션 시작
session_start();

// 2. DB 연결 (이미 존재하는 db.php를 사용)
require_once 'PHP+DB.php'; // 예: includes/db.php 경로에 DB 연결 코드가 있음

// 3. 페이징 관련 변수 설정
$perPage = 5; // 한 페이지에 보여줄 글 개수
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 4. 전체 글 개수 구하기
try {
    $countSql = "SELECT COUNT(*) FROM post";
    $totalPosts = $pdo->query($countSql)->fetchColumn();
} catch (PDOException $e) {
    die("⚠️ 글 개수를 불러오는 데 실패했습니다: " . $e->getMessage());
}

// 5. 전체 페이지 수 계산
$totalPages = ceil($totalPosts / $perPage);
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages; // 최대 페이지 제한
}

// 6. 현재 페이지의 OFFSET 계산
$offset = ($page - 1) * $perPage;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>대파 게시판 메인</title>
</head>
<body>
    <h2>📋대파 게시판 메인</h2>

    <!-- 로그인 상태에 따른 화면 -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <p>👋 안녕하세요, <?php echo htmlspecialchars($_SESSION['user_name']); ?>님!</p>
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

    <!-- 글 목록 출력 -->
    <h3>📝 글 목록</h3>
    <?php
    try {
        // 현재 페이지 글만 가져오기 (최신글 순)
        $sql = "SELECT post_id, post_title, created_at FROM post ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        // 게시물 번호는 전체 글 개수에서 (현재 페이지 시작 번호 + 반복 횟수)로 계산
        $startNumber = $totalPosts - $offset;

        while ($row = $stmt->fetch()) {
            $post_id = $row['post_id'];
            $title = htmlspecialchars($row['post_title']);
            $date = $row['created_at'];

            echo "<p>";
            echo "<strong>{$startNumber}</strong>. ";
            echo "<a href='view_post.php?id=$post_id'>$title</a> <small>($date)</small>";
            echo "</p>";

            $startNumber--;
        }

        if ($totalPosts == 0) {
            echo "<p>등록된 글이 없습니다.</p>";
        }
    } catch (PDOException $e) {
        echo "⚠️ 글 목록을 불러오는 데 실패했습니다: " . $e->getMessage();
    }
    ?>

    <!-- 페이징 네비게이션 -->
    <div style="margin-top: 20px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">◀ 이전</a>
        <?php endif; ?>

        <?php
        // 페이지 번호 출력 (예: 1 2 3 4 5)
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $page) {
                echo " <strong>$i</strong> ";
            } else {
                echo " <a href='?page=$i'>$i</a> ";
            }
        }
        ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">다음 ▶</a>
        <?php endif; ?>
    </div>
</body>
</html>

