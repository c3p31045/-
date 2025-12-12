<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',1);
error_reporting(E_ALL);

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // JSON受け取り
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception("JSONが不正です");
    }

    $request_id  = (int)$data["request_id"];
    $provider_id = (int)$data["provider_id"];
    $score       = (float)$data["score"];

    $sql = "INSERT INTO temporary_matches (request_id, provider_id, score)
            VALUES (:request_id, :provider_id, :score)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":request_id"  => $request_id,
        ":provider_id" => $provider_id,
        ":score"       => $score
    ]);

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
