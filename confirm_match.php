<?php
// confirm_match.php
// 仮候補(temporary_matches)を本保存(confirmed_matches)に移す

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data || !isset($data['temp_id'])) {
        throw new Exception('temp_id が指定されていません');
    }

    $temp_id = (int)$data['temp_id'];

    // 仮候補を取得
    $stmt = $pdo->prepare("SELECT * FROM temporary_matches WHERE id = :id");
    $stmt->execute([':id' => $temp_id]);
    $tmp = $stmt->fetch();

    if (!$tmp) {
        throw new Exception('指定された仮候補が見つかりません (id=' . $temp_id . ')');
    }

    // confirmed_matches に保存
    $insert = $pdo->prepare("
        INSERT INTO confirmed_matches (request_id, provider_id, score)
        VALUES (:request_id, :provider_id, :score)
    ");
    $insert->execute([
        ':request_id'  => $tmp['request_id'],
        ':provider_id' => $tmp['provider_id'],
        ':score'       => $tmp['score'],
    ]);

    // temporary_matches から削除（仮候補一覧から消す）
    $delete = $pdo->prepare("DELETE FROM temporary_matches WHERE id = :id");
    $delete->execute([':id' => $temp_id]);

    echo json_encode([
        'ok' => true,
        'confirmed_id' => (int)$pdo->lastInsertId(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'エラー(confirm_match.php): ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
