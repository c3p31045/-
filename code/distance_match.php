<?php

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function simple_distance_km($lat1, $lon1, $lat2, $lon2)
{
    $dx = ($lat2 - $lat1) * 111; // 緯度差→km
    $dy = ($lon2 - $lon1) * 111; // 経度差→km（市内なら誤差小）
    return sqrt($dx * $dx + $dy * $dy);
}

function distance_score($d_km)
{
    $k = 0.2; // 距離感度（必要なら調整）
    return 1.0 / (1.0 + $k * $d_km);
}


function get_requester_member_id(PDO $pdo, $requestId)
{
    $sql = "SELECT requester_member_id
            FROM support_requests
            WHERE id = :id
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['requester_member_id']) {
        throw new Exception("支援依頼が存在しないか、requester_member_id が未設定です（id={$requestId}）");
    }
    return (int)$row['requester_member_id'];
}

function build_distance_ranking(PDO $pdo, $requestId)
{
    $requesterMemberId = get_requester_member_id($pdo, $requestId);

    $sqlReq = "SELECT id, last_name, first_name, lat, lon
               FROM members
               WHERE id = :id
               LIMIT 1";
    $stmtReq = $pdo->prepare($sqlReq);
    $stmtReq->bindValue(':id', $requesterMemberId, PDO::PARAM_INT);
    $stmtReq->execute();
    $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        throw new Exception('利用会員情報が見つかりません。member_id=' . $requesterMemberId);
    }
    if ($req['lat'] === null || $req['lon'] === null) {
        throw new Exception('利用会員に緯度経度が登録されていません。');
    }

    $reqLat = (float)$req['lat'];
    $reqLon = (float)$req['lon'];

    $sqlPro = "
        SELECT
            pp.id AS provider_id,
            m.last_name,
            m.first_name,
            m.lat,
            m.lon
        FROM provider_profiles pp
        JOIN members m ON m.id = pp.member_id
        WHERE m.is_provider = 1
          AND m.lat IS NOT NULL
          AND m.lon IS NOT NULL
    ";
    $stmtPro = $pdo->query($sqlPro);
    $providers = $stmtPro->fetchAll(PDO::FETCH_ASSOC);

    $results = array();

    foreach ($providers as $p) {
        $proLat = (float)$p['lat'];
        $proLon = (float)$p['lon'];

        $d = simple_distance_km($reqLat, $reqLon, $proLat, $proLon);
        $score = distance_score($d);

        $results[] = array(
            'provider_id'    => (int)$p['provider_id'],
            'provider_name'  => $p['last_name'] . ' ' . $p['first_name'],
            'distance_km'    => $d,
            'distance_score' => $score,
        );
    }

    usort($results, 'compare_distance_score');

    return $results;
}

function compare_distance_score($a, $b)
{
    if ($a['distance_score'] == $b['distance_score']) return 0;
    return ($a['distance_score'] < $b['distance_score']) ? 1 : -1;
}

try {
    if (!isset($_GET['request_id'])) {
        throw new Exception('request_id が指定されていません。');
    }
    $requestId = (int)$_GET['request_id'];

    $pdo = new PDO($dsn, $db_user, $db_pass, array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $ranking = build_distance_ranking($pdo, $requestId);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ranking, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'エラー(distance_match.php): ' . $e->getMessage();
}

