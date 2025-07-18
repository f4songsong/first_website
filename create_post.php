<?php
session_start(); // 세션(사용자 정보 서버 저장) 시작, html위에 위치 필요

require_once 'PHP+DB.php'; // db연결하는 코드가 적힌 php파일을 현재 파일에 불러오는 명령

if (!isset($_SESSION['user_id'])) { //$_SESSION에 user_id 없을시(로그인 안한 경우), !isset(): 로그인 안함?
    header("Location: login.php"); // login.php로 이동
    exit; // php 즉시 종료
}

$error = ''; // 에러 발생시 뜨는 메시지를 담기 위한 변수
$success = ''; // 성공시 뜨는 메시지를 담기 위한 변수

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //현재 HTTP요청 방식을 나타냄, 이 조건문을 통해 폼 제출 시에만 아래 코드가 실행되게 함
    $title = trim($_POST['title']); // 폼에 입력한 제목을 받아오면서 trim을 통해 앞 뒤 공백을 제거, 입력값 공백만 있는 경우도 방지 가능
    $body = trim($_POST['body']); // 위와 동일 하지만 본문 내용 처리

    if (empty($title) || empty($body)) { // 제목 or 본문이 비어있다면
        $error = '제목과 내용을 모두 입력해 주세요.'; // 조건 만족시 에러 메시지 출력
    } else { // 모두 정상적으로 내용이 입력되었다면
        try { //에러 발생해도 계속 진행
            $pdo->beginTransaction();

            $stmt = $pdo->query("SELECT MAX(post_id) AS max_id FROM post"); // post테이블에서 가장 큰 post_id 값을 구해 가장 큰 게시글번호를 가져옴
            $row = $stmt->fetch(); // 위 코드에서 가져온 결과를 한 줄 가져와 $row에 담는다
            $new_post_id = $row['max_id'] + 1; // 마지막 글 번호에 +1해서 새 글 번호를 만들기
            if ($new_post_id === null) { // 만약 post_id가 아무 값도 없으면 null실행(게시글 없을시)
                $new_post_id = 1; //새 글 번호 1로 지정
            }

            $stmt1 = $pdo->prepare("INSERT INTO post (post_id, post_title, users_id) VALUES (?, ?, ?)"); // post 테이블에  post_id, post_title, users_id에 각각 값 넣에 새 행 추가
            $stmt1->execute([$new_post_id, $title, $_SESSION['user_id']]); // 준비한 쿼리에 실제 값을 넣고 실행(새 글 번호, 제목, 글 쓴 사람 아이디)

            $stmt2 = $pdo->prepare("INSERT INTO content (block_type, content, post_id) VALUES ('text', ?, ?)"); // content 테이블에 새 데이터 추가 준비, block_type은 text로 고정
            $stmt2->execute([$body, $new_post_id]); // 위 쿼리에서 비워둔 ?에 두 값 추가 후 실행

            if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) { // 사용자가 파일 업로드했는지 확인
                $file = $_FILES['upload_file']; //$_FILES['upload_file'] 배열을 $file 변수에 저장
                $original_name = basename($file['name']); // 업로드된 파일의 경로를 자른 원래 파일 이름만 잘라줌
                $saved_name = uniqid() . "_" . $original_name; //저장할 파일 이름 제작, 같은 이름이 여러번 업로드 되어도 덮어쓰지 방지
                $upload_dir = __DIR__ . '/uploads/'; //업로드 파일을 저장할 폴더 경로

                if (!is_dir($upload_dir)) { // uploads 폴더가 존재하지 않으면
                    mkdir($upload_dir, 0755, true); // 새 폴더 제작
                }

                move_uploaded_file($file['tmp_name'], $upload_dir . $saved_name); // 임시로 저장된 업로드 파일을 임시 경로->실제 서버 저장 경로로 옮겨줌

                $stmt3 = $pdo->prepare("INSERT INTO file (file_name, saved_name, post_id) VALUES (?, ?, ?)"); // file 테이블에 정보 추가 준비
                $stmt3->execute([$original_name, $saved_name, $new_post_id]); // 정보 추가
            }

            $pdo->commit(); // 지금까지 실행한 DB 작업들을 완전히 저장

            $success = '✅ 글이 성공적으로 작성되었습니다!'; // 글 저장 끝난 후 success변수에 문장을 담아 성공메시지 보여줌
        } catch (PDOException $e) { // try()블록에서 오류 발생시 catch(여기)로 이동
            $error = 'DB 오류: ' . $e->getMessage(); // 에러 메시지 만들고 변수에 저장 후 실제 에러 내용을 문자열로 뽑아줌(getmessage())
        }
    }
}
?>

<!DOCTYPE html> <!-- html 웹브라우저에 선언 -->
<html> <!-- html 문서 시작 -->
<head> <!-- 웹페이지 정보 설정 -->
    <meta charset="UTF-8"> <!-- 글자 인코딩 UTF-8로 설정 -->
    <title>글 작성</title> <!-- 브라우저 탭에 보이는 페이지 제목 -->
    <link rel="stylesheet" href="style.css">
</head><!-- 웹페이지 정보 설정 끝 -->
<body><!-- 실제로 화면 출력 내용 담는 부분 -->
    <h2>✏️ 글 작성</h2><!-- 가운데 글 제목으로 보여짐 -->

    <?php if ($error): ?> <!-- $error에 값이 있으면 아래 코드 실행 -->
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p><!-- 빨간 글씨로 에러 메시지 보여줌 -->
    <?php endif; ?> <!-- if 조건문 닫기 -->

    <?php if ($success): ?> <!-- $success에 값 있으면 아래 코드 실행 -->
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p> <!-- 초록 글씨로 메시지 보여줌 -->
    <?php endif; ?><!-- if 조건문 닫기 -->

    <form method="post" action="create_post.php" enctype="multipart/form-data"><!-- 데이터 보낼 방식 설정, 데이터 처리할 php파일 경로 +파일 데이터 전송을 위해 쪼개서 보내는 형식 -->
        <p>제목: <input type="text" name="title" required></p><!-- 한 줄짜리 텍스트 입력창에 제목 작성가능, 입력없으면 제출 불가 -->
        <p>내용:<br> <!-- 본문 입력창을 표시하는 글씨 -->
            <textarea name="body" rows="10" cols="50" required></textarea><!-- 긴 텍스트 박스에 10높이, 50글자 너비, 입력없으면 제출 불가 -->
        </p>
        <p>파일 첨부: <input type="file" name="upload_file"></p> <!-- 파일첨부 버튼 추가 -->
        <p><button type="submit">작성하기</button></p> <!-- 작성하기 버튼, 클릭시 위에 from 내용을 서버로 전송 -->
    </form>

    <p><a href="index.php">← 메인으로 돌아가기</a></p> <!-- 메인화면으로 돌아가는 버튼 -->
</body>
</html>
