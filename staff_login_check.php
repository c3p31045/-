<?php
declare(strict_types=1);
session_start();

// 直接叩かれたときのガード（任意）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: staff_login.html');
    exit;
}

// DB 接続情報
$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

// フォーム入力
$login_id = $_POST['login_id'] ?? '';
$password = $_POST['password'] ?? '';

if ($login_id === '' || $password === '') {
    exit('ログインID またはパスワードが未入力です。');
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ★ 職員フラグ is_staff = 1 のユーザーだけ取得
    $stmt = $pdo->prepare("
        SELECT id, login_id, password_hash
        FROM users
        WHERE login_id = ? AND is_staff = 1
        LIMIT 1
    ");
    $stmt->execute([$login_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // ログインID が存在しない / 職員フラグが立っていない
        exit('職員としてのログインに失敗しました（IDが存在しないか、職員権限がありません）。');
    }

    // パスワード照合
    if (!password_verify($password, $user['password_hash'])) {
        exit('職員としてのログインに失敗しました（パスワード誤り）。');
    }

    // セッションに職員ログイン情報を保存
    $_SESSION['staff_login']    = true;
    $_SESSION['staff_id']       = $user['id'];
    $_SESSION['staff_login_id'] = $user['login_id'];
    $_SESSION["staff_id"] = $user["id"];
    $_SESSION["staff_login"] = true;

    $_SESSION["member_id"] = $user["id"];  // ← 追加（必要なら）
    $_SESSION["role"] = "staff";           // ← 追加（必要なら）


    // 職員メイン画面へ
    header('Location: staff_menu.html');
    exit;

} catch (Throwable $e) {
    exit('エラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
