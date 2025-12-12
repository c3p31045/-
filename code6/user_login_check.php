<?php
declare(strict_types=1);

// ===== DB 接続 =====
$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

$login_id = $_POST['login_id'] ?? '';
$password = $_POST['password'] ?? '';

if ($login_id === '' || $password === '') {
    exit('ログインID または パスワードが入力されていません。');
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // ===== usersテーブルから該当ユーザーを取得 =====
    $stmt = $pdo->prepare("SELECT id, login_id, password_hash FROM users WHERE login_id = ? LIMIT 1");
    $stmt->execute([$login_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        exit('ログインに失敗しました（IDが存在しません）。');
    }

    // ===== パスワード照合 =====
    if (!password_verify($password, $user['password_hash'])) {
        exit('ログインに失敗しました（パスワードが誤っています）。');
    }

    // ===== ログイン成功 → セッション発行 =====
    session_start();
    $_SESSION['user_login'] = true;
    $_SESSION['user_id']    = $user['id'];
    $_SESSION["member_id"] = $user["id"];  // ← 追加（超重要）
    $_SESSION["role"] = "user"; 
    $_SESSION['login_id']   = $user['login_id'];

    // ===== 遷移先（利用会員メニュー） =====
    header("Location: user_menu.html");
    exit;

} catch (Throwable $e) {
    exit('エラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
