<?php
// ai_match_api.php
// match_api.php の結果をPython(AI)に渡して成立確率を付与する版
// ここでは Python FastAPI の /api/score-providers を叩く

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
    if ($request_id <= 0) {
        throw new Exception("request_id が不正です");
    }

    // -------------------------
    // 1. match_api.php を呼び出す
    // -------------------------
    $matchFile = __DIR__ . "/match_api.php";
    if (!file_exists($matchFile)) {
        throw new Exception("match_api.php が見つかりません");
    }

    $backup = $_GET;
    $_GET['request_id'] = $request_id;

    ob_start();
    include $matchFile;
    $match_json = ob_get_clean();

    $_GET = $backup;

    $match_data = json_decode($match_json, true);
    if (!$match_data || isset($match_data["error"])) {
        throw new Exception("match_api.php のレスポンスが不正: " . $match_json);
    }

    $candidates   = $match_data["candidates"];
    $request_text = $match_data["request_text"];

    if (empty($candidates)) {
        echo json_encode([
            "request_id"   => $request_id,
            "request_text" => $request_text,
            "candidates"   => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------
    // 2. Python FastAPI に candidates を送って成立確率を付与してもらう
    // -------------------------
    $pythonApiBase = 'http://127.0.0.1:8000';
    $url = rtrim($pythonApiBase, '/') . '/api/score-providers';

    $payload = json_encode($candidates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new Exception('candidates の JSON エンコードに失敗しました');
    }

    $ch = curl_init($url);
    if ($ch === false) {
        throw new Exception('curl_init に失敗しました');
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Python API(score-providers) 呼び出しエラー: ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('Python API(score-providers) HTTPエラー: ' . $httpCode . ' / レスポンス: ' . $response);
    }

    $ai_result = json_decode($response, true);
    if (!is_array($ai_result)) {
        throw new Exception('Python API(score-providers) の JSON が不正です: ' . $response);
    }

    // 降順にソート（expected_match_probability の大きい順）
    usort($ai_result, function($a, $b) {
        $pa = isset($a['expected_match_probability']) ? $a['expected_match_probability'] : 0.0;
        $pb = isset($b['expected_match_probability']) ? $b['expected_match_probability'] : 0.0;
        if ($pa == $pb) return 0;
        return ($pa < $pb) ? 1 : -1;
    });

    // -------------------------
    // 5. 最終レスポンス
    // -------------------------
    echo json_encode([
        "request_id"   => $request_id,
        "request_text" => $request_text,
        "candidates"   => $ai_result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
