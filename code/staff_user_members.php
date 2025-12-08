<?php
session_start();

// 職員ログインチェック
if (empty($_SESSION['staff_login'])) {
    header("Location: staff_login.html");
    exit;
}

$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function h($v): string {
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
function yesno($v): string {
    return ((int)$v === 1) ? '有' : '無';
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 利用会員一覧
    $stmt = $pdo->query("
        SELECT m.*, u.login_id
        FROM members m
        JOIN users u ON m.user_id = u.id
        WHERE m.is_user = 1
        ORDER BY m.id
    ");
    $members = $stmt->fetchAll();

    $memberData = [];
    foreach ($members as $m) {
        $memberId = (int)$m['id'];

        // 緊急連絡先
        $stmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $emcs = $stmt->fetchAll();

        // 子ども
        $stmt = $pdo->prepare("SELECT * FROM children WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $children = $stmt->fetchAll();

        $memberData[] = [
            'member' => $m,
            'emcs'   => $emcs,
            'children' => $children,
        ];
    }

} catch (Throwable $e) {
    echo 'エラー：' . h($e->getMessage());
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>利用会員一覧（職員用）</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
/*** デザインは前と同じ ***/
:root{--brand:#0b6a57;--border:#d8e1e8;--bg:#f4f7fa;}
body{margin:0;font-family:"Noto Sans JP";background:var(--bg);}
.appbar{background:var(--brand);color:#fff;padding:12px;font-size:17px;}
.container{max-width:1080px;margin:24px auto;padding:0 16px;}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;margin-bottom:18px;}
.card-header{padding:12px;border-bottom:1px solid var(--border);font-weight:bold;}
.tab-header{display:flex;border-bottom:1px solid var(--border);}
.tab-btn{flex:1;padding:8px;text-align:center;cursor:pointer;background:#eee;border:none;}
.tab-btn.active{background:#fff;border-bottom:3px solid var(--brand);}
.tab-body{padding:16px;}
.row{display:grid;grid-template-columns:140px 1fr;margin-bottom:6px;}
.input-like{border:1px solid var(--border);padding:6px;border-radius:6px;}
.child-block,.emc-block{border:1px solid var(--border);padding:8px;border-radius:10px;margin-bottom:8px;}
.hidden{display:none;}
.no-data{color:#777;}
</style>
</head>
<body>

<div class="appbar">流山市ファミリーサポートセンター（職員用）</div>

<main class="container">
<h2>利用会員一覧</h2>

<?php if (!$memberData): ?>
  <p class="no-data">利用会員は登録されていません。</p>
<?php endif; ?>

<?php foreach ($memberData as $d): 
  $m = $d['member'];
  $children = $d['children'];
  $emcs = $d['emcs'];

  // 本人の年齢計算
  $age = $m['age'];
  if ($age === null && !empty($m['birthday'])) {
      $age = (new DateTime($m['birthday']))->diff(new DateTime())->y;
  }
?>
<div class="card">

  <div class="card-header">
    <?php echo h($m['last_name'].' '.$m['first_name']); ?>
    （ID: <?php echo h($m['id']); ?> / <?php echo h($m['login_id']); ?>）
  </div>

  <!-- タブ -->
  <div class="tab-header">
    <button class="tab-btn active" data-target="p<?php echo $m['id']; ?>">本人情報</button>
    <button class="tab-btn" data-target="c<?php echo $m['id']; ?>">子ども</button>
    <button class="tab-btn" data-target="e<?php echo $m['id']; ?>">緊急連絡先</button>
  </div>

  <!-- 本人 -->
  <div id="p<?php echo $m['id']; ?>" class="tab-body">
    <div class="row"><label>氏名</label><div class="input-like">
      <?php echo h($m['last_name'].' '.$m['first_name']); ?>
    </div></div>

    <div class="row"><label>ふりがな</label><div class="input-like">
      <?php echo h($m['last_name_kana'].' '.$m['first_name_kana']); ?>
    </div></div>

    <div class="row"><label>性別</label><div class="input-like">
      <?php echo sex_label($m['sex']); ?>
    </div></div>

    <div class="row"><label>年齢</label><div class="input-like">
      <?php echo h($age); ?> 歳
    </div></div>

    <div class="row"><label>郵便番号</label><div class="input-like"><?php echo h($m['postal_code']); ?></div></div>

    <div class="row"><label>住所</label><div class="input-like"><?php echo h($m['address1']); ?></div></div>

    <div class="row"><label>電話</label>
      <div class="input-like">
        固定：<?php echo h($m['phone_home']); ?><br>
        携帯：<?php echo h($m['phone_mobile']); ?>
      </div>
    </div>

    <div class="row"><label>メール</label>
      <div class="input-like"><?php echo h($m['contact_email']); ?></div>
    </div>

    <div class="row"><label>配偶者</label><div class="input-like"><?php echo yesno($m['has_spouse']); ?></div></div>

    <div class="row"><label>子ども人数</label><div class="input-like"><?php echo h($m['num_children']); ?></div></div>

    <div class="row"><label>同居人 続柄</label><div class="input-like"><?php echo h($m['cohabit_relation']); ?></div></div>

    <div class="row"><label>同居人 その他</label><div class="input-like"><?php echo h($m['cohabit_others']); ?></div></div>

  </div>

  <!-- 子ども -->
  <div id="c<?php echo $m['id']; ?>" class="tab-body hidden">
    <?php if (!$children): ?>
      <p class="no-data">子ども情報なし</p>
    <?php endif; ?>

    <?php foreach ($children as $c): 
      $cAge = $c['age'];
      if ($cAge === null && !empty($c['birthday'])) {
          $cAge = (new DateTime($c['birthday']))->diff(new DateTime())->y;
      }
    ?>
      <div class="child-block">
        <div class="row"><label>氏名</label><div class="input-like">
          <?php echo h($c['last_name'].' '.$c['first_name']); ?>
        </div></div>

        <div class="row"><label>ふりがな</label><div class="input-like">
          <?php echo h($c['last_name_kana'].' '.$c['first_name_kana']); ?>
        </div></div>

        <div class="row"><label>性別</label><div class="input-like">
          <?php echo sex_label($c['sex']); ?>
        </div></div>

        <div class="row"><label>年齢</label><div class="input-like">
          <?php echo h($cAge); ?> 歳
        </div></div>

        <div class="row"><label>生年月日</label><div class="input-like">
          <?php echo format_date_jp($c['birthday']); ?>
        </div></div>

        <div class="row"><label>学校</label><div class="input-like">
          <?php echo h($c['school']); ?>
        </div></div>

        <div class="row"><label>アレルギー</label><div class="input-like">
          <?php echo yesno($c['allergy_flag']); ?>
        </div></div>

        <div class="row"><label>特記事項</label><div class="input-like">
          <?php echo nl2br(h($c['notes'])); ?>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- 緊急連絡先 -->
  <div id="e<?php echo $m['id']; ?>" class="tab-body hidden">
    <?php if (!$emcs): ?>
      <p class="no-data">緊急連絡先なし</p>
    <?php endif; ?>

    <?php foreach ($emcs as $em): ?>
      <div class="emc-block">
        <div class="row"><label>氏名</label><div class="input-like"><?php echo h($em['name']); ?></div></div>
        <div class="row"><label>続柄</label><div class="input-like"><?php echo h($em['relation']); ?></div></div>
        <div class="row"><label>電話番号</label><div class="input-like"><?php echo h($em['phone']); ?></div></div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php endforeach; ?>

</main>

<script>
// タブ切替
document.querySelectorAll('.card').forEach(card=>{
  const btns = card.querySelectorAll('.tab-btn');
  const bodies = card.querySelectorAll('.tab-body');

  btns.forEach(btn=>{
    btn.onclick = ()=>{
      const target = btn.dataset.target;
      btns.forEach(b=>b.classList.remove('active'));
      bodies.forEach(b=>b.classList.add('hidden'));
      btn.classList.add('active');
      card.querySelector('#'+target).classList.remove('hidden');
    };
  });
});
</script>

</body>
</html>
