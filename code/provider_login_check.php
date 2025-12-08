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

    // ===== users テーブルから取得 =====
    $stmt = $pdo->prepare("
        SELECT u.id, u.login_id, u.password_hash, m.id AS member_id, m.is_provider
        FROM users u
        LEFT JOIN members m ON m.user_id = u.id
        WHERE u.login_id = ?
        LIMIT 1
    ");
    $stmt->execute([$login_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        exit('ログインに失敗しました（IDが存在しません）。');
    }

    // ===== パスワード照合 =====
    if (!password_verify($password, $row['password_hash'])) {
        exit('ログインに失敗しました（パスワードが誤っています）。');
    }

    // ===== 提供会員であるか確認 =====
    if ((int)$row['is_provider'] !== 1) {
        exit('このアカウントは提供会員ではありません。');
    }

    // ===== ログイン成功 → セッション発行 =====
    session_start();
    $_SESSION['provider_login']  = true;
    $_SESSION['provider_user_id'] = $row['id'];
    $_SESSION["member_id"] = $user["id"];  // ← 追加
    $_SESSION["role"] = "provider";
    $_SESSION['provider_member_id'] = $row['member_id'];
    $_SESSION['provider_login_id'] = $row['login_id'];

    // ===== 遷移先（提供会員メニュー） =====
    header("Location: provider_menu.html");
    exit;

} catch (Throwable $e) {
    exit('エラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
