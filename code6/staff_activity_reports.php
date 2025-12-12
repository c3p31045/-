<?php
// staff_activity_reports.php
session_start();

// 職員チェック
if (empty($_SESSION['staff_login'])) {
    header('Location: staff_login.html');
    exit;
}

$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 全件 or 必要なら WHERE status = 'submitted' にしてもOK
    $stmt = $pdo->query("
        SELECT *
        FROM activity_reports
        ORDER BY work_date DESC, id DESC
    ");
    $rows = $stmt->fetchAll();

} catch (Throwable $e) {
    echo 'エラー：' . h($e->getMessage());
    exit;
}

// CSVダウンロード要求（?download=1）
if (isset($_GET['download']) && $_GET['download'] === '1') {
    // CSVヘッダ
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="activity_reports.csv"');

    // Excel向けにBOM付与（任意）
    echo "\xEF\xBB\xBF";

    $fp = fopen('php://output', 'w');

    // ヘッダー行
    fputcsv($fp, [
        'ID','ステータス',
        '会員番号','会員名','子ども氏名','子ども年齢',
        '日付','開始','終了','人数',
        '交通費','食事代','報酬',
        '実施場所','備考',
        '合計時間','合計報酬',
    ]);

    foreach ($rows as $r) {
        fputcsv($fp, [
            $r['id'],
            $r['status'],
            $r['member_id'],
            $r['member_name'],
            $r['child_name'],
            $r['child_age'],
            $r['work_date'],
            $r['start_time'],
            $r['end_time'],
            $r['person_count'],
            $r['transport_fee'],
            $r['meal_fee'],
            $r['reward_fee'],
            $r['place'],
            $r['note'],
            $r['total_hours'],
            $r['total_reward'],
        ]);
    }

    fclose($fp);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style2.css">
<title>援助活動報告一覧（職員用）</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>

</style>
</head>
<body>

<div class="appbar">流山市ファミリーサポートセンター　職員画面</div>


<main class="container1">
  <h1>援助活動報告一覧</h1>

  <div class="actions">
    <a href="?download=1">CSVダウンロード</a>
    <a href="staff_menu.html">職員メニューに戻る</a>
  </div>

  <?php if (!$rows): ?>
    <p>登録された報告はありません。</p>
  <?php else: ?>
  <table class="hou1">
    <thead class="hou2">
      <tr class="hou3">
        <th class="hou3">ID</th>
        <th class="hou3">ステータス</th>
        <th class="hou3">会員番号</th>
        <th class="hou3">会員名</th>
        <th class="hou3">子ども氏名</th>
        <th class="hou3">日付</th>
        <th class="hou3">時間</th>
        <th class="hou3">人数</th>
        <th class="hou3">報酬合計</th>
        <th class="hou3">実施場所</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr class="hou3">
        <td class="hou3"><?php echo h($r['id']); ?></td>
        <td class="<?php echo ($r['status']==='draft'?'status-draft':'status-submitted'); ?>">
          <?php echo ($r['status']==='draft'?'下書き':'送信済'); ?>
        </td>
        <td class="hou3"><?php echo h($r['member_id']); ?></td>
        <td class="hou3"><?php echo h($r['member_name']); ?></td>
        <td class="hou3"><?php echo h($r['child_name']); ?></td>
        <td class="hou3"><?php echo h($r['work_date']); ?></td>
        <td class="hou3"><?php echo h($r['start_time'].'〜'.$r['end_time']); ?></td>
        <td class="hou3"><?php echo h($r['person_count']); ?></td>
        <td class="hou3"><?php echo h($r['total_reward'] ?? $r['reward_fee']); ?></td>
        <td class="hou3"><?php echo h($r['place']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>

</body>
</html>
