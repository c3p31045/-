<?php
declare(strict_types=1);
session_start();

// ログインしてなければログインへ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// ★ 事前に support_requests に以下のカラムを追加しておいてください
//   child_age        INT NULL
//   care_types_json  TEXT もしくは JSON 型

$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

function nullif_empty(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    return $v === '' ? null : $v;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('不正なアクセスです。');
    }

    // フォームからの入力値を取得
    $request_date = $_POST['request_date'] ?? '';
    $start_time   = $_POST['start_time']   ?? '';
    $end_time     = $_POST['end_time']     ?? '';
    $detail       = $_POST['detail']       ?? '';
    $note         = $_POST['note']         ?? '';

    // 追加：子どもの年齢
    $child_age_raw = $_POST['child_age'] ?? '';
    // 追加：預け方（複数チェックボックス）＋その他テキスト
    $care_types_post       = $_POST['care_types']          ?? [];
    $care_type_other_input = $_POST['care_type_other_text'] ?? null;

    // 必須チェック（HTML 側の required と同じ内容）
    if ($request_date === '' || $start_time === '' || $end_time === '' || trim($detail) === '') {
        throw new RuntimeException('必須項目が未入力です。');
    }

    // 子どもの年齢も必須（number required）として扱う
    if ($child_age_raw === '') {
        throw new RuntimeException('子どもの年齢が未入力です。');
    }
    $child_age = (int)$child_age_raw;
    if ($child_age < 0 || $child_age > 12) {
        throw new RuntimeException('子どもの年齢が範囲外です（0〜12歳）。');
    }

    // care_types は必ず配列として扱う
    $care_types = is_array($care_types_post) ? $care_types_post : [];
    // その他テキストは空なら null
    $care_type_other_text = nullif_empty($care_type_other_input);

    // 預け方情報を JSON でまとめて保存する
    $care_types_payload = [
        'selected'   => array_values($care_types),   // 選択された value の配列
        'other_text' => $care_type_other_text,       // その他に書かれた自由記述
    ];
    $care_types_json = json_encode(
        $care_types_payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($care_types_json === false) {
        throw new RuntimeException('預け方情報のエンコードに失敗しました。');
    }

    // DB 接続
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ログイン中ユーザーに紐づく「利用会員」レコードを取得
    $userId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare(
        'SELECT id, address1, lat, lon
           FROM members
          WHERE user_id = :uid
            AND is_user = 1
          LIMIT 1'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $member = $stmt->fetch();

    if (!$member) {
        throw new RuntimeException('利用会員情報が登録されていません。');
    }

    $requesterMemberId = (int)$member['id'];
    $address           = $member['address1'] ?? '';
    $lat               = $member['lat'];
    $lon               = $member['lon'];

    // 勘違い防止のため：support_requests の address は「活動場所」
    // 今回は利用会員の住所をそのまま入れる運用（自宅での預かり前提など）
    $sql = '
      INSERT INTO support_requests
        (requester_member_id,
         request_date,
         start_time,
         end_time,
         address,
         child_age,
         care_types_json,
         detail,
         note,
         created_at,
         lat,
         lon)
      VALUES
        (:requester_member_id,
         :request_date,
         :start_time,
         :end_time,
         :address,
         :child_age,
         :care_types_json,
         :detail,
         :note,
         NOW(),
         :lat,
         :lon)
    ';

    $stmtIns = $pdo->prepare($sql);
    $stmtIns->bindValue(':requester_member_id', $requesterMemberId, PDO::PARAM_INT);
    $stmtIns->bindValue(':request_date',  $request_date);
    $stmtIns->bindValue(':start_time',    $start_time);
    $stmtIns->bindValue(':end_time',      $end_time);
    $stmtIns->bindValue(':address',       $address); // フォームからではなく会員情報
    $stmtIns->bindValue(':child_age',     $child_age, PDO::PARAM_INT);
    $stmtIns->bindValue(':care_types_json', $care_types_json);
    $stmtIns->bindValue(':detail',        $detail);
    $stmtIns->bindValue(':note',          nullif_empty($note));
    $stmtIns->bindValue(':lat',           $lat);
    $stmtIns->bindValue(':lon',           $lon);
    $stmtIns->execute();

    echo '援助依頼を登録しました。';

} catch (Throwable $e) {
    http_response_code(500);
    echo 'エラーが発生しました: ' .
         htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
