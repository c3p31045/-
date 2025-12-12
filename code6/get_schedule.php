<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

$dsn = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ロール判定
    $role = 'staff';   // デフォルト職員
    $memberId = null;

    if (!empty($_SESSION['provider_member_id'])) {
        $role = 'provider'; // 提供会員
        $memberId = (int)$_SESSION['provider_member_id']; // ← members.id
    } elseif (!empty($_SESSION['member_id'])) {
        $role = 'user'; // 利用会員
        $memberId = (int)$_SESSION['member_id']; // ← members.id
    }

    // 日別取得（ツールチップ用）
    if (!empty($_GET['date'])) {
        $date = $_GET['date'];

        $sql = "
            SELECT
              sr.request_date, sr.start_time, sr.end_time,
              rm.last_name AS requester_last, rm.first_name AS requester_first,
              pm.last_name AS provider_last, pm.first_name AS provider_first
            FROM confirmed_matches cm
            JOIN support_requests sr ON sr.id = cm.request_id
            JOIN members rm ON rm.id = sr.requester_member_id
            JOIN provider_profiles pp ON pp.id = cm.provider_id
            JOIN members pm ON pm.id = pp.member_id
            WHERE sr.request_date = :date
        ";
        $params = [':date' => $date];

        if ($role === 'provider') {
            $sql .= " AND pp.member_id = :mid";
            $params[':mid'] = $memberId;
        } elseif ($role === 'user') {
            $sql .= " AND sr.requester_member_id = :mid";
            $params[':mid'] = $memberId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $events = [];
        foreach ($rows as $r) {
            $events[] = [
                'start_time' => substr($r['start_time'], 0, 5),
                'title' => "利用: {$r['requester_last']} {$r['requester_first']} / 提供: {$r['provider_last']} {$r['provider_first']}"
            ];
        }

        echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 月別取得（強調表示用）
    if (!empty($_GET['month'])) {
        $month = $_GET['month'];
        $start = $month . "-01";
        $end   = date("Y-m-t", strtotime($start));

        $sql = "
            SELECT DISTINCT sr.request_date
            FROM confirmed_matches cm
            JOIN support_requests sr ON sr.id = cm.request_id
            JOIN provider_profiles pp ON pp.id = cm.provider_id
            WHERE sr.request_date BETWEEN :start AND :end
        ";
        $params = [':start' => $start, ':end' => $end];

        if ($role === 'provider') {
            $sql .= " AND pp.member_id = :mid";
            $params[':mid'] = $memberId;
        } elseif ($role === 'user') {
            $sql .= " AND sr.requester_member_id = :mid";
            $params[':mid'] = $memberId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode([
            'dates' => array_column($rows, 'request_date')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['error' => 'no param']);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
