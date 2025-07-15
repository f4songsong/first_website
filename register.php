<?php
// 1. 세션 시작 & DB 연결
session_start();
require_once 'PHP+DB.php';

// 2. 변수 초기화
$users_id = '';
$users_name = '';
$users_password = '';
$error = '';
$success = '';

// 3. 폼 제출 처리+ trim 추가
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users_id = trim($_POST['users_id']);
    $users_name = trim($_POST['users_name']);
    $users_password = trim($_POST['users_password']);


    // 3-1. 입력값 검증
    if (empty($users_id) || empty($users_name) || empty($users_password)) {
        $error = '모든 항목을 입력해주세요.';
    } elseif (!preg_match('/^[a-z0-9]{4,20}$/', $users_id)) {
        $error = '아이디는 영문 소문자와 숫자 조합이며, 4~20자여야 합니다.';
    } elseif (!preg_match('/^[a-zA-Z가-힣]{2,30}$/u', $users_name)) {
        $error = '이름은 한글 또는 영문으로 2~30자 사이여야 합니다.';
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).{8,}$/', $users_password)) {
        $error = '비밀번호는 8자 이상이며, 영문과 숫자를 포함해야 합니다.';
    } else {
        // 3-2. 아이디 중복 확인
        $stmt = $pdo->prepare("SELECT * FROM users WHERE users_id = ?");
        $stmt->execute([$users_id]);

        if ($stmt->fetch()) {
            $error = '이미 존재하는 아이디입니다.';
        } else {
            // 3-3. 비밀번호 해시 후 저장
            $hashed_password = password_hash($users_password, PASSWORD_DEFAULT);

            // 3-4. 데이터 저장
            $stmt = $pdo->prepare("INSERT INTO users (users_id, users_name, users_password) VALUES (?, ?, ?)");
            $result = $stmt->execute([$users_id, $users_name, $hashed_password]);

            if ($result) {
                $success = '회원가입이 완료되었습니다. 로그인해주세요.';
                // 입력값 초기화
                $users_id = $users_name = '';
            } else {
                $error = '회원가입에 실패했습니다.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
</head>
<body>
    <h2>📝 회원가입</h2>

    <!-- 메시지 출력 -->
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>

    <!-- 회원가입 폼 -->
    <form method="post" action="register.php">
        <p>아이디: <input type="text" name="users_id" value="<?php echo htmlspecialchars($users_id); ?>">
        <small style="color: gray;">※ 영문 소문자 + 숫자, 4~20자</small>
        </p>
        <p>이름: <input type="text" name="users_name" value="<?php echo htmlspecialchars($users_name); ?>">
        <small style="color: gray;">※ 한글 또는 영문, 2~30자</small>
        </p>
        <p>비밀번호: <input type="password" name="users_password">
        <small style="color: gray;">※ 8자 이상, 영문 + 숫자 포함</small>
        </p>
        <p><button type="submit">회원가입</button></p>
    </form>

    <p><a href="index.php">← 메인으로 돌아가기</a></p>
</body>
</html>
