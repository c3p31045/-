<?php
// match_api.php
// 役割：distance_match.php, time_match.php, matching_service.py の結果を統合して返すだけ
// ・AI（ロジスティック回帰）の処理はここでは行わない
// ・戻り値は「3要素の正規化結果をまとめた candidates 配列」

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // -------------------------
    // 0. request_id を取得
    // -------------------------
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
    if ($request_id <= 0) {
        throw new Exception('request_id が不正です');
    }

    // =========================================================
    // 1. 距離スコア：distance_match.php を include して JSON を取得
    // =========================================================
    $distanceFile = __DIR__ . '/distance_match.php';
    if (!file_exists($distanceFile)) {
        throw new Exception('distance_match.php が見つかりません: ' . $distanceFile);
    }

    $backup_get = $_GET;
    $_GET['request_id'] = $request_id;

    ob_start();
    include $distanceFile;
    $distanceOut = ob_get_clean();

    $_GET = $backup_get;

    // distance_match.php がエラー時にプレーンテキストを返している場合の簡易チェック
    if (strpos($distanceOut, 'エラー(') === 0) {
        throw new Exception('distance_match.php エラー: ' . $distanceOut);
    }

    $distanceList = json_decode($distanceOut, true);
    if (!is_array($distanceList)) {
        throw new Exception('distance_match.php の JSON が不正です: ' . $distanceOut);
    }

    // =========================================================
    // 2. 時間スコア：time_match.php を include して JSON を取得
    // =========================================================
    $timeFile = __DIR__ . '/time_match.php';
    if (!file_exists($timeFile)) {
        throw new Exception('time_match.php が見つかりません: ' . $timeFile);
    }

    $backup_get = $_GET;
    $_GET['request_id'] = $request_id;

    ob_start();
    include $timeFile;
    $timeOut = ob_get_clean();

    $_GET = $backup_get;

    if (strpos($timeOut, 'エラー(') === 0) {
        throw new Exception('time_match.php エラー: ' . $timeOut);
    }

    $timeList = json_decode($timeOut, true);
    if (!is_array($timeList)) {
        throw new Exception('time_match.php の JSON が不正です: ' . $timeOut);
    }

    // =========================================================
    // 3. 内容スコア：matching_service.py を exec して JSON を取得
    // =========================================================
    $python          = 'C:\\Users\\PC_User\\AppData\\Local\\Programs\\Python\\Python311\\python.exe';
    $script_matching = 'C:\\xampp\\htdocs\\matching\\matching_service.py';

    if (!file_exists($script_matching)) {
        throw new Exception('matching_service.py が見つかりません: ' . $script_matching);
    }

    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script_matching) . ' ' . intval($request_id) . ' 2>&1';

    $output = array();
    $ret    = 0;
    exec($cmd, $output, $ret);

    if ($ret !== 0 || empty($output)) {
        throw new Exception('matching_service.py 実行エラー: ' . implode("\n", $output));
    }

    $matchingJson = $output[0];
    $matchingData = json_decode($matchingJson, true);
    if (!is_array($matchingData) || !isset($matchingData['results'])) {
        throw new Exception('matching_service.py の JSON が不正です: ' . $matchingJson);
    }

    $contentList  = $matchingData['results']; // [{provider_id, score}, ...]
    $request_text = isset($matchingData['request_text']) ? $matchingData['request_text'] : '';

    // =========================================================
    // 4. provider_id ごとに 3要素をマージ（AIはまだかけない）
    // =========================================================
    $providers = array();

    // 距離
    foreach ($distanceList as $row) {
        if (!isset($row['provider_id'])) continue;
        $pid = $row['provider_id'];

        if (!isset($providers[$pid])) {
            $providers[$pid] = array(
                'provider_id' => $pid,
            );
        }

        if (isset($row['distance_score'])) {
            $providers[$pid]['distance_score'] = (float)$row['distance_score'];
        }
        if (isset($row['distance_km'])) {
            $providers[$pid]['distance_km'] = (float)$row['distance_km'];
        }
        if (isset($row['provider_name'])) {
            $providers[$pid]['provider_name'] = $row['provider_name'];
        }
    }

    // 時間
    foreach ($timeList as $row) {
        if (!isset($row['provider_id'])) continue;
        $pid = $row['provider_id'];

        if (!isset($providers[$pid])) {
            $providers[$pid] = array(
                'provider_id' => $pid,
            );
        }
        if (isset($row['time_score'])) {
            $providers[$pid]['time_score'] = (float)$row['time_score'];
        }
    }

    // 内容（E5類似度）
    foreach ($contentList as $row) {
        if (!isset($row['provider_id'])) continue;
        $pid = $row['provider_id'];

        if (!isset($providers[$pid])) {
            $providers[$pid] = array(
                'provider_id' => $pid,
            );
        }
        if (isset($row['score'])) {
            $providers[$pid]['content_score'] = (float)$row['score'];
        }
    }

    // 足りないスコアを 0.0 で埋める
    foreach ($providers as $pid => $p) {
        if (!isset($providers[$pid]['distance_score'])) $providers[$pid]['distance_score'] = 0.0;
        if (!isset($providers[$pid]['time_score']))     $providers[$pid]['time_score']     = 0.0;
        if (!isset($providers[$pid]['content_score']))  $providers[$pid]['content_score']  = 0.0;
    }

    // 配列化
    $candidates = array_values($providers);

    // 表示用として、とりあえず「内容スコアの高い順」でソート（AI ではない）
    usort($candidates, 'compare_content_score');

    // =========================================================
    // 5. 最終レスポンス（統合結果のみ）
    // =========================================================
    $response = array(
        'request_id'   => $request_id,
        'request_text' => $request_text,
        'candidates'   => $candidates,
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => '内部エラー: ' . $e->getMessage(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// --------- usort 用 比較関数（内容スコアの降順） ---------
function compare_content_score($a, $b)
{
    $sa = isset($a['content_score']) ? $a['content_score'] : 0;
    $sb = isset($b['content_score']) ? $b['content_score'] : 0;
    if ($sa == $sb) return 0;
    return ($sa < $sb) ? 1 : -1; // 大きい方を前に
}
