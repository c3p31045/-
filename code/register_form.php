<?php
// UTF-8(BOMなし)で保存してください
// このファイルはフォーム表示用。送信先は register.php です。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>流山市ファミリーサポートセンター—新規会員登録</title>
  <style>
    :root { --brand:#0b6a57; --brand-dark:#085343; --ink:#0b1b1f; --muted:#5c6b73; --border:#d8e1e8; --bg:#f4f7fa; --danger:#c62828; --accent:#2f855a; --chip:#edf7f2; }
    *{box-sizing:border-box} html,body{height:100%} body{margin:0;font-family:"Noto Sans JP",system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);background:var(--bg);line-height:1.7}
    .appbar{position:sticky;top:0;z-index:10;display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--brand);color:#fff;box-shadow:0 1px 0 rgba(0,0,0,.1)}
    .appbar .menu{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.25);cursor:pointer}
    .appbar .menu span{width:18px;height:2px;background:#fff;display:block;position:relative}
    .appbar .menu span::before,.appbar .menu span::after{content:"";position:absolute;inset:0}
    .appbar .menu span::before{transform:translateY(-6px);background:currentColor;height:2px}
    .appbar .menu span::after{transform:translateY(6px);background:currentColor;height:2px}
    .appbar h1{font-size:clamp(16px,2.2vw,20px);margin:0;letter-spacing:.03em}
    .container{max-width:980px;margin:24px auto 80px;padding:0 16px}
    .card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 2px 10px rgba(13,34,45,.05);overflow:hidden}
    .card .card-header{background:linear-gradient(180deg,#ffffff,#f8fbfd);border-bottom:1px solid var(--border);padding:18px 20px}
    .card .card-header h2{margin:0;font-size:18px}
    .card .card-body{padding:20px}
    fieldset{border:1px dashed var(--border);border-radius:12px;padding:16px;margin:18px 0}
    legend{padding:0 8px;color:var(--brand-dark);font-weight:700}
    .row{display:grid;grid-template-columns:180px 1fr;gap:12px 16px;align-items:center;margin:10px 0}
    .row.start{align-items:start}
    label{font-weight:600;color:#203139}
    .hint{color:var(--muted);font-weight:500;font-size:12px}
    input[type="text"],input[type="email"],input[type="tel"],input[type="password"],textarea,select{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff;font-size:14px;outline:none;transition:box-shadow .15s,border-color .15s}
    textarea{min-height:88px;resize:vertical}
    input:focus,textarea:focus,select:focus{border-color:#99c3ff;box-shadow:0 0 0 3px rgba(153,195,255,.35)}
    .choices{display:flex;flex-wrap:wrap;gap:14px 18px}
    .choices label{font-weight:500}
    .inline{display:inline-flex;gap:6px;align-items:center}
    .grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .grid-4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .addr{display:grid;grid-template-columns:100px 1fr;gap:10px}
    .week-grid{border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .week-grid table{width:100%;border-collapse:collapse;font-size:14px}
    .week-grid th,.week-grid td{border-bottom:1px solid var(--border);border-right:1px solid var(--border);padding:8px;text-align:center}
    .week-grid th:last-child,.week-grid td:last-child{border-right:none}
    .week-grid tr:last-child td{border-bottom:none}
    .badge-note{font-size:12px;color:var(--muted);margin-top:8px}
    .submit-bar{display:flex;justify-content:center;padding:20px;background:#f8fbfa;border-top:1px solid var(--border)}
    .btn{appearance:none;border:none;border-radius:999px;padding:12px 28px;font-weight:800;letter-spacing:.08em;cursor:pointer;background:var(--brand);color:#fff;box-shadow:0 4px 0 var(--brand-dark)}
    .btn:active{transform:translateY(2px);box-shadow:0 2px 0 var(--brand-dark)}
    .login-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .req{color:var(--danger);font-weight:700;margin-left:.2em}
    @media (max-width:720px){.row{grid-template-columns:1fr}.addr{grid-template-columns:1fr}.grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}.login-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header class="appbar">
    <div class="menu" aria-label="menu"><span></span></div>
    <h1>流山市ファミリーサポートセンター</h1>
  </header>

  <main class="container">
    <!-- フォーム開始 -->
    <form action="register.php" method="POST">

      <div class="card">
        <div class="card-header"><h2>新規会員登録</h2></div>
        <div class="card-body">

          <!-- 会員種別 -->
          <fieldset>
            <legend>会員種類</legend>
            <div class="choices">
              <label class="inline"><input type="radio" name="member[member_type]" value="user"> 利用会員</label>
              <label class="inline"><input type="radio" name="member[member_type]" value="provider"> 提供会員</label>
              <label class="inline"><input type="radio" name="member[member_type]" value="both"> 両方会員</label>
            </div>
          </fieldset>

          <!-- 基本情報 -->
          <fieldset>
            <legend>基本情報（本人）</legend>

            <div class="row">
              <label>会員氏名<span class="req">*</span></label>
              <div class="grid-2">
                <input type="text" name="member[last_name]" placeholder="姓" required />
                <input type="text" name="member[first_name]" placeholder="名" required />
              </div>
            </div>

            <div class="row">
              <label>ふりがな</label>
              <div class="grid-2">
                <input type="text" name="member[last_name_kana]" placeholder="せい" />
                <input type="text" name="member[first_name_kana]" placeholder="めい" />
              </div>
            </div>

            <div class="row">
              <label>性別</label>
              <div class="choices">
                <label class="inline"><input type="radio" name="member[sex]" value="female"> 女</label>
                <label class="inline"><input type="radio" name="member[sex]" value="male"> 男</label>
              </div>
            </div>

            <div class="row">
              <label>生年月日<span class="req">*</span></label>
              <div class="grid-3">
                <select name="member[birth_year]" required>
                  <option value="">西暦</option>
                  <?php for ($y = 1930; $y <= (int)date('Y'); $y++): ?>
                    <option value="<?= $y ?>"><?= $y ?>年</option>
                  <?php endfor; ?>
                </select>
                <select name="member[birth_month]" required>
                  <option value="">月</option>
                  <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>"><?= $m ?>月</option>
                  <?php endfor; ?>
                </select>
                <select name="member[birth_day]" required>
                  <option value="">日</option>
                  <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?= $d ?>"><?= $d ?>日</option>
                  <?php endfor; ?>
                </select>
              </div>
            </div>

            <div class="row">
              <label>年齢</label>
              <div class="grid-2">
                <input type="text" id="age_display" placeholder="—" disabled />
                <span class="hint">（生年月日から自動計算）</span>
              </div>
            </div>

            <div class="row">
              <label>職業等</label>
              <div>
                <div class="choices">
                  <label class="inline"><input type="radio" name="member[workstyle]" value="employee" /> 雇用労働者</label>
                  <label class="inline"><input type="radio" name="member[workstyle]" value="fulltime" /> フルタイム</label>
                  <label class="inline"><input type="radio" name="member[workstyle]" value="parttime" /> パート</label>
                </div>
                <div class="row" style="margin-top:8px;">
                  <label style="font-weight:600; color:#203139;">勤務先</label>
                  <input type="text" name="member[workplace]" placeholder="勤務先名" />
                </div>
                <div class="choices" style="margin-top:8px;">
                  <label class="inline"><input type="checkbox" name="member[self_employed]" value="1" /> 自営業</label>
                  <label class="inline"><input type="checkbox" name="member[jobless]" value="1" /> 無職</label>
                  <label class="inline"><input type="checkbox" name="member[work_other_flag]" value="1" /> その他</label>
                  <input type="text" name="member[work_other_text]" placeholder="（その他の内容）" />
                </div>
              </div>
            </div>

            <div class="row start">
              <label>住所</label>
              <div class="addr">
                <input type="text" name="member[postal_code]" placeholder="〒000-0000" />
                <input type="text" name="member[address1]" placeholder="都道府県・市区町村・番地" />
              </div>
            </div>

            <div class="row">
              <label>電話番号</label>
              <div class="grid-2">
                <input type="tel" name="member[phone_home]" placeholder="固定電話" />
                <input type="tel" name="member[phone_mobile]" placeholder="携帯" />
              </div>
            </div>

            <div class="row">
              <label>メールアドレス</label>
              <input type="email" name="member[contact_email]" placeholder="example@example.jp" />
            </div>

            <div class="row">
              <label>本人以外の緊急連絡先</label>
              <div>
                <div class="grid-2" style="margin-bottom:10px;">
                  <input type="text" name="emergency[name]" placeholder="氏名" />
                  <input type="text" name="emergency[relation]" placeholder="続柄（例：配偶者）" />
                </div>
                <input type="tel" name="emergency[phone]" placeholder="電話番号" />
              </div>
            </div>

            <div class="row start">
              <label>同居家族</label>
              <div>
                <div class="choices" style="margin-bottom:8px;">
                  <span>配偶者：</span>
                  <label class="inline"><input type="radio" name="member[has_spouse]" value="1" /> 有</label>
                  <label class="inline"><input type="radio" name="member[has_spouse]" value="0" /> 無</label>
                </div>
                <div class="grid-4" style="margin-bottom:8px;">
                  <!-- ★子どもの人数 -->
                  <input type="number" id="num_children_input" min="0" name="member[num_children]" placeholder="こども（人数）" />
                  <span></span><span></span>
                </div>
                <input type="text" name="member[cohabit_others]" placeholder="その他（自由記述）" />
                <div class="hint" style="margin-top:6px;">※ 子どもの人数を入力すると、下にお子さん情報の入力欄が自動で表示されます。</div>
              </div>
            </div>
          </fieldset>

          <!-- ◎利用会員・両方会員の方（お子さん情報） -->
          <fieldset>
            <legend>◎利用会員・両方会員の方（お子さん情報）</legend>
            <div id="children_container"></div>
          </fieldset>

          <!-- 提供会員／両方会員 -->
          <fieldset>
            <legend>◎提供会員・両方会員の方</legend>

            <div class="row start">
              <label>資格・免許</label>
              <div class="choices">
                <label class="inline"><input type="checkbox" name="provider[licenses][]" value="保育士・幼稚園教諭" /> 保育士・幼稚園教諭</label>
                <label class="inline"><input type="checkbox" name="provider[licenses][]" value="小学校教諭" /> 小学校教諭</label>
                <label class="inline"><input type="checkbox" name="provider[licenses][]" value="看護・保健師等" /> 看護・保健師等</label>
                <label class="inline"><input type="checkbox" name="provider[licenses][]" value="自動車免許" /> 自動車免許</label>
                <label class="inline"><input type="checkbox" name="provider[licenses][]" value="その他" /> その他</label>
                <input type="text" name="provider[license_other_text]" placeholder="（その他の内容）" />
              </div>
            </div>

            <!-- ★追加：対応可能な子どもの年齢 -->
            <div class="row start">
              <label>対応可能な子どもの年齢</label>
              <div class="choices">
                <label class="inline">
                  最低年齢：
                  <input type="number" name="provider[min_age]" min="0" max="12" step="1" style="width:80px;" />
                  歳
                </label>
                <label class="inline" style="margin-left:16px;">
                  最高年齢：
                  <input type="number" name="provider[max_age]" min="0" max="12" step="1" style="width:80px;" />
                  歳
                </label>
              </div>
              <div class="hint" style="margin-top:4px;">※生後6か月〜12歳（小学6年生）が対象です。整数で入力してください。</div>
            </div>

            <!-- ★差し替え：援助できる活動内容 -->
            <div class="row start">
              <label>援助できる活動内容</label>
              <div>
                <div class="choices">
                  <label class="inline">
                    <input type="checkbox" name="provider[activities][]" value="冠婚葬祭・学校行事" />
                    冠婚葬祭や子どもの学校行事等の際のお預かり
                  </label>
                  <label class="inline">
                    <input type="checkbox" name="provider[activities][]" value="保育施設前後" />
                    保育園・幼稚園・学童保育室の開始時間まで／終了時間後のお預かり
                  </label>
                  <label class="inline">
                    <input type="checkbox" name="provider[activities][]" value="送迎" />
                    保育園・幼稚園・学童・習い事等への送迎
                  </label>
                  <label class="inline">
                    <input type="checkbox" name="provider[activities][]" value="買い物・外出・リフレッシュ" />
                    買い物等外出や保護者のリフレッシュのためのお預かり
                  </label>
                  <label class="inline">
                    <input type="checkbox" name="provider[activities][]" value="その他" />
                    その他
                  </label>
                  <input type="text" name="provider[activity_other_text]" placeholder="（その他の内容）" />
                </div>
                <div class="hint" style="margin-top:6px;">※流山市ファミリーサポートセンターの案内に合わせた区分です。</div>
              </div>
            </div>

            <div class="row start">
              <label>援助できる日時</label>
              <div class="week-grid">
                <table>
                  <thead>
                    <tr><th>区分</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th></tr>
                  </thead>
                  <tbody>
                    <?php
                      // ★ 夜間を削除（午前・午後のみ）
                      $periods = ['am'=>'午前','pm'=>'午後'];
                      foreach ($periods as $periodKey => $periodLabel):
                    ?>
                    <tr>
                      <th><?= $periodLabel ?></th>
                      <?php for ($w=1; $w<=7; $w++): ?>
                        <td>
                          <select name="availability[<?= $w ?>][<?= $periodKey ?>]">
                            <option>○</option><option>△</option><option>×</option>
                          </select>
                        </td>
                      <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="badge-note">○ = 可能 ／ △ = 条件により可能 ／ × = 不可</div>
              <div style="margin-top:10px;">
                <label class="inline" style="margin-right:18px;"><input type="radio" name="provider[negotiable]" value="consult" /> 相談に応じて検討</label>
                <label class="inline"><input type="radio" name="provider[negotiable]" value="other" /> その他</label>
              </div>
            </div>

            <div class="row start">
              <label>備考</label>
              <textarea name="provider[remarks]" placeholder="時間帯や曜日の補足、活動に関する希望など"></textarea>
            </div>

            <div class="row">
              <label>自家用車での送迎</label>
              <div class="choices">
                <label class="inline"><input type="radio" name="provider[car]" value="allow" /> 可</label>
                <label class="inline"><input type="radio" name="provider[car]" value="deny" /> 不可</label>
                <span class="hint">（任意損害保険加入：
                  <label class="inline"><input type="radio" name="provider[insurance]" value="have" /> 有</label>／
                  <label class="inline"><input type="radio" name="provider[insurance]" value="none" /> 無</label>）
                </span>
              </div>
            </div>

            <div class="row start">
              <label>ペットの状況</label>
              <textarea name="provider[pets_notes]" placeholder="犬／猫／その他（品種・頭数など）"></textarea>
            </div>
          </fieldset>

          <!-- ログイン -->
          <fieldset>
            <legend>ログイン情報</legend>
            <div class="login-grid">
              <div class="row"><label>ログインID</label><input type="text" name="login[login_id]" placeholder="任意のID" required /></div>
              <div class="row"><label>パスワード</label><input type="password" name="login[password]" placeholder="8文字以上を推奨" required /></div>
              <div class="row"><label>再パスワード</label><input type="password" name="login[password_confirm]" placeholder="確認用" required /></div>
            </div>
            <div class="hint" style="margin-top:8px;">※ご記入いただいた個人情報は援助活動目的以外には使用いたしません。</div>
          </fieldset>

        </div>
        <div class="submit-bar">
          <button class="btn" type="submit">会員登録</button>
        </div>
      </div>

    </form>
    <!-- フォーム終了 -->
  </main>

  <script>
  // ===== 共通：生年月日→年齢ラベル =====
  function calcAgeLabel(y, m, d) {
    y = parseInt(y); m = parseInt(m); d = parseInt(d);
    if (!y || !m || !d) return "";
    const birth = new Date(y, m - 1, d);
    if (birth.getFullYear() !== y || birth.getMonth() !== m - 1 || birth.getDate() !== d) return "";
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const mmdd = (today.getMonth() - birth.getMonth()) * 100 + (today.getDate() - birth.getDate());
    if (mmdd < 0) age--;
    return age < 0 ? "" : (age + "歳");
  }

  // ===== 本人の年齢自動計算 =====
  function setupMemberAge() {
    const ySel = document.querySelector('select[name="member[birth_year]"]');
    const mSel = document.querySelector('select[name="member[birth_month]"]');
    const dSel = document.querySelector('select[name="member[birth_day]"]');
    const ageDisp = document.getElementById('age_display');
    if (!ySel || !mSel || !dSel || !ageDisp) return;
    const update = () => { ageDisp.value = calcAgeLabel(ySel.value, mSel.value, dSel.value); };
    [ySel, mSel, dSel].forEach(el => el.addEventListener('change', update));
    update();
  }

  // ===== 子どもフォーム生成 =====
  const childrenContainer = () => document.getElementById('children_container');

  function childBlockHTML(idx) {
    return `
      <div class="child-block" data-idx="${idx}" style="border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:12px;">
        <div class="row">
          <label>お子さん氏名</label>
          <div class="grid-2">
            <input type="text" name="children[${idx}][last_name]"  placeholder="姓" />
            <input type="text" name="children[${idx}][first_name]" placeholder="名" />
          </div>
        </div>

        <div class="row">
          <label>ふりがな</label>
          <div class="grid-2">
            <input type="text" name="children[${idx}][last_name_kana]"  placeholder="せい" />
            <input type="text" name="children[${idx}][first_name_kana]" placeholder="めい" />
          </div>
        </div>

        <div class="row">
          <label>性別</label>
          <div class="choices">
            <label class="inline"><input type="radio" name="children[${idx}][sex]" value="female" /> 女</label>
            <label class="inline"><input type="radio" name="children[${idx}][sex]" value="male" /> 男</label>
          </div>
        </div>

        <div class="row">
          <label>生年月日</label>
          <div>
            <div class="grid-4">
              <select name="children[${idx}][birth_year]">
                <option value="">西暦</option>
                <?php for ($y = 1930; $y <= (int)date('Y'); $y++): ?>
                  <option value="<?= $y ?>"><?= $y ?>年</option>
                <?php endfor; ?>
              </select>
              <select name="children[${idx}][birth_month]">
                <option value="">月</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                  <option value="<?= $m ?>"><?= $m ?>月</option>
                <?php endfor; ?>
              </select>
              <select name="children[${idx}][birth_day]">
                <option value="">日</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                  <option value="<?= $d ?>"><?= $d ?>日</option>
                <?php endfor; ?>
              </select>
              <input type="text" name="children[${idx}][age_display]" class="child-age" placeholder="—" disabled />
            </div>
            <div class="hint" style="margin-top:6px;">（生年月日から自動計算）</div>
          </div>
        </div>

        <div class="row">
          <label>こども園・学校名</label>
          <input type="text" name="children[${idx}][school]" placeholder="園・学校名" />
        </div>

        <div class="row">
          <label>アレルギー</label>
          <div class="choices">
            <label class="inline"><input type="radio" name="children[${idx}][allergy_flag]" value="1" /> 有</label>
            <label class="inline"><input type="radio" name="children[${idx}][allergy_flag]" value="0" /> 無</label>
          </div>
        </div>

        <div class="row">
          <label>障がい者手帳</label>
          <div class="choices">
            <label class="inline"><input type="radio" name="children[${idx}][disability_card]" value="1" /> 有</label>
            <label class="inline"><input type="radio" name="children[${idx}][disability_card]" value="0" /> 無</label>
          </div>
        </div>

        <div class="row start">
          <label>特記事項</label>
          <textarea name="children[${idx}][notes]" placeholder="既往歴・配慮事項など"></textarea>
        </div>
      </div>
    `;
  }

  function renderChildren(count) {
    const n = Math.max(0, parseInt(count) || 0);
    const cont = childrenContainer();
    if (!cont) return;
    cont.innerHTML = '';
    for (let i = 0; i < n; i++) cont.insertAdjacentHTML('beforeend', childBlockHTML(i));
  }

  function setupChildrenAgeAuto() {
    const cont = childrenContainer();
    if (!cont) return;

    const updateAgeByIdx = (idx) => {
      const y = cont.querySelector(`select[name="children[${idx}][birth_year]"]`)?.value;
      const m = cont.querySelector(`select[name="children[${idx}][birth_month]"]`)?.value;
      const d = cont.querySelector(`select[name="children[${idx}][birth_day]"]`)?.value;
      const out = cont.querySelector(`input[name="children[${idx}][age_display]"]`);
      if (out) out.value = calcAgeLabel(y, m, d);
    };

    cont.addEventListener('change', (e) => {
      const t = e.target;
      if (!(t instanceof Element)) return;
      if (!t.matches('select[name^="children["][name$="[birth_year]"], select[name^="children["][name$="[birth_month]"], select[name^="children["][name$="[birth_day]"]')) return;
      const name = t.getAttribute('name') || '';
      const m = name.match(/^children\[(\d+)\]\[(birth_year|birth_month|birth_day)\]$/);
      if (!m) return;
      updateAgeByIdx(m[1]);
    });
  }

  function setupChildrenCount() {
    const numInput = document.getElementById('num_children_input');
    if (!numInput) return;
    const refresh = () => {
      renderChildren(numInput.value);
    };
    numInput.addEventListener('input', refresh);
    refresh();
  }

  document.addEventListener('DOMContentLoaded', () => {
    setupMemberAge();
    setupChildrenCount();
    setupChildrenAgeAuto();
  });
  </script>
</body>
</html>
