<?php
// support_request_confirm.php
// 1回目のPOST: 確認画面表示
// 2回目のPOST (final_submit=1): DB登録＆完了画面

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/**
 * 子どもの年齢（月数）を表示用ラベルに変換
 *  6   -> 生後6か月
 *  12  -> 1歳
 *  24  -> 2歳
 *  30  -> 2歳6か月 など
 */
function format_child_age_label(?int $months): string
{
    if ($months === null) return '';

    if ($months < 12) {
        return "生後{$months}か月";
    }

    $years  = intdiv($months, 12);
    $remain = $months % 12;

    if ($remain === 0) {
        return "{$years}歳";
    } else {
        return "{$years}歳{$remain}か月";
    }
}

// POST 以外で来た場合はフォームへ戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: support_request.html');
    exit;
}

// --------------------------------------------------
// 2回目の POST（登録処理）かどうか判定
// --------------------------------------------------
if (isset($_POST['final_submit']) && $_POST['final_submit'] === '1') {
    // hidden で送られてきた値を受け取る
    $request_date = $_POST['request_date'] ?? null;
    $start_time   = $_POST['start_time'] ?? null;
    $end_time     = $_POST['end_time'] ?? null;
    $detail       = $_POST['detail'] ?? null;
    $note         = $_POST['note'] ?? null;
    $child_age    = isset($_POST['child_age']) ? (int)$_POST['child_age'] : null;

    // care_types は JSON 文字列で hidden に入れてある前提
    $care_types_json = $_POST['care_types_json'] ?? null;

    // ログイン中の会員ID（なければ null）
    $requester_member_id = $_SESSION['member_id'] ?? null;

    // 今回は住所・座標は一旦NULL（必要なら拡張）
    $address = null;
    $lat     = null;
    $lon     = null;

    // DB登録
    $dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
    $db_user = 'root';
    $db_pass = '';

    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            INSERT INTO support_requests (
                requester_member_id,
                request_date,
                start_time,
                end_time,
                address,
                detail,
                note,
                child_age,
                care_types_json,
                lat,
                lon
            ) VALUES (
                :requester_member_id,
                :request_date,
                :start_time,
                :end_time,
                :address,
                :detail,
                :note,
                :child_age,
                :care_types_json,
                :lat,
                :lon
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':requester_member_id', $requester_member_id, PDO::PARAM_INT);
        $stmt->bindValue(':request_date', $request_date);
        $stmt->bindValue(':start_time', $start_time);
        $stmt->bindValue(':end_time', $end_time);
        $stmt->bindValue(':address', $address);
        $stmt->bindValue(':detail', $detail);
        $stmt->bindValue(':note', $note);
        $stmt->bindValue(':child_age', $child_age, PDO::PARAM_INT);
        $stmt->bindValue(':care_types_json', $care_types_json);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lon', $lon);
        $stmt->execute();

        // 登録完了画面
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>援助依頼 登録完了</title>
          <link rel="stylesheet" href="style2.css">
        </head>
        <body>
        <header>
          <a href="login.html" class="title">流山市ファミリーサポートセンター</a>
        </header>
        <main class="request-page">
          <div class="request-card2">
            <h1 class="request-title">援助依頼の登録が完了しました</h1>
            <p>ご入力いただいた内容で援助依頼を受け付けました。</p>
            <div class="request-actions">
              <a href="user_menu.html" class="btn-submit">メニューに戻る</a>
            </div>
          </div>
        </main>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
          const savedColor = localStorage.getItem('headerColor');
          if (savedColor) {
            const header = document.querySelector('header');
            if (header) header.style.background = savedColor;
          }
        });
        </script>
        </body>
        </html>
        <?php
        exit;

    } catch (Exception $e) {
        // エラー表示（必要ならログ出力に変更）
        echo "登録中にエラーが発生しました: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
}

// --------------------------------------------------
// ここから 1回目の POST（確認画面）
// --------------------------------------------------

