<?php
// activity_report.php
session_start();

// 提供会員でログインしているか確認
if (empty($_SESSION['provider_member_id'])) {
    header("Location: login.html");
    exit;
}

$providerId = $_SESSION['provider_member_id'];

$dsn = "mysql:host=localhost;dbname=famisapo;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    exit("DB接続エラー：" . $e->getMessage());
}

/*
==========================================================
 ① 提供会員とマッチしている利用会員を取得
==========================================================
*/
$sql = "
SELECT DISTINCT
    m.id AS user_id,
    m.last_name,
    m.first_name,
    m.last_name_kana,
    m.first_name_kana,
    m.sex,
    m.birthday,
    m.age,
    m.postal_code,
    m.address1,
    m.phone_mobile,
    m.contact_email,
    m.has_spouse,
    m.num_children,
    m.cohabit_relation,
    m.cohabit_others
FROM confirmed_matches cm
JOIN support_requests sr ON sr.id = cm.request_id
JOIN members m ON m.id = sr.requester_member_id
WHERE cm.provider_id = :pid
ORDER BY m.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':pid' => $providerId]);
$users = $stmt->fetchAll();

$userIds = array_column($users, 'user_id');

/*
==========================================================
 ② 子ども情報（children）をまとめて取得
==========================================================
*/
$childrenMap = [];

if (!empty($userIds)) {
    $placeholders = implode(",", array_fill(0, count($userIds), "?"));

    $sqlChild = "
    SELECT
        c.id,
        c.member_id,
        c.last_name,
        c.first_name,
        c.last_name_kana,
        c.first_name_kana,
        c.sex,
        c.birthday,
        c.age,
        c.school,
        c.allergy_flag,
        c.notes
    FROM children c
    WHERE c.member_id IN ($placeholders)
    ORDER BY c.member_id, c.birthday
    ";

    $stmtChild = $pdo->prepare($sqlChild);
    $stmtChild->execute($userIds);

    while ($row = $stmtChild->fetch()) {
        $childrenMap[$row['member_id']][] = $row;
    }
}

function h($v) {
    return htmlspecialchars($v ?? "", ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>利用会員データ（マッチング済み）</title>
    <link rel="stylesheet" href="style2.css">

</head>
<body>
<header class="appbar">
  <h1>流山市ファミリーサポートセンター</h1>
</header>

<main class="staff-menu">
    <div class="staff-menu-inner">

        <div class="page-header">
            <a class="back-btn" href="provider_menu.html">← メニューに戻る</a>
            <h1 class="page-title">マッチングした利用会員データ</h1>
        </div>

        <?php if (empty($users)): ?>
            <p class="empty-message">現在、マッチングが確定している利用会員はいません。</p>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($users as $u): ?>
                    <section class="card">
                        <h2>
                            <?= h($u['last_name']) ?> <?= h($u['first_name']) ?>
                            （<?= h($u['last_name_kana']) ?> <?= h($u['first_name_kana']) ?>）
                        </h2>

                        <div class="info-row">
                            <div class="info-label">性別</div>
                            <div class="info-value"><?= h($u['sex']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">年齢</div>
                            <div class="info-value"><?= h($u['age']) ?> 歳</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">住所</div>
                            <div class="info-value">
                                〒<?= h($u['postal_code']) ?><br>
                                <?= h($u['address1']) ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">電話番号</div>
                            <div class="info-value"><?= h($u['phone_mobile']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">メール</div>
                            <div class="info-value"><?= h($u['contact_email']) ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">同居関係</div>
                            <div class="info-value">
                                <?= h($u['cohabit_relation']) ?> <?= h($u['cohabit_others']) ?>
                            </div>
                        </div>

                        <div class="card-section-title">子どもの情報</div>

                        <?php
                            $cid = $u['user_id'];
                            $children = $childrenMap[$cid] ?? [];
                        ?>

                        <?php if (empty($children)): ?>
                            <p class="info-value">登録された子どもの情報はありません。</p>
                        <?php else: ?>
                            <?php foreach ($children as $c): ?>
                                <div class="child-box">
                                    <div class="info-row">
                                        <div class="info-label">氏名</div>
                                        <div class="info-value">
                                            <?= h($c['last_name']) ?> <?= h($c['first_name']) ?>
                                            （<?= h($c['last_name_kana']) ?> <?= h($c['first_name_kana']) ?>）
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">性別</div>
                                        <div class="info-value"><?= h($c['sex']) ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">生年月日</div>
                                        <div class="info-value">
                                            <?= h($c['birthday']) ?>（<?= h($c['age']) ?>歳）
                                        </div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">園・学校</div>
                                        <div class="info-value"><?= h($c['school']) ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">アレルギー</div>
                                        <div class="info-value"><?= $c['allergy_flag'] ? "あり" : "なし" ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">備考</div>
                                        <div class="info-value"><?= nl2br(h($c['notes'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

</body>
</html>
