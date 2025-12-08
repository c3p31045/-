<?php
// report_submit.php

session_start();

// （必要ならログインチェックを有効にしてください）
// if (empty($_SESSION['provider_login'])) {
//     header('Location: provider_login.html');
//     exit;
// }

// ====== DB 接続情報 ======
$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ====== フォーム以外から来たら報告フォームへ戻す ======
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report.html');
    exit;
}

// ====== フォーム値を取得 ======
$member_name   = trim($_POST['member_name'] ?? '');
$member_id     = trim($_POST['member_id'] ?? '');
$child_name    = trim($_POST['child_name'] ?? '');
$child_age     = ($_POST['child_age'] === '' ? null : (int)$_POST['child_age']);

$work_date     = $_POST['work_date'] ?? null;
$start_time    = $_POST['start_time'] ?? null;
$end_time      = $_POST['end_time'] ?? null;
$person_count  = ($_POST['person_count'] === '' ? 1 : (int)$_POST['person_count']);

$transport_fee = ($_POST['transport_fee'] === '' ? 0 : (int)$_POST['transport_fee']);
$meal_fee      = ($_POST['meal_fee']      === '' ? 0 : (int)$_POST['meal_fee']);
$reward_fee    = ($_POST['reward_fee']    === '' ? 0 : (int)$_POST['reward_fee']);

$place         = trim($_POST['place'] ?? '');
$note          = trim($_POST['note'] ?? '');

$total_hours   = ($_POST['total_hours']   === '' ? null : (float)$_POST['total_hours']);
$total_reward  = ($_POST['total_reward']  === '' ? null : (int)$_POST['total_reward']);

// ステータスは「送信済」で固定（下書きなどが必要なら拡張）
$status = 'submitted';

// ====== 簡単なバリデーション ======
$errors = [];
if ($member_name === '')  $errors[] = '会員名は必須です。';
if ($member_id   === '')  $errors[] = '会員番号は必須です。';
if (!$work_date)          $errors[] = '日付は必須です。';
if (!$start_time)         $errors[] = '開始時間は必須です。';
if (!$end_time)           $errors[] = '終了時間は必須です。';

