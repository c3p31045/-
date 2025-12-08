<?php
// get_schedule.php
// 役割：
//   ?date=YYYY-MM-DD  → その日の予定一覧を返す（ツールチップ用）
//   ?month=YYYY-MM    → その月の「予定がある日付一覧」を返す（強調表示用）

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

session_start();

/*
  ★ ログイン情報はあなたの環境に合わせてください
    - provider_login_check.php では  provider_member_id / provider_login を使用
    - user_login_check.php     では  user_login しかないので、本来は member_id も欲しい
      （confirmed_list.php と同じ方針にそろえる）
*/

// 会員IDの取得（confirmed_list.php と同じ取り方）
$memberId =
    $_SESSION['member_id']
    ?? ($_SESSION['provider_member_id'] ?? ($_SESSION['id'] ?? null));

// 役割の推定（confirmed_list.php と同じロジック）
$role = $_SESSION['role'] ?? (
    (!empty($_SESSION['provider_login']) ? 'provider'
     : (!empty($_SESSION['user_login']) ? 'user' : null))
);

// ログインしていない / 役割不明なら空を返す
if (!$memberId || !$role) {
    echo json_encode(['events' => [], 'dates' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // -----------------------------
    // ① 日別イベント取得モード
    // -----------------------------
    if (!empty($_GET['date'])) {
        $date = $_GET['date'];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['events' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "
            SELECT
              cm.id AS confirmed_id,
              sr.request_date,
              sr.start_time,
              sr.end_time,
              rm.last_name  AS requester_last_name,
              rm.first_name AS requester_first_name,
              pm.last_name  AS provider_last_name,
              pm.first_name AS provider_first_name,
              sr.requester_member_id,
              cm.provider_id
            FROM confirmed_matches cm
            JOIN support_requests sr
              ON sr.id = cm.request_id
            JOIN members rm
              ON rm.id = sr.requester_member_id
            JOIN members pm
              ON pm.id = cm.provider_id
            WHERE sr.request_date = :date
        ";

        // 本人の分だけ絞る（confirmed_list.php と同じ考え方）
        if ($role === 'provider') {
            $sql .= " AND cm.provider_id = :mid";
        } elseif ($role === 'user') {
            $sql .= " AND sr.requester_member_id = :mid";
        } else {
            // 念のためその他ロール用（基本通らない想定）
            $sql .= " AND (cm.provider_id = :mid OR sr.requester_member_id = :mid)";
        }

        $sql .= " ORDER BY sr.start_time ASC, cm.id ASC";

        $params = [
            ':date' => $date,
            ':mid'  => $memberId,
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $events = [];
        foreach ($rows as $r) {
            $requester = trim(($r['requester_last_name'] ?? '') . ' ' . ($r['requester_first_name'] ?? ''));
            $provider  = trim(($r['provider_last_name']  ?? '') . ' ' . ($r['provider_first_name']  ?? ''));

            $events[] = [
                'start_time' => substr($r['start_time'], 0, 5),
                'end_time'   => substr($r['end_time'], 0, 5),
                'title'      => "利用: {$requester} / 提供: {$provider}",
            ];
        }

        echo json_encode([
            'mode'   => 'date',
            'date'   => $date,
            'events' => $events,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------
    // ② 月別「予定あり日」取得モード
    // -----------------------------
    if (!empty($_GET['month'])) {
        $month = $_GET['month']; // 例: 2025-11

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            echo json_encode(['dates' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        list($year, $mon) = explode('-', $month);
        $startDate = sprintf('%04d-%02d-01', (int)$year, (int)$mon);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $sql = "
            SELECT DISTINCT sr.request_date
            FROM confirmed_matches cm
            JOIN support_requests sr
              ON sr.id = cm.request_id
            WHERE sr.request_date BETWEEN :start AND :end
        ";

        // 本人の分だけ絞る
        if ($role === 'provider') {
            $sql .= " AND cm.provider_id = :mid";
        } elseif ($role === 'user') {
            $sql .= " AND sr.requester_member_id = :mid";
        } else {
            $sql .= " AND (cm.provider_id = :mid OR sr.requester_member_id = :mid)";
        }

        $sql .= " ORDER BY sr.request_date ASC";

        $params = [
            ':start' => $startDate,
            ':end'   => $endDate,
            ':mid'   => $memberId,
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $dates = [];
        foreach ($rows as $r) {
            $dates[] = $r['request_date'];
        }

        echo json_encode([
            'mode'  => 'month',
            'month' => $month,
            'dates' => $dates,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // どちらのパラメータもなければ空
    echo json_encode(['events' => [], 'dates' => []], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'events' => [],
        'dates'  => [],
        'error'  => 'get_schedule エラー: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
