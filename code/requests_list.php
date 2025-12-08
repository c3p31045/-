<?php
// requests_list.php
// 援助依頼一覧を返す

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $sql = "
        SELECT
            sr.id,
            sr.request_date,
            sr.start_time,
            sr.end_time,
            sr.detail AS request_text,   -- ★ここが正解：detail（単数）
            m.last_name,
            m.first_name
        FROM support_requests sr
        LEFT JOIN members m ON m.id = sr.requester_member_id
        ORDER BY sr.id DESC
        LIMIT 20
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $list = [];
    foreach ($rows as $r) {
        $list[] = [
            'id'             => (int)$r['id'],
            'request_date'   => $r['request_date'],
            'start_time'     => $r['start_time'],
            'end_time'       => $r['end_time'],
            'request_text'   => $r['request_text'],  // ←detail を request_text として扱う
            'requester_name' => trim(($r['last_name'] ?? '') . ' ' . ($r['first_name'] ?? '')),
        ];
    }

    echo json_encode($list, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'エラー(requests_list.php): ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
