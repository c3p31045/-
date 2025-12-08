<?php
// list_temporary_matches.php
// temporary_matches に保存された仮候補を一覧で返す

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

    /**
     * temporary_matches.provider_id  → provider_profiles.id
     * provider_profiles.member_id    → members.id（提供会員）
     * support_requests.requester_member_id → members.id（利用会員）
     *
     * 同じ request_id × provider_id が複数行あるときは
     * スコア最大のものだけ 1 件にまとめて返す。
     */
    $sql = "
        SELECT
            MIN(tm.id)           AS temp_id,          -- 代表行のID
            tm.request_id,
            tm.provider_id,
            MAX(tm.score)        AS score,           -- 同じ組合せが複数あるとき最大スコア
            MIN(tm.created_at)   AS created_at,

            sr.request_date,
            sr.start_time,
            sr.end_time,
            sr.detail            AS request_text,

            rq.last_name         AS requester_last_name,
            rq.first_name        AS requester_first_name,

            pv.last_name         AS provider_last_name,
            pv.first_name        AS provider_first_name
        FROM temporary_matches tm
        JOIN support_requests sr
          ON sr.id = tm.request_id
        JOIN members rq
          ON rq.id = sr.requester_member_id           -- 依頼者（利用会員）

        JOIN provider_profiles pp
          ON pp.id = tm.provider_id                   -- プロバイダプロファイル
        JOIN members pv
          ON pv.id = pp.member_id                     -- 提供会員本人

        GROUP BY
            tm.request_id,
            tm.provider_id,
            sr.request_date,
            sr.start_time,
            sr.end_time,
            sr.detail,
            rq.last_name,
            rq.first_name,
            pv.last_name,
            pv.first_name

        ORDER BY
            tm.request_id,
            score DESC,
            temp_id
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $list = [];
    foreach ($rows as $r) {
        $list[] = [
            'temp_id'        => (int)$r['temp_id'],
            'request_id'     => (int)$r['request_id'],
            'provider_id'    => (int)$r['provider_id'],
            'score'          => (float)$r['score'],
            'created_at'     => $r['created_at'],
            'request_date'   => $r['request_date'],
            'start_time'     => $r['start_time'],
            'end_time'       => $r['end_time'],
            'request_text'   => $r['request_text'],
            'requester_name' => trim(($r['requester_last_name'] ?? '') . ' ' . ($r['requester_first_name'] ?? '')),
            'provider_name'  => trim(($r['provider_last_name']  ?? '') . ' ' . ($r['provider_first_name']  ?? '')),
        ];
    }

    echo json_encode($list, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'エラー(list_temporary_matches.php): ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
