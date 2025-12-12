<?php
// user_member_tabs.php
declare(strict_types=1);
session_start();

// ログインチェック（user_login_check.php でセットしたセッションを利用）
if (empty($_SESSION['user_login']) || empty($_SESSION['user_id'])) {
    header("Location: user_login.html");
    exit;
}

$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

$userId = (int)$_SESSION['user_id'];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ===== 会員(保護者)情報を取得 =====
    $stmt = $pdo->prepare("
        SELECT *
        FROM members
        WHERE user_id = ? AND is_user = 1
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new RuntimeException('利用会員情報が見つかりません。');
    }

    $memberId = (int)$member['id'];

    // 緊急連絡先（1件目だけ）
    $stmt = $pdo->prepare("
        SELECT *
        FROM emergency_contacts
        WHERE member_id = ?
        ORDER BY id
        LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $emergency = $stmt->fetch() ?: null;

    // ===== 子ども情報を取得（複数想定） =====
    $stmt = $pdo->prepare("
        SELECT *
        FROM children
        WHERE member_id = ?
        ORDER BY id
    ");
    $stmt->execute([$memberId]);
    $children = $stmt->fetchAll();

} catch (Throwable $e) {
    http_response_code(500);
    echo 'エラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

// 表示用の整形
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function format_date_jp(?string $ymd): string {
    if (!$ymd) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    return $dt ? $dt->format('Y年n月j日') : '';
}
function sex_label(?string $sex): string {
    if ($sex === 'male') return '男';
    if ($sex === 'female') return '女';
    return '';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>利用会員データ表示</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style2.css">
</head>
<body>
<header class="appbar">
  <h1>流山市ファミリーサポートセンター</h1>
</header>

<main class="container3">
  <div class="card">
    <div class="card-header">
      <h2>利用会員データ</h2>
    </div>

    <!-- タブ -->
    <div class="tab-header">
      <button class="tab-btn active" data-tab="parent">保護者</button>
      <button class="tab-btn" data-tab="child">子供</button>
    </div>

    <!-- ▼ 保護者タブ -->
    <div id="tab-parent" class="tab-body">
      <div class="row">
        <label>名前</label>
        <div class="input-like"><?php echo h($member['last_name'] . ' ' . $member['first_name']); ?></div>
      </div>
      <div class="row">
        <label>住所</label>
        <div class="input-like"><?php echo h($member['address1']); ?></div>
      </div>
      <div class="row">
        <label>生年月日</label>
        <div class="input-like">
          <?php echo format_date_jp($member['birthday']); ?>
        </div>
      </div>
      <div class="row">
        <label>職業・勤務地</label>
        <div class="input-like">
          <?php
            $work = [];
            if (!empty($member['employment'])) $work[] = $member['employment'];
            if (!empty($member['workplace']))  $work[] = $member['workplace'];
            echo h(implode(' ／ ', $work));
          ?>
        </div>
      </div>
      <div class="row">
        <label>電話番号</label>
        <div class="input-like">
          <?php
            $phones = [];
            if (!empty($member['phone_home']))   $phones[] = '固定: '.$member['phone_home'];
            if (!empty($member['phone_mobile'])) $phones[] = '携帯: '.$member['phone_mobile'];
            echo h(implode(' ／ ', $phones));
          ?>
        </div>
      </div>
      <div class="row">
        <label>メールアドレス</label>
        <div class="input-like"><?php echo h($member['contact_email']); ?></div>
      </div>
      <div class="row">
        <label>緊急連絡先</label>
        <div class="input-like">
          <?php if ($emergency): ?>
            <?php echo h($emergency['name']); ?>
            （<?php echo h($emergency['relation']); ?>）
            ／ <?php echo h($emergency['phone']); ?>
          <?php endif; ?>
        </div>
      </div>

      <a href="user_dashboard.php" class="back-link">← メニューに戻る</a>
    </div>

    <!-- ▼ 子供タブ -->
    <div id="tab-child" class="tab-body hidden">
      <?php if (!$children): ?>
        <p>登録されているお子さんの情報はありません。</p>
      <?php else: ?>
        <?php foreach ($children as $index => $c): ?>
          <div class="child-block">
            <div class="child-title">
              お子さん <?php echo $index+1; ?> 人目
            </div>

            <div class="row">
              <label>名前</label>
              <div class="input-like">
                <?php echo h(($c['last_name'] ?? '').' '.($c['first_name'] ?? '')); ?>
              </div>
            </div>

            <div class="row">
              <label>性別</label>
              <div class="input-like">
                <?php echo h(sex_label($c['sex'] ?? null)); ?>
              </div>
            </div>

            <div class="row">
              <label>年齢</label>
              <div class="input-like">
                <?php
                  // children.age が NULL なら birthday から計算
                  $age = $c['age'];
                  if ($age === null && !empty($c['birthday'])) {
                    $age = (new DateTime($c['birthday']))->diff(new DateTime('today'))->y;
                  }
                  echo h($age).' 歳';
                ?>
              </div>
            </div>

            <div class="row">
              <label>アレルギー情報</label>
              <div class="textarea-like">
                <?php
                  $aflag = $c['allergy_flag'];
                  if ($aflag === null) {
                    echo '';
                  } elseif ((int)$aflag === 1) {
                    echo '有';
                  } else {
                    echo '無';
                  }
                ?>
              </div>
            </div>

            <div class="row">
              <label>特記事項</label>
              <div class="textarea-like">
                <?php echo nl2br(h($c['notes'] ?? '')); ?>
              </div>
            </div>

            <div class="row">
              <label>こども園・学校名</label>
              <div class="input-like">
                <?php echo h($c['school'] ?? ''); ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <p class="note-center">※ 2人目以降も順に表示しています。</p>
      <?php endif; ?>

    </div>

  </div>
</main>

<script>
// タブ切り替え
document.addEventListener('DOMContentLoaded', () => {
  const btns = document.querySelectorAll('.tab-btn');
  const parentTab = document.getElementById('tab-parent');
  const childTab  = document.getElementById('tab-child');

  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      btns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const tab = btn.dataset.tab;
      if (tab === 'parent') {
        parentTab.classList.remove('hidden');
        childTab.classList.add('hidden');
      } else {
        childTab.classList.remove('hidden');
        parentTab.classList.add('hidden');
      }
    });
  });
});
</script>
</body>
</html>
