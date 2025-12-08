<?php
// list_confirmed_matches.php
// confirmed_matches に入っている「確定済みマッチング」を一覧でJSON返却

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

session_start();

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
          MIN(cm.id)          AS confirmed_id,          -- 代表ID
          cm.request_id,
          cm.provider_id,
          MAX(cm.score)       AS score,                 -- 最大スコア
          MIN(cm.created_at)  AS created_at,

          sr.request_date,
          sr.start_time,
          sr.end_time,
          sr.detail           AS request_text,

          rm.last_name        AS requester_last_name,
          rm.first_name       AS requester_first_name,

          pv.last_name        AS provider_last_name,
          pv.first_name       AS provider_first_name
        FROM confirmed_matches cm
        JOIN support_requests sr
          ON sr.id = cm.request_id
        JOIN members rm
          ON rm.id = sr.requester_member_id

        -- ★ここが重要：provider_profiles 経由で提供会員にJOIN
        JOIN provider_profiles pp
          ON pp.id = cm.provider_id           -- provider_profiles.id
        JOIN members pv
          ON pv.id = pp.member_id             -- 提供会員本人

        GROUP BY
          cm.request_id,
          cm.provider_id,
          sr.request_date,
          sr.start_time,
          sr.end_time,
          sr.detail,
          rm.last_name,
          rm.first_name,
          pv.last_name,
          pv.first_name

        ORDER BY
          sr.request_date ASC,
          sr.start_time ASC,
          confirmed_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $result = [];

    foreach ($rows as $r) {
        $result[] = [
            'confirmed_id'   => (int)$r['confirmed_id'],
            'request_id'     => (int)$r['request_id'],
            'provider_id'    => (int)$r['provider_id'],
            'request_date'   => $r['request_date'],
            'start_time'     => $r['start_time'] ? substr($r['start_time'], 0, 5) : null,
            'end_time'       => $r['end_time']   ? substr($r['end_time'],   0, 5) : null,
            'request_text'   => $r['request_text'],
            'requester_name' => trim(($r['requester_last_name'] ?? '') . ' ' . ($r['requester_first_name'] ?? '')),
            'provider_name'  => trim(($r['provider_last_name']  ?? '') . ' ' . ($r['provider_first_name']  ?? '')),
            'score'          => is_null($r['score']) ? null : (float)$r['score'],
            'created_at'     => $r['created_at'],
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'list_confirmed_matches エラー: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
