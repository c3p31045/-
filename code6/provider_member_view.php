<?php
// provider_member_view.php
declare(strict_types=1);
session_start();

// 提供会員としてログインしているか確認
if (empty($_SESSION['provider_login']) || empty($_SESSION['provider_member_id'])) {
    header("Location: provider_login.html");
    exit;
}

$dsn     = 'mysql:host=127.0.0.1;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

$memberId = (int)$_SESSION['provider_member_id'];

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
function employment_label(?string $emp): string {
    switch ($emp) {
        case 'employee': return '雇用労働者';
        case 'fulltime': return 'フルタイム';
        case 'parttime': return 'パート';
        case 'self':     return '自営業';
        case 'jobless':  return '無職';
        case 'other':    return 'その他';
        default:         return '';
    }
}
function symbol_from_state(string $state): string {
    switch ($state) {
        case 'OK':    return '○';
        case 'MAYBE': return '△';
        case 'NG':
        default:      return '×';
    }
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ===== 提供会員の基本情報（members + users） =====
    $stmt = $pdo->prepare("
        SELECT m.*, u.email AS user_email
        FROM members m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = ? AND m.is_provider = 1
        LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new RuntimeException('提供会員情報が見つかりません。');
    }

    // 年齢（NULLなら計算）
    $age = $member['age'];
    if ($age === null && !empty($member['birthday'])) {
        $b = new DateTime($member['birthday']);
        $today = new DateTime('today');
        $age = $b->diff($today)->y;
    }

    // ===== provider_profiles =====
    $stmt = $pdo->prepare("
        SELECT * FROM provider_profiles
        WHERE member_id = ?
        LIMIT 1
    ");
    $stmt->execute([$memberId]);
    $profile = $stmt->fetch();
    $providerId = $profile ? (int)$profile['id'] : null;

    // ===== 取得免許（provider_licenses + m_license_types） =====
    $licensesText = '';
    if ($providerId !== null) {
        $stmt = $pdo->prepare("
            SELECT lt.name, pl.other_text
            FROM provider_licenses pl
            JOIN m_license_types lt ON pl.license_id = lt.id
            WHERE pl.provider_id = ?
            ORDER BY lt.name   -- ★ ここを pl.id から lt.name に変更
        ");
        $stmt->execute([$providerId]);
        $rows = $stmt->fetchAll();

        $parts = [];
        foreach ($rows as $r) {
            $name = $r['name'];
            if ($name === 'その他' && !empty($r['other_text'])) {
                $parts[] = $name . '（' . $r['other_text'] . '）';
            } else {
                $parts[] = $name;
            }
        }
        $licensesText = implode('、', $parts);
    }

    // ===== 活動可能曜日・時間帯（provider_availability） =====
    $weekLabels = [1=>'月',2=>'火',3=>'水',4=>'木',5=>'金',6=>'土',7=>'日'];
    $periods = ['am'=>'午前','pm'=>'午後']; // 夜間はUIでは使わない

    // デフォルトは「×」
    $grid = [];
    foreach ($periods as $pKey => $_) {
        foreach ($weekLabels as $w => $_lbl) {
            $grid[$pKey][$w] = '×';
        }
    }

    if ($providerId !== null) {
        $stmt = $pdo->prepare("
            SELECT weekday, period, state
            FROM provider_availability
            WHERE provider_id = ?
        ");
        $stmt->execute([$providerId]);
        while ($row = $stmt->fetch()) {
            $w = (int)$row['weekday'];
            $p = $row['period'];
            if (isset($grid[$p][$w])) {
                $grid[$p][$w] = symbol_from_state($row['state']);
            }
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo 'エラーが発生しました：' . h($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>提供会員データ</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style2.css">
</head>

<body>
<header class="appbar">
  <h1>流山市ファミリーサポートセンター</h1>
</header>

<main class="container5">
  <div class="card">
    <div class="card-header">
      提供会員データ
    </div>
    <div class="card-body">

      <div class="row">
        <label>名前</label>
        <div class="inline-3">
          <div class="input-like"><?php echo h($member['last_name'].' '.$member['first_name']); ?></div>
          <div class="input-like"><?php echo h(sex_label($member['sex'] ?? null)); ?></div>
          <div class="input-like"><?php echo h($age); ?> 歳</div>
        </div>
      </div>

      <div class="row">
        <label>住所</label>
        <div class="input-like"><?php echo h($member['address1']); ?></div>
      </div>

      <div class="row">
        <label>生年月日</label>
        <div class="input-like"><?php echo format_date_jp($member['birthday']); ?></div>
      </div>

      <div class="row">
        <label>職業・勤務先</label>
        <div class="inline-3">
          <div class="input-like"><?php echo h(employment_label($member['employment'])); ?></div>
          <div class="input-like" style="grid-column:span 2;"><?php echo h($member['workplace']); ?></div>
        </div>
      </div>

      <div class="row">
        <label>電話・メール</label>
        <div class="inline-3">
          <div class="input-like"><?php echo h($member['phone_home']); ?></div>
          <div class="input-like"><?php echo h($member['phone_mobile']); ?></div>
          <div class="input-like"><?php echo h($member['contact_email']); ?></div>
        </div>
      </div>

      <div class="row">
        <label>取得免許</label>
        <div class="input-like"><?php echo h($licensesText); ?></div>
      </div>

      <div class="row">
        <label>活動可能曜日・時間帯</label>
        <div>
          <table class="avail-table">
            <thead>
            <tr>
              <th>区分</th>
              <?php foreach ($weekLabels as $lbl): ?>
                <th><?php echo h($lbl); ?></th>
              <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($periods as $pKey => $pLabel): ?>
              <tr>
                <th><?php echo h($pLabel); ?></th>
                <?php foreach ($weekLabels as $w => $_lbl): ?>
                  <td><?php echo h($grid[$pKey][$w]); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <div style="margin-top:4px;font-size:12px;color:#555;">
            ○＝可能　／　△＝条件により可能　／　×＝不可
          </div>
        </div>
      </div>

      <?php if ($profile): ?>
        <div class="row">
          <label>自家用車送迎</label>
          <div class="input-like">
            <?php echo ((int)$profile['car_allowed'] === 1) ? '可' : '不可'; ?>
            （任意保険：
            <?php echo ($profile['insurance_status'] === 'have') ? '加入済み' : '未加入'; ?>）
          </div>
        </div>

        <div class="row">
          <label>ペット状況</label>
          <div class="textarea-like"><?php echo nl2br(h($profile['pets_notes'] ?? '')); ?></div>
        </div>

        <div class="row">
          <label>備考</label>
          <div class="textarea-like"><?php echo nl2br(h($profile['remarks'] ?? '')); ?></div>
        </div>
      <?php endif; ?>

      <a href="provider_dashboard.php" class="back-link">← メニューに戻る</a>

    </div>
  </div>
</main>
</body>
</html>