if ($errors) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
      <meta charset="UTF-8">
      <title>活動報告 入力エラー</title>
      <style>
        body{font-family:"Noto Sans JP",sans-serif;background:#f4f7fa;margin:0;padding:40px 0;text-align:center;}
        .card{background:#fff;max-width:600px;margin:auto;padding:24px 18px;border-radius:14px;
              box-shadow:0 2px 10px rgba(13,34,45,.1);border:1px solid #d8e1e8;text-align:left;}
        h1{font-size:20px;margin-top:0;margin-bottom:12px;text-align:center;}
        ul{margin:0 0 16px 16px;padding:0;}
        li{color:#c62828;margin-bottom:4px;}
        a.btn{display:inline-block;margin-top:12px;padding:10px 20px;border-radius:999px;
              background:#0b6a57;color:#fff;text-decoration:none;font-weight:700;
              box-shadow:0 4px 0 #085343;}
        a.btn:active{transform:translateY(2px);box-shadow:0 2px 0 #085343;}
      </style>
    </head>
    <body>
      <div class="card">
        <h1>入力内容に不備があります</h1>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?php echo h($e); ?></li>
          <?php endforeach; ?>
        </ul>
        <div style="text-align:center;">
          <a href="report.html" class="btn">活動報告フォームに戻る</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ====== DB 登録処理 ======
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $sql = "
        INSERT INTO activity_reports (
          member_id, member_name, child_name, child_age,
          work_date, start_time, end_time, person_count,
          transport_fee, meal_fee, reward_fee,
          place, note,
          total_hours, total_reward,
          status, created_at, updated_at
        ) VALUES (
          :member_id, :member_name, :child_name, :child_age,
          :work_date, :start_time, :end_time, :person_count,
          :transport_fee, :meal_fee, :reward_fee,
          :place, :note,
          :total_hours, :total_reward,
          :status, NOW(), NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':member_id'     => $member_id,
        ':member_name'   => $member_name,
        ':child_name'    => $child_name,
        ':child_age'     => $child_age,
        ':work_date'     => $work_date,
        ':start_time'    => $start_time,
        ':end_time'      => $end_time,
        ':person_count'  => $person_count,
        ':transport_fee' => $transport_fee,
        ':meal_fee'      => $meal_fee,
        ':reward_fee'    => $reward_fee,
        ':place'         => $place,
        ':note'          => $note,
        ':total_hours'   => $total_hours,
        ':total_reward'  => $total_reward,
        ':status'        => $status,
    ]);

} catch (Throwable $e) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
      <meta charset="UTF-8">
      <title>活動報告 登録エラー</title>
      <style>
        body{font-family:"Noto Sans JP",sans-serif;background:#f4f7fa;margin:0;padding:40px 0;text-align:center;}
        .card{background:#fff;max-width:600px;margin:auto;padding:24px 18px;border-radius:14px;
              box-shadow:0 2px 10px rgba(13,34,45,.1);border:1px solid #d8e1e8;text-align:left;}
        h1{font-size:20px;margin-top:0;margin-bottom:12px;text-align:center;}
        .err{color:#c62828;font-size:14px;word-break:break-all;}
        a.btn{display:inline-block;margin-top:12px;padding:10px 20px;border-radius:999px;
              background:#0b6a57;color:#fff;text-decoration:none;font-weight:700;
              box-shadow:0 4px 0 #085343;}
        a.btn:active{transform:translateY(2px);box-shadow:0 2px 0 #085343;}
      </style>
    </head>
    <body>
      <div class="card">
        <h1>活動報告の登録に失敗しました</h1>
        <p class="err"><?php echo h($e->getMessage()); ?></p>
        <div style="text-align:center;">
          <a href="report.html" class="btn">活動報告フォームに戻る</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ====== 登録成功時の完了画面 ======
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>活動報告 完了</title>
  <style>
    body {
      font-family: "Noto Sans JP", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      background: #f4f7fa;
      margin: 0;
      padding: 40px 16px;
      color: #0b1b1f;
    }
    .header {
      max-width: 960px;
      margin: 0 auto 24px;
    }
    .header-title {
      font-size: 18px;
      font-weight: 700;
    }
    .card {
      background: #fff;
      max-width: 600px;
      margin: 0 auto;
      padding: 28px 20px 24px;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(13,34,45,.1);
      border: 1px solid #d8e1e8;
      text-align: center;
    }
    .card-title {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 10px;
    }
    .card-msg {
      font-size: 15px;
      line-height: 1.7;
      margin-bottom: 26px;
    }
    .btn-main {
      display: inline-block;
      padding: 12px 26px;
      border-radius: 999px;
      background: #0b6a57;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      letter-spacing: .05em;
      box-shadow: 0 4px 0 #085343;
    }
    .btn-main:active {
      transform: translateY(2px);
      box-shadow: 0 2px 0 #085343;
    }
    .btn-sub {
      display: inline-block;
      margin-top: 10px;
      font-size: 13px;
      color: #0b6a57;
      text-decoration: none;
    }
    .btn-sub:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="header">
  <div class="header-title">流山市ファミリーサポートセンター</div>
</div>

<div class="card">
  <div class="card-title">活動報告を送信しました</div>
  <div class="card-msg">
    援助活動の報告を受け付けました。<br>
    内容は事務局（職員）側で確認させていただきます。
  </div>

  <!-- ★ 提供会員閲覧会員画面（提供会員メニュー）への戻りボタン -->
  <a href="provider_menu.html" class="btn-main">提供会員メニューに戻る</a>

  <!-- 必要なら報告フォームへ戻るリンクも置いておく -->
  <div>
    <a href="report.html" class="btn-sub">別の活動報告を入力する</a>
  </div>
</div>

</body>
</html>
