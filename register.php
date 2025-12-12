<?php
declare(strict_types=1);

// ===== DB接続設定 =====
$dsn     = 'mysql:host=localhost;dbname=famisapo;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

// ○/△/× → ENUM的な値にマッピング
function mapAvailabilitySymbol(string $v): string {
    $v = trim($v);
    if ($v === '○') return 'OK';
    if ($v === '△') return 'MAYBE';
    return 'NG';
}

// 空文字 → null
function nullif_empty($v) {
    if (!isset($v)) return null;
    if (is_string($v)) {
        $v = trim($v);
        return ($v === '') ? null : $v;
    }
    return $v;
}

// Y,M,D → 'YYYY-MM-DD' or null
function ymd_or_null($y, $m, $d): ?string {
    $y = (int)($y ?? 0);
    $m = (int)($m ?? 0);
    $d = (int)($d ?? 0);
    if (!$y || !$m || !$d) return null;
    if (!checkdate($m, $d, $y)) return null;
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

// 'YYYY-MM-DD' → 年齢（今日ベース）
function calc_age_from_date(?string $ymd): ?int {
    if (!$ymd) return null;
    $birth = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$birth) return null;
    $today = new DateTime('today');
    if ($birth > $today) return null;
    return (int)$birth->diff($today)->y;
}

/**
 * 住所 → 緯度経度
 * - 郵便番号＋住所を結合して検索
 * - OpenStreetMap Nominatim を使用
 */
/**
 * 住所 → 緯度経度
 * - 郵便番号＋住所を結合して検索
 * - OpenStreetMap Nominatim を使用
 * - 郵便番号は「123-4567」形式に自動整形
 */
/**
 * 郵便番号＋住所から緯度経度を取得する
 * - 郵便番号は「123-4567」形式に自動整形
 * - OpenStreetMap Nominatim を利用
 * - 失敗時は null を返す
 * - 今回はデバッグ用にログもたくさん吐く
 */
function geocode_address(string $postal, string $address): ?array {
    // --- 入力の確認ログ（デバッグ用） ---
    error_log('geocode_address INPUT postal=' . $postal . ' address=' . $address);

    // 前処理
    $postal  = trim($postal);
    $address = trim($address);

    // 郵便番号を数字だけにして7桁なら 123-4567 に整形
    if ($postal !== '') {
        $digits = preg_replace('/[^0-9]/', '', $postal);
        if (strlen($digits) === 7) {
            $postal = substr($digits, 0, 3) . '-' . substr($digits, 3);
        } else {
            // 桁数がおかしい場合は郵便番号なし扱い
            error_log('geocode_address: invalid postal length -> ' . $postal);
            $postal = '';
        }
    }

    // 郵便番号も住所も空なら何もしない
    if ($postal === '' && $address === '') {
        error_log('geocode_address: both postal and address empty');
        return null;
    }

    // 検索クエリ
    $queryText = '';
    if ($postal !== '') {
        $queryText .= $postal . ' ';
    }
    $queryText .= $address;

    error_log('geocode_address QUERY=' . $queryText);

    $params = [
        'q'      => $queryText,
        'format' => 'json',
        'limit'  => 1,
    ];
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);

    $json = null;

    // まず cURL で試す
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'famisapo-nagareyama/1.0 (contact@example.com)',
        ]);
        $json = curl_exec($ch);
        if ($json === false) {
            error_log('geocode_address: curl_exec error: ' . curl_error($ch));
            $json = null;
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            error_log('geocode_address: curl HTTP status=' . $httpCode);
            if ($httpCode !== 200) {
                $json = null;
            }
        }
        curl_close($ch);
    } else {
        error_log('geocode_address: curl_init not available, fallback to file_get_contents');
    }

    // cURL で失敗した場合は file_get_contents を試す
    if ($json === null) {
        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: famisapo-nagareyama/1.0 (contact@example.com)\r\n",
                'timeout' => 5,
            ],
        ];
        $context = stream_context_create($opts);
        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            error_log('geocode_address: file_get_contents failed for ' . $queryText);
            return null;
        } else {
            error_log('geocode_address: file_get_contents success');
        }
    }

    $data = json_decode($json, true);
    if (!is_array($data) || empty($data)) {
        error_log('geocode_address: no result for ' . $queryText);
        return null;
    }

    $lat = isset($data[0]['lat']) ? (float)$data[0]['lat'] : null;
    $lon = isset($data[0]['lon']) ? (float)$data[0]['lon'] : null;

    error_log('geocode_address RESULT lat=' . $lat . ' lon=' . $lon);

    if ($lat === null || $lon === null) {
        return null;
    }

    return [
        'lat' => $lat,
        'lon' => $lon,
    ];
}


