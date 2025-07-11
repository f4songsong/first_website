<?php
// 1. 세션 시작
session_start();

// 2. 모든 세션 변수 제거
$_SESSION = array();

// 3. 세션 쿠키가 있으면 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. 세션 파괴
session_destroy();

// 5. 메인 페이지로 리다이렉트
header("Location: index.php");
exit;
?>