$request_date = $_POST['request_date'] ?? '';
$start_time   = $_POST['start_time'] ?? '';
$end_time     = $_POST['end_time'] ?? '';
$detail       = $_POST['detail'] ?? '';
$note         = $_POST['note'] ?? '';
$child_age    = isset($_POST['child_age']) ? (int)$_POST['child_age'] : null;

// チェックボックスの配列
$care_types          = $_POST['care_types'] ?? [];
$care_type_other_text = $_POST['care_type_other_text'] ?? null;

// DBには JSON形式で保存する（例: {"selected":[...],"other_text":"..."}）
$care_types_payload = [
    'selected'   => $care_types,
    'other_text' => $care_type_other_text !== '' ? $care_type_other_text : null,
];
$care_types_json = json_encode($care_types_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 表示用の年齢ラベル
$child_age_label = format_child_age_label($child_age);

// 安全なエスケープ
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>援助依頼内容の確認</title>
  <link rel="stylesheet" href="style2.css">
</head>
<body>
<header>
  <a href="login.html" class="title">流山市ファミリーサポートセンター</a>
</header>

<main class="request-page">
  <form class="request-card2" action="support_request_confirm.php" method="post">
    <h1 class="request-title">援助依頼内容の確認</h1>

    <section class="report-section">
      <h2 class="request-subtitle">希望日時</h2>
      <div class="report-row">
        <span class="report-field">希望日</span>
        <div><?= h($request_date) ?></div>
      </div>
      <div class="report-row">
        <span class="report-field">開始時間</span>
        <div><?= h($start_time) ?></div>
      </div>
      <div class="report-row">
        <span class="report-field">終了時間</span>
        <div><?= h($end_time) ?></div>
      </div>
    </section>

    <section class="report-section">
      <h2 class="request-subtitle">お子さんについて</h2>
      <div class="report-row">
        <span class="report-field">子どもの年齢</span>
        <div><?= h($child_age_label) ?></div>
      </div>

      <div class="report-row">
        <span class="report-field">依頼する預け方</span>
        <div>
          <?php if (!empty($care_types)): ?>
            <ul>
              <?php foreach ($care_types as $ct): ?>
                <li><?= h($ct) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>（選択なし）</p>
          <?php endif; ?>

          <?php if ($care_type_other_text !== ''): ?>
            <p>その他：<?= h($care_type_other_text) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="report-section">
      <h2 class="request-subtitle">依頼内容</h2>
      <div class="report-row">
        <span class="report-field full">依頼内容（できるだけ詳しく）</span>
        <div class="report-field full">
          <pre><?= h($detail) ?></pre>
        </div>
      </div>
    </section>

    <section class="report-section">
      <h2 class="request-subtitle">備考</h2>
      <div class="report-row">
        <span class="report-field full">備考（メモ用）</span>
        <div class="report-field full">
          <pre><?= h($note) ?></pre>
        </div>
      </div>
    </section>

    <!-- hidden で値を引き継ぐ -->
    <input type="hidden" name="request_date" value="<?= h($request_date) ?>">
    <input type="hidden" name="start_time" value="<?= h($start_time) ?>">
    <input type="hidden" name="end_time" value="<?= h($end_time) ?>">
    <input type="hidden" name="detail" value="<?= h($detail) ?>">
    <input type="hidden" name="note" value="<?= h($note) ?>">
    <input type="hidden" name="child_age" value="<?= h($child_age) ?>">
    <input type="hidden" name="care_types_json" value="<?= h($care_types_json) ?>">
    <input type="hidden" name="final_submit" value="1">

    <div class="request-actions">
      <button type="button" class="btn-back-link" onclick="history.back();">← 修正する</button>
      <button type="submit" class="btn-submit">この内容で登録する</button>
    </div>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const savedColor = localStorage.getItem('headerColor');
  if (savedColor) {
    const header = document.querySelector('header');
    if (header) header.style.background = savedColor;
  }
});
</script>
</body>
</html>
