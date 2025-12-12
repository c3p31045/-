<?php
// confirmed_list.php
// ログイン中の会員に紐づく「確定した援助日時」をカード形式で一覧表示

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

/*
  ★ セッションの取り方を柔軟にする
    - provider_login_check.php では  provider_member_id / provider_login を使用
    - user_login_check.php     では  user_login しかないので、本来は member_id も欲しいが、
      ここではまず provider 側が正しく動くことを優先
*/
$memberId =
    $_SESSION['member_id']
    ?? ($_SESSION['provider_member_id'] ?? null);   // 提供会員ログイン用

// role も、明示されていなければフラグから推定
$role = $_SESSION['role'] ?? (
    (!empty($_SESSION['provider_login']) ? 'provider'
     : (!empty($_SESSION['user_login']) ? 'user' : null))
);

// ログインしていない / 役割不明なら、とりあえず一覧は空表示にする
if (!$memberId || !$role) {
    $rows  = [];
    $error = null;
} else {
    $dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
    $db_user = 'root';
    $db_pass = '';

    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
              cm.id AS confirmed_id,
              cm.score,
              cm.created_at,

              sr.request_date,
              sr.start_time,
              sr.end_time,
              sr.detail AS request_text,

              rm.id           AS requester_member_id,
              rm.last_name    AS requester_last_name,
              rm.first_name   AS requester_first_name,
              rm.phone_mobile AS requester_phone_mobile,
              rm.phone_home   AS requester_phone_home,
              rm.postal_code  AS requester_postal_code,
              rm.address1     AS requester_address1,
              rm.num_children AS requester_num_children,

              pm.id           AS provider_member_id,
              pm.last_name    AS provider_last_name,
              pm.first_name   AS provider_first_name
            FROM confirmed_matches cm
            JOIN support_requests sr
              ON sr.id = cm.request_id
            JOIN members rm
              ON rm.id = sr.requester_member_id
            JOIN members pm
              ON pm.id = cm.provider_id
            WHERE 1=1
        ";

        $params = [];

        // ★ 本人の分だけ絞る
        if ($role === 'provider') {
            // 提供会員として関わっているマッチだけ
            $sql .= " AND cm.provider_id = :mid";
            $params[':mid'] = $memberId;
        } elseif ($role === 'user') {
            // 利用会員として関わっているマッチだけ
            $sql .= " AND sr.requester_member_id = :mid";
            $params[':mid'] = $memberId;
        } else {
            // その他（職員など）はここでは表示しない
            $rows  = [];
            $error = 'この画面は利用会員・提供会員のみが利用できます。';
        }

        if (!isset($rows)) {
            $sql .= " ORDER BY sr.request_date ASC, sr.start_time ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows  = $stmt->fetchAll();
            $error = null;
        }

    } catch (Exception $e) {
        $rows  = [];
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>確定した援助日時一覧</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* カード表示を少し整える（必要なら style.css に移してOK） */
    .fixed-card {
      max-width: 900px;
      margin: 24px auto;
      background: #fff;
      border-radius: 14px;
      padding: 20px 22px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .fixed-section {
      margin-bottom: 14px;
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 10px;
    }
    .fixed-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 4px;
    }
    .fixed-field {
      flex: 1 1 160px;
      min-width: 160px;
    }
    .fixed-label {
      display: inline-block;
      font-size: 11px;
      color: #6b7280;
      margin-bottom: 1px;
    }
    .fixed-value {
      display: block;
      font-size: 13px;
      color: #111827;
    }
    .user-info-box {
      background: #f9fafb;
      border-radius: 8px;
      padding: 6px 8px;
      margin-top: 4px;
      border: 1px solid #e5e7eb;
    }
    .user-info-title {
      font-size: 11px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 2px;
    }
  </style>
</head>
<body>

<header>
  <a href="login.html" class="title">流山市ファミリーサポートセンター</a>
</header>

<main class="fixed-page">
  <div class="fixed-card">
    <h1 class="fixed-title">確定した援助日時</h1>
    <p class="fixed-subtext">
      あなたに紐づくマッチング確定済みの援助日時の一覧です。<br>
      提供会員ログインの場合は、対応する利用会員の情報も表示されます。
    </p>

    <?php if (!empty($error)): ?>
      <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <p>現在確定している予定はありません。</p>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <?php
          $requesterName = trim(($r['requester_last_name'] ?? '') . ' ' . ($r['requester_first_name'] ?? ''));
          $providerName  = trim(($r['provider_last_name']  ?? '') . ' ' . ($r['provider_first_name']  ?? ''));
          $date  = $r['request_date'];
          $start = $r['start_time'] ? substr($r['start_time'], 0, 5) : '';
          $end   = $r['end_time']   ? substr($r['end_time'],   0, 5) : '';
          $phone =
              $r['requester_phone_mobile']
              ?: ($r['requester_phone_home'] ?: '');
          $addr  = trim(($r['requester_postal_code'] ? '〒'.$r['requester_postal_code'].' ' : '') .
                        ($r['requester_address1'] ?? ''));
          $numChildren = $r['requester_num_children'];
        ?>
        <section class="fixed-section">
          <div class="fixed-row">
            <div class="fixed-field">
              <span class="fixed-label">利用会員</span>
              <span class="fixed-value">
                <?= htmlspecialchars($requesterName ?: '（未指定）', ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div class="fixed-field">
              <span class="fixed-label">提供会員</span>
              <span class="fixed-value">
                <?= htmlspecialchars($providerName ?: '（未指定）', ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
          </div>

          <div class="fixed-row">
            <div class="fixed-field">
              <span class="fixed-label">日付</span>
              <span class="fixed-value">
                <?= htmlspecialchars($date ?? '', ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>
            <div class="fixed-field">
              <span class="fixed-label">時間</span>
              <span class="fixed-value">
                <?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($end): ?> 〜 <?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
              </span>
            </div>
          </div>

          <div class="fixed-row">
            <div class="fixed-field" style="flex:1 1 auto;">
              <span class="fixed-label">依頼内容</span>
              <span class="fixed-value">
                <?= nl2br(htmlspecialchars($r['request_text'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
              </span>
            </div>
          </div>

          <?php if ($role === 'provider'): ?>
            <!-- 提供会員でログインしているときは、利用会員情報を表示 -->
            <div class="user-info-box">
              <div class="user-info-title">利用会員情報</div>
              <div class="fixed-row">
                <div class="fixed-field">
                  <span class="fixed-label">氏名</span>
                  <span class="fixed-value">
                    <?= htmlspecialchars($requesterName ?: '（未登録）', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </div>
                <div class="fixed-field">
                  <span class="fixed-label">連絡先</span>
                  <span class="fixed-value">
                    <?= htmlspecialchars($phone ?: '（電話番号未登録）', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </div>
              </div>
              <div class="fixed-row">
                <div class="fixed-field" style="flex:2 1 auto;">
                  <span class="fixed-label">住所</span>
                  <span class="fixed-value">
                    <?= htmlspecialchars($addr ?: '（住所未登録）', ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </div>
                <div class="fixed-field">
                  <span class="fixed-label">子どもの人数</span>
                  <span class="fixed-value">
                    <?= htmlspecialchars(($numChildren === null ? '（未登録）' : $numChildren.'人'), ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </div>
              </div>
            </div>
          <?php endif; ?>

        </section>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const savedColor = localStorage.getItem('headerColor');
  if (savedColor) {
      document.querySelector('header').style.background = savedColor;
  }
});
</script>

</body>
</html>
