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

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function format_date_jp(?string $ymd){
    if (!$ymd) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    return $dt ? $dt->format('Y年n月j日') : '';
}
function sex_label($sex){
    if ($sex === 'male') return '男';
    if ($sex === 'female') return '女';
    return '';
}
function yesno($v){ return ((int)$v === 1) ? '有' : '無'; }
function symbol_from_state($state){
    return match($state){
        'OK'    => '○',
        'MAYBE' => '△',
        default => '×',
    };
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 提供会員
    $stmt = $pdo->query("
        SELECT m.*, u.login_id
        FROM members m
        JOIN users u ON m.user_id = u.id
        WHERE m.is_provider = 1
        ORDER BY m.id
    ");
    $members = $stmt->fetchAll();

    $providerList = [];

    foreach ($members as $m){
        $mid = (int)$m['id'];

        // provider_profiles
        $stmt = $pdo->prepare("SELECT * FROM provider_profiles WHERE member_id = ?");
        $stmt->execute([$mid]);
        $profile = $stmt->fetch();

        $pid = $profile['id'] ?? null;

        // 資格一覧
        $licenses = [];
        if ($pid){
            $stmt = $pdo->prepare("
                SELECT lt.name, pl.other_text
                FROM provider_licenses pl
                JOIN m_license_types lt ON pl.license_id = lt.id
                WHERE pl.provider_id = ?
            ");
            $stmt->execute([$pid]);
            foreach ($stmt as $row){
                if ($row['name'] === 'その他' && $row['other_text']){
                    $licenses[] = 'その他（'.h($row['other_text']).'）';
                } else {
                    $licenses[] = $row['name'];
                }
            }
        }

        // 活動可能時間（夜間なし）
        $week = [1=>'月',2=>'火',3=>'水',4=>'木',5=>'金',6=>'土',7=>'日'];
        $periods = ['am'=>'午前','pm'=>'午後'];
        $grid = [];

        foreach ($periods as $pkey => $_){
            foreach ($week as $w => $_2){
                $grid[$pkey][$w] = '×';
            }
        }

        if ($pid){
            $stmt = $pdo->prepare("SELECT * FROM provider_availability WHERE provider_id = ?");
            $stmt->execute([$pid]);
            foreach ($stmt as $r){
                $grid[$r['period']][$r['weekday']] = symbol_from_state($r['state']);
            }
        }

        $providerList[] = [
            'member'  => $m,
            'profile' => $profile,
            'licenses'=> $licenses,
            'grid'    => $grid,
        ];
    }

} catch(Throwable $e){
    echo "エラー: " . h($e->getMessage());
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>提供会員一覧（職員用）</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style2.css">
</head>

<body>

<div class="appbar">流山市ファミリーサポートセンター（職員用）</div>

<main class="container">
<h2>提供会員一覧</h2>

<?php if (!$providerList): ?>
<p class="no-data">提供会員は登録されていません。</p>
<?php endif; ?>

<?php foreach ($providerList as $row): 
  $m = $row['member'];
  $pr = $row['profile'];
  $lic = $row['licenses'];
  $grid = $row['grid'];

  $age = $m['age'];
  if ($age === null && $m['birthday']){
      $age = (new DateTime($m['birthday']))->diff(new DateTime())->y;
  }
?>

<div class="card">

  <div class="card-header">
    <?php echo h($m['last_name'].' '.$m['first_name']); ?>
    （ID: <?php echo h($m['id']); ?> / <?php echo h($m['login_id']); ?>）
  </div>

  <div class="card-body" style="padding:16px;">

    <div class="row">
      <label>基本情報</label>
      <div class="inline-3">
        <div class="input-like"><?php echo h($m['last_name'].' '.$m['first_name']); ?></div>
        <div class="input-like"><?php echo sex_label($m['sex']); ?></div>
        <div class="input-like"><?php echo h($age); ?>歳</div>
      </div>
    </div>

    <div class="row"><label>住所</label><div class="input-like"><?php echo h($m['address1']); ?></div></div>

    <div class="row"><label>生年月日</label><div class="input-like"><?php echo format_date_jp($m['birthday']); ?></div></div>

    <div class="row">
      <label>連絡先</label>
      <div class="inline-3">
        <div class="input-like">固定：<?php echo h($m['phone_home']); ?></div>
        <div class="input-like">携帯：<?php echo h($m['phone_mobile']); ?></div>
        <div class="input-like">メール：<?php echo h($m['contact_email']); ?></div>
      </div>
    </div>

    <div class="row">
      <label>資格・免許</label>
      <div class="input-like">
        <?php echo $lic ? h(implode('、', $lic)) : 'なし'; ?>
      </div>
    </div>

    <div class="row">
      <label>活動可能日時</label>
      <div>
        <table class="avail-table">
          <thead>
            <tr>
              <th>区分</th>
              <th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>午前</th>
              <?php foreach ($grid['am'] as $v) echo "<td>".h($v)."</td>"; ?>
            </tr>
            <tr>
              <th>午後</th>
              <?php foreach ($grid['pm'] as $v) echo "<td>".h($v)."</td>"; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($pr): ?>
    <div class="row">
      <label>自家用車送迎</label>
      <div class="input-like">
        <?php echo yesno($pr['car_allowed']); ?>
        （保険：<?php echo ($pr['insurance_status']==='have'?'加入済':'未加入'); ?>）
      </div>
    </div>

    <div class="row"><label>ペット</label><div class="input-like"><?php echo nl2br(h($pr['pets_notes'])); ?></div></div>

    <div class="row"><label>備考</label><div class="input-like"><?php echo nl2br(h($pr['remarks'])); ?></div></div>
    <?php endif; ?>

  </div>

</div>

<?php endforeach; ?>

</main>
</body>
</html>
