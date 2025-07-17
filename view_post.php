<?php
// 세션 시작 및 DB 연결
session_start();
require_once 'PHP+DB.php';

// 1. URL 파라미터 검사->SQL Injection 공격 위험 감소
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { // $_GET['id'] 값이 존재하지 않거나 숫자가 아닐 경우(참) -> id 안전한지 아닌지 확인
    echo "잘못된 접근입니다.";
    exit;
}
$post_id = (int)$_GET['id']; // $_GET['id']을 정수로 변환 후 변수에 저장

try {
    // 2. post 테이블에서 제목 가져오기
    $stmt = $pdo->prepare("SELECT post_title, users_id FROM post WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo "존재하지 않는 글입니다.";
        exit;
    }

    // 3. content 테이블에서 block_type='text'인 본문만 가져오기
    $stmt2 = $pdo->prepare("
        SELECT content 
        FROM content 
        WHERE post_id = ? AND block_type = 'text' 
        ORDER BY content_id ASC
    ");
    $stmt2->execute([$post_id]);
    $contents = $stmt2->fetchAll();

} catch (PDOException $e) {
    echo "DB 오류: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['post_title']); ?></title>
</head>
<body>

    <!-- 글 제목 -->
    <h2><?php echo htmlspecialchars($post['post_title']); ?></h2>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['users_id']): ?>
        <p>
            <a href="delete_post.php?post_id=<?php echo $post_id; ?>" 
            onclick="return confirm('정말 이 글을 삭제하시겠습니까?')">🗑️ 게시글 삭제</a>
        </p>
    <?php endif; ?>

    <!-- 본문 내용 출력 -->
    <?php if ($contents): ?>
        <?php foreach ($contents as $content): ?>
            <p><?php echo nl2br(htmlspecialchars($content['content'])); ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <p>내용이 없습니다.</p>
    <?php endif; ?>

    <p><a href="index.php">← 목록으로 돌아가기</a></p>

    <!---->
 <h3>📎 첨부 파일</h3>
<?php
$stmt_file = $pdo->prepare("SELECT file_name, saved_name FROM file WHERE post_id = ?");
$stmt_file->execute([$post_id]);
$files = $stmt_file->fetchAll();

if ($files):
    foreach ($files as $file):
        $file_name = htmlspecialchars($file['file_name']);
        $saved_name = urlencode($file['saved_name']);
        $file_url = "uploads/" . $saved_name;

        // 확장자 추출
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        if (in_array($ext, $image_exts)) {
            // 이미지라면 미리보기로 표시
            echo "<div style='margin-bottom:10px;'>
                    <img src=\"$file_url\" alt=\"$file_name\" style=\"max-width:100%; height:auto; border:1px solid #ccc; border-radius:4px;\">
                  </div>";
        } else {
            // 그 외 파일은 다운로드 링크로
            echo "<p><a href=\"$file_url\" download>$file_name</a></p>";
        }
    endforeach;
else:
    echo "<p>첨부된 파일이 없습니다.</p>";
endif;
?>

<!---->

    <!-- 댓글 작성 폼 -->
    <h3>💬 댓글 작성</h3>
    <form id="comment-form">
        <textarea name="comment" rows="4" cols="50" placeholder="댓글을 입력하세요..." required></textarea>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <p><button type="submit">댓글 작성</button></p>
    </form>

    <p id="comment-message"></p>

    <!-- 댓글 목록 출력 -->
    <h3>🗨️ 댓글 목록</h3>
    <?php
    try {
        $stmt = $pdo->prepare("
            SELECT c.comment_id, c.content, c.created_at, c.users_id, u.users_name
            FROM comment c
            JOIN users u ON c.users_id = u.users_id
            WHERE c.post_id = ? AND c.deleted_at IS NULL
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();

        if ($comments):
            foreach ($comments as $comment):
    ?>
                <p>
                    <strong><?php echo htmlspecialchars($comment['users_name']); ?></strong>: 
                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    <small>(<?php echo $comment['created_at']; ?>)</small>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $comment['users_id']): ?>
                        <a href="delete_comment.php?comment_id=<?php echo $comment['comment_id']; ?>&post_id=<?php echo $post_id; ?>"
                        onclick="return confirm('댓글을 삭제하시겠습니까?')">🗑️ 삭제</a>
                    <?php endif; ?>
                    
                </p>
    <?php
            endforeach; // foreach 끝

        else:
            echo "<p>작성된 댓글이 없습니다.</p>";
        endif;
    } catch (PDOException $e) {
        echo "<p>❌ 댓글을 불러오는 데 실패했습니다: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

    <!-- 댓글 비동기 처리 -->
    <script>
    document.getElementById('comment-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const response = await fetch('comment.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        const msg = document.getElementById('comment-message');
        msg.textContent = result.message;
        msg.style.color = result.success ? 'green' : 'red';

        if (result.success) {
            this.comment.value = '';
            location.reload(); // 새로고침으로 댓글 목록 반영
        }
    });
    </script>

</body>
</html>