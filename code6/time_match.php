<?php
// time_match.php
// 支援依頼ID（request_id）を元に、曜日＋AM/PM と提供会員の稼働ステータスから時間スコアを算出
// ★ provider_id = provider_profiles.id で統一

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function time_state_to_score($state)
{
    // DB定義: enum('OK','MAYBE','NG')
    if ($state === 'OK') {
        return 1.0;
    } elseif ($state === 'MAYBE') {
        return 0.5;
    } else {
        // NG または NULL
        return 0.0;
    }
}

function build_time_score_list(PDO $pdo, $requestId)
{
    // 1) 依頼情報
    $sqlReq = "SELECT request_date, start_time
               FROM support_requests
               WHERE id = :id
               LIMIT 1";
    $stmtReq = $pdo->prepare($sqlReq);
    $stmtReq->bindValue(':id', $requestId, PDO::PARAM_INT);
    $stmtReq->execute();
    $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        throw new Exception("支援依頼が存在しません（id={$requestId}）");
    }

    // 日付 → 曜日(1〜7)  月=1, 日=7
    $dt = new DateTime($req['request_date']);
    $weekday = (int)$dt->format('N');

    // 開始時間 → AM/PM
    $start = $req['start_time'];
    $hour  = (int)substr($start, 0, 2);
    $period = ($hour < 12) ? 'am' : 'pm';

    // 2) 提供会員（provider_profiles.id を provider_id として扱う）
    $sqlPro = "
        SELECT
            pp.id AS provider_id,
            m.last_name,
            m.first_name,
            pa.state
        FROM provider_profiles pp
        JOIN members m ON m.id = pp.member_id
        LEFT JOIN provider_availability pa
          ON pa.provider_id = pp.id
         AND pa.weekday    = :weekday
         AND pa.period     = :period
        WHERE m.is_provider = 1
    ";

    $stmtPro = $pdo->prepare($sqlPro);
    $stmtPro->bindValue(':weekday', $weekday, PDO::PARAM_INT);
    $stmtPro->bindValue(':period',  $period,  PDO::PARAM_STR);
    $stmtPro->execute();
    $providers = $stmtPro->fetchAll(PDO::FETCH_ASSOC);

    $results = array();

    foreach ($providers as $p) {
        $timeState = isset($p['state']) ? $p['state'] : null;
        $timeScore = time_state_to_score($timeState);

        $results[] = array(
            'provider_id'   => (int)$p['provider_id'], // ★ provider_profiles.id
            'provider_name' => $p['last_name'] . ' ' . $p['first_name'],
            'time_state'    => $timeState,
            'time_score'    => $timeScore,
        );
    }

    // 時間スコア降順
    usort($results, 'compare_time_score');

    return $results;
}

// usort用 比較関数
function compare_time_score($a, $b)
{
    if ($a['time_score'] == $b['time_score']) return 0;
    return ($a['time_score'] < $b['time_score']) ? 1 : -1; // 大きい方を前に
}

// 実行部
try {
    if (!isset($_GET['request_id'])) {
        throw new Exception('request_id が指定されていません。');
    }
    $requestId = (int)$_GET['request_id'];

    $pdo = new PDO($dsn, $db_user, $db_pass, array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $list = build_time_score_list($pdo, $requestId);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    header("Content-Type: text/plain; charset=utf-8");
    echo "エラー(time_match.php): " . $e->getMessage();
}
