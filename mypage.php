<?php
session_start();
require_once 'PHP+DB.php';

// 로그인 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// 정보 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_password = trim($_POST['password']);

    if (empty($new_name)) {
        $error = "이름은 비워둘 수 없습니다.";
    } else {
        try {
            if (!empty($new_password)) {
                // 비밀번호와 이름 둘 다 수정
                $stmt = $pdo->prepare("UPDATE users SET users_name = ?, users_password = ? WHERE users_id = ?");
                $stmt->execute([$new_name, $new_password, $user_id]);
            } else {
                // 이름만 수정
                $stmt = $pdo->prepare("UPDATE users SET users_name = ? WHERE users_id = ?");
                $stmt->execute([$new_name, $user_id]);
            }

            $_SESSION['user_name'] = $new_name; // 세션 갱신
            $success = "✅ 정보가 성공적으로 수정되었습니다!";
        } catch (PDOException $e) {
            $error = "DB 오류: " . $e->getMessage();
        }
    }
}

// 사용자 정보 불러오기
try {
    $stmt = $pdo->prepare("SELECT users_id, users_name FROM users WHERE users_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "사용자 정보를 불러올 수 없습니다.";
        exit;
    }
} catch (PDOException $e) {
    echo "DB 오류: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>마이페이지</title>
</head>
<body>
    <h2>👤 마이페이지</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <p><strong>아이디:</strong> <?php echo htmlspecialchars($user['users_id']); ?></p>

    <form method="post" action="mypage.php">
        <p>
            <label>이름: <input type="text" name="name" value="<?php echo htmlspecialchars($user['users_name']); ?>" required></label>
        </p>
        <p>
            <label>비밀번호: <input type="password" name="password" placeholder="변경 시에만 입력"></label>
        </p>
        <p><button type="submit">정보 수정</button></p>
    </form>

    <p><a href="index.php">← 메인으로 돌아가기</a></p>
</body>
</html>
