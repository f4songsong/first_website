<?php
// 1. 세션 시작 & DB 연결
session_start();
require_once 'PHP+DB.php';

// 2. CSRF 토큰 생성 (GET 요청 시)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. 변수 초기화
$users_id = '';
$users_password = '';
$error = '';

// 4. 로그인 처리 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 확인
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = '잘못된 요청입니다.';
    } else {
        // 사용자 입력 받기
        $users_id = trim($_POST['users_id']);
        $users_password = trim($_POST['users_password']);

        // 유효성 검사
        if (empty($users_id) || empty($users_password)) {
            $error = '아이디와 비밀번호를 모두 입력해주세요.';
        } elseif (strlen($users_id) < 4 || strlen($users_id) > 20) {
            $error = '아이디는 4자 이상 20자 이하로 입력해주세요.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $users_id)) {
            $error = '아이디는 영문자, 숫자, 밑줄(_)만 사용할 수 있습니다.';
        } else {
            // 사용자 정보 조회
            $stmt = $pdo->prepare("SELECT * FROM users WHERE users_id = ?");
            $stmt->execute([$users_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($users_password, $user['users_password'])) {
                // 로그인 성공 → 세션 저장
                $_SESSION['user_id'] = $user['users_id'];
                $_SESSION['user_name'] = $user['users_name'];

                // 로그인 후 토큰 제거
                unset($_SESSION['csrf_token']);

                // 메인 페이지로 이동
                header("Location: index.php");
                exit;
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
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
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <p>아이디: <input type="text" name="users_id" value="<?php echo htmlspecialchars($users_id); ?>"></p>
        <p>비밀번호: <input type="password" name="users_password"></p>
        <p><button type="submit">로그인</button></p>
    </form>

    <p><a href="register.php">회원가입</a> | <a href="index.php">← 메인으로</a></p>
</body>
</html>
