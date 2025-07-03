<?php
// 1. 세션 시작 & DB 연결
session_start();
require_once 'PHP+DB.php';

// 2. 변수 초기화
$users_id = '';
$users_password = '';
$error = '';

// 3. 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users_id = $_POST['users_id'];
    $users_password = $_POST['users_password'];

    if (empty($users_id) || empty($users_password)) {
        $error = '아이디와 비밀번호를 모두 입력해주세요.';
    } else {
        // 3-1. 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM users WHERE users_id = ?");
        $stmt->execute([$users_id]);
        $user = $stmt->fetch();

        if ($user) {
            // 3-2. 비밀번호 검증
            if (password_verify($users_password, $user['users_password'])) {
                // 로그인 성공 → 세션 저장
                $_SESSION['user_id'] = $user['users_id'];
                $_SESSION['user_name'] = $user['users_name'];

                // 메인 페이지로 이동
                header("Location: index.php");
                exit;
            } else {
                $error = '비밀번호가 올바르지 않습니다.';
            }
        } else {
            $error = '존재하지 않는 아이디입니다.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
    <h2>🔐 로그인</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <p>아이디: <input type="text" name="users_id" value="<?php echo htmlspecialchars($users_id); ?>"></p>
        <p>비밀번호: <input type="password" name="users_password"></p>
        <p><button type="submit">로그인</button></p>
    </form>

    <p><a href="register.php">회원가입</a> | <a href="index.php">← 메인으로</a></p>
</body>
</html>