// マスタテーブル（m_license_types / m_activities）に name で行を保証してidを返す
function ensure_master_id(PDO $pdo, string $table, string $name): int {
    if (!in_array($table, ['m_license_types', 'm_activities'], true)) {
        throw new InvalidArgumentException('invalid master table');
    }
    $name = trim($name);
    if ($name === '') {
        throw new InvalidArgumentException('empty master name');
    }

    $sqlSelect = "SELECT id FROM {$table} WHERE name = :name LIMIT 1";
    $stmt = $pdo->prepare($sqlSelect);
    $stmt->execute([':name' => $name]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $sqlInsert = "INSERT INTO {$table} (name) VALUES (:name)";
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([':name' => $name]);
    return (int)$pdo->lastInsertId();
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ===== 入力取得 =====
    $member       = $_POST['member']       ?? [];
    $login        = $_POST['login']        ?? [];
    $children     = $_POST['children']     ?? [];
    $emergency    = $_POST['emergency']    ?? [];
    $provider     = $_POST['provider']     ?? [];
    $availability = $_POST['availability'] ?? [];

    // ログイン必須チェック
    if (empty($login['login_id']) || empty($login['password']) || empty($login['password_confirm'])) {
        throw new RuntimeException('ログイン情報が不足しています。');
    }
    if ($login['password'] !== $login['password_confirm']) {
        throw new RuntimeException('パスワード（確認用）と一致しません。');
    }

    $pdo->beginTransaction();

    // ===== users =====
    $loginId = trim($login['login_id']);
    if ($loginId === '') {
        throw new RuntimeException('ログインIDが空です。');
    }

    $contactEmail = nullif_empty($member['contact_email'] ?? null);
    if (!$contactEmail) {
        $contactEmail = $loginId . '@local.invalid';
    }

    $sqlUser = 'INSERT INTO users (login_id,email,password_hash,is_staff)
                VALUES (:login_id,:email,:password_hash,0)';
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->bindValue(':login_id', $loginId);
    $stmtUser->bindValue(':email', $contactEmail);
    $stmtUser->bindValue(':password_hash', password_hash($login['password'], PASSWORD_DEFAULT));
    $stmtUser->execute();
    $userId = (int)$pdo->lastInsertId();

    // ===== members =====
    $birthday = ymd_or_null(
        $member['birth_year']  ?? null,
        $member['birth_month'] ?? null,
        $member['birth_day']   ?? null
    );
    $age = calc_age_from_date($birthday);

    $memberType  = $member['member_type'] ?? '';
    $is_user     = (int)in_array($memberType, ['user','both'], true);
    $is_provider = (int)in_array($memberType, ['provider','both'], true);

    $employment = null;
    if (!empty($member['workstyle'])) {
        $employment = $member['workstyle'];
    } elseif (!empty($member['self_employed'])) {
        $employment = 'self';
    } elseif (!empty($member['jobless'])) {
        $employment = 'jobless';
    } elseif (!empty($member['work_other_flag'])) {
        $employment = 'other';
    }

    $postalCode  = nullif_empty($member['postal_code'] ?? null);
    $addressText = nullif_empty($member['address1']  ?? null);

    $lat = null;
    $lon = null;
    if ($postalCode !== null || $addressText !== null) {
        $geo = geocode_address($postalCode ?? '', $addressText ?? '');
        if ($geo !== null) {
            $lat = $geo['lat'];
            $lon = $geo['lon'];
        }
    }

    $sqlMember = '
      INSERT INTO members
      (user_id,last_name,first_name,last_name_kana,first_name_kana,sex,birthday,age,
       employment,workplace,postal_code,address1,lat,lon,phone_home,phone_mobile,contact_email,
       has_spouse,num_children,cohabit_relation,cohabit_others,is_user,is_provider)
      VALUES
      (:user_id,:last_name,:first_name,:last_name_kana,:first_name_kana,:sex,:birthday,:age,
       :employment,:workplace,:postal_code,:address1,:lat,:lon,:phone_home,:phone_mobile,:contact_email,
       :has_spouse,:num_children,:cohabit_relation,:cohabit_others,:is_user,:is_provider)
    ';
    $stmtMember = $pdo->prepare($sqlMember);
    $stmtMember->bindValue(':user_id',        $userId, PDO::PARAM_INT);
    $stmtMember->bindValue(':last_name',      $member['last_name']        ?? '');
    $stmtMember->bindValue(':first_name',     $member['first_name']       ?? '');
    $stmtMember->bindValue(':last_name_kana', nullif_empty($member['last_name_kana']  ?? null));
    $stmtMember->bindValue(':first_name_kana',nullif_empty($member['first_name_kana'] ?? null));
    $stmtMember->bindValue(':sex',           $member['sex']              ?? null);
    $stmtMember->bindValue(':birthday',      $birthday);
    $stmtMember->bindValue(':age',           $age, PDO::PARAM_INT);
    $stmtMember->bindValue(':employment',    $employment);
    $stmtMember->bindValue(':workplace',     nullif_empty($member['workplace'] ?? null));
    $stmtMember->bindValue(':postal_code',   $postalCode);
    $stmtMember->bindValue(':address1',      $addressText);
    $stmtMember->bindValue(':lat',           $lat);
    $stmtMember->bindValue(':lon',           $lon);
    $stmtMember->bindValue(':phone_home',    nullif_empty($member['phone_home']   ?? null));
    $stmtMember->bindValue(':phone_mobile',  nullif_empty($member['phone_mobile'] ?? null));
    $stmtMember->bindValue(':contact_email', $contactEmail);
    $stmtMember->bindValue(':has_spouse',    isset($member['has_spouse'])   ? (int)$member['has_spouse']   : null, PDO::PARAM_INT);
    $stmtMember->bindValue(':num_children',  isset($member['num_children']) ? (int)$member['num_children'] : 0,    PDO::PARAM_INT);
    $stmtMember->bindValue(':cohabit_relation', nullif_empty($member['cohabit_relation'] ?? null));
    $stmtMember->bindValue(':cohabit_others',   nullif_empty($member['cohabit_others']   ?? null));
    $stmtMember->bindValue(':is_user',      $is_user,     PDO::PARAM_INT);
    $stmtMember->bindValue(':is_provider',  $is_provider, PDO::PARAM_INT);
    $stmtMember->execute();
    $memberId = (int)$pdo->lastInsertId();

    // ===== emergency_contacts =====
    if (!empty($emergency['name']) && !empty($emergency['phone'])) {
        $sqlEm = 'INSERT INTO emergency_contacts (member_id,name,relation,phone)
                  VALUES (:member_id,:name,:relation,:phone)';
        $stmtEm = $pdo->prepare($sqlEm);
        $stmtEm->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmtEm->bindValue(':name',      $emergency['name']);
        $stmtEm->bindValue(':relation',  nullif_empty($emergency['relation'] ?? null));
        $stmtEm->bindValue(':phone',     $emergency['phone']);
        $stmtEm->execute();
    }

    // ===== children =====
    if (!empty($children) && is_array($children)) {
        $sqlChild = '
          INSERT INTO children
          (member_id,last_name,first_name,last_name_kana,first_name_kana,sex,birthday,age,school,allergy_flag,notes)
          VALUES
          (:member_id,:last_name,:first_name,:last_name_kana,:first_name_kana,:sex,:birthday,:age,:school,:allergy_flag,:notes)
        ';
        $stmtChild = $pdo->prepare($sqlChild);

        foreach ($children as $c) {
            $hasSomething = false;
            foreach (['last_name','first_name','birth_year','birth_month','birth_day','school'] as $k) {
                if (!empty($c[$k])) { $hasSomething = true; break; }
            }
            if (!$hasSomething) continue;

            $childBirthday = ymd_or_null(
                $c['birth_year'] ?? null,
                $c['birth_month']?? null,
                $c['birth_day']  ?? null
            );
            $childAge = calc_age_from_date($childBirthday);

            $stmtChild->bindValue(':member_id',      $memberId, PDO::PARAM_INT);
            $stmtChild->bindValue(':last_name',      $c['last_name']  ?? '');
            $stmtChild->bindValue(':first_name',     $c['first_name'] ?? '');
            $stmtChild->bindValue(':last_name_kana', nullif_empty($c['last_name_kana']  ?? null));
            $stmtChild->bindValue(':first_name_kana',nullif_empty($c['first_name_kana'] ?? null));
            $stmtChild->bindValue(':sex',           $c['sex'] ?? null);
            $stmtChild->bindValue(':birthday',      $childBirthday);
            $stmtChild->bindValue(':age',           $childAge, PDO::PARAM_INT);
            $stmtChild->bindValue(':school',        nullif_empty($c['school'] ?? null));
            $stmtChild->bindValue(':allergy_flag',  isset($c['allergy_flag']) ? (int)$c['allergy_flag'] : null, PDO::PARAM_INT);
            $stmtChild->bindValue(':notes',         nullif_empty($c['notes'] ?? null));
            $stmtChild->execute();
        }
    }

    // ===== provider関連 =====
    if ($is_provider) {
        // provider_profiles
        $sqlProf = '
          INSERT INTO provider_profiles (member_id,car_allowed,insurance_status,pets_notes,remarks)
          VALUES (:member_id,:car_allowed,:insurance_status,:pets_notes,:remarks)
        ';
        $stmtProf = $pdo->prepare($sqlProf);
        $stmtProf->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmtProf->bindValue(':car_allowed',
            (isset($provider['car']) && $provider['car'] === 'allow') ? 1 : 0,
            PDO::PARAM_INT
        );
        $stmtProf->bindValue(':insurance_status',
            (isset($provider['insurance']) && $provider['insurance'] === 'have') ? 'have' : 'none'
        );
        $stmtProf->bindValue(':pets_notes', nullif_empty($provider['pets_notes'] ?? null));
        $stmtProf->bindValue(':remarks',    nullif_empty($provider['remarks']   ?? null));
        $stmtProf->execute();
        $providerId = (int)$pdo->lastInsertId();

        // provider_licenses
        if (!empty($provider['licenses']) && is_array($provider['licenses'])) {
            $sqlPL = '
              INSERT INTO provider_licenses (provider_id,license_id,other_text)
              VALUES (:provider_id,:license_id,:other_text)
            ';
            $stmtPL = $pdo->prepare($sqlPL);
            $otherText = nullif_empty($provider['license_other_text'] ?? null);

            foreach ($provider['licenses'] as $label) {
                $label = trim((string)$label);
                if ($label === '') continue;
                $lid = ensure_master_id($pdo, 'm_license_types', $label);

                $stmtPL->bindValue(':provider_id', $providerId, PDO::PARAM_INT);
                $stmtPL->bindValue(':license_id',  $lid,        PDO::PARAM_INT);
                $stmtPL->bindValue(':other_text', ($label === 'その他') ? $otherText : null);
                $stmtPL->execute();
            }
        }

        // provider_activities
        if (!empty($provider['activities']) && is_array($provider['activities'])) {
            $sqlPA = '
              INSERT INTO provider_activities (provider_id,activity_id,other_text)
              VALUES (:provider_id,:activity_id,:other_text)
            ';
            $stmtPA = $pdo->prepare($sqlPA);

            foreach ($provider['activities'] as $label) {
                $label = trim((string)$label);
                if ($label === '') continue;
                $aid = ensure_master_id($pdo, 'm_activities', $label);

                $stmtPA->bindValue(':provider_id', $providerId, PDO::PARAM_INT);
                $stmtPA->bindValue(':activity_id', $aid,        PDO::PARAM_INT);
                $stmtPA->bindValue(':other_text',  ($label === 'その他') ? 'その他' : null);
                $stmtPA->execute();
            }
        }

        // provider_availability（午前・午後のみ）
        if (!empty($availability) && is_array($availability)) {
            $sqlAV = '
              INSERT INTO provider_availability (provider_id,weekday,period,state)
              VALUES (:provider_id,:weekday,:period,:state)
              ON DUPLICATE KEY UPDATE state = VALUES(state)
            ';
            $stmtAV = $pdo->prepare($sqlAV);

            foreach ($availability as $weekday => $periods) {
                $weekday = (int)$weekday;
                if ($weekday < 1 || $weekday > 7) continue;

                foreach (['am','pm'] as $period) { // night は無し
                    if (!isset($periods[$period])) continue;
                    $state = mapAvailabilitySymbol((string)$periods[$period]);

                    $stmtAV->bindValue(':provider_id', $providerId, PDO::PARAM_INT);
                    $stmtAV->bindValue(':weekday',     $weekday,    PDO::PARAM_INT);
                    $stmtAV->bindValue(':period',      $period);
                    $stmtAV->bindValue(':state',       $state);
                    $stmtAV->execute();
                }
            }
        }
    }

    $pdo->commit();

    header('Location: register_complete.php');
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo '登録に失敗しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
