// app.js　
const pages = {

/*書き方 　これをテンプレートにして書き換えてもいいかも


'login': `　　　←htmlのloadpageの()の名前を入れて
<div class='manual-box'>
<h2>ログイン方法</h2>　　←マニュアルのタイトル
<p>1.ここにログイン手順を記載します。</p>　　←１工程の説明文
<img src="image/base/login/login1.png" class="manual-image" />　　←１工程の説明文に合わせた画像をパスを正しく設定

あとは工程の終わりまでは繰り返し


</div>
`,　←これガチ大事だから消さないで


*/


//1項目目
'login': `
<div class='manual-box'>
<h2>ログイン方法</h2>
<p>1.ホーム画面から提供会員を選択します。</p>
<img src="image/base/login/login1.png" class="manual-image0" />
<p>2.新規会員登録時に設定したIDとパスワードを入力してください</p>
<img src="image/base/login/login2.png" class="manual-image0" />
</div>
`,



//2項目目
'calender': `
<div class='manual-box'>
<h2>カレンダーの見方</h2>
<p>1. カレンダーは決定した依頼を表示します</p>
<img src="image/toko.png" class="manual-image" />
<p>2. 確定日時に表示されている日時がカレンダーに表示されます</p>

<p>3. カレンダーの確認したい日にカーソルを合わせて確認してください</p>

</div>
`,


'teikyo-info': `
<div class='manual-box'>
<h2>提供会員情報</h2>
<p>提供会員トップページ</p>
<img src="image/mypage/teikyo-info/teikyo-top.png" class="manual-image" />
<h2>提供会員データについて</h2>
<p>1.トップページから提供会員データを選択</p>
<img src="image/mypage/teikyo-info/teikyo-top1.png" class="manual-image" />
<p>2.提供会員のお子様情報などを確認できます</p>
<img src="image/mypage/teikyo-info/teikyo-.png" class="manual-image" />
</div>
`,

'riyou-info': `
<div class='manual-box'>
<h2>利用会員情報トップページ</h2>
<img src="image/mypage/riyou-info/riyou-top.png" class="manual-image" />
<h2>援助依頼方法</h2>
<p>1.トップページから援助依頼を選択</p>
<img src="image/mypage/riyou-info/riyou-top1.png" class="manual-image" />
<p>2.希望日時に入力してください</p>
<img src="image/mypage/riyou-info/riyou-request.png" class="manual-image" />
<p>3.お子様について子どもの年齢・依頼する預け方を入力してください</p>
<img src="image/mypage/riyou-info/riyou-request2.png" class="manual-image0" />
<p>3.依頼する詳細な情報を入力してください</p>
<img src="image/mypage/riyou-info/riyou-request3.png" class="manual-image" />
</div>
`,



//3項目目
'report': `
<div class='manual-box'>
<h2>活動報告書の取得方法</h2>
<p>1.提供会員トップページから活動報告書を選択</p>
<img src="image/list/report/houkoku1.png" class="manual-image0" />
<p>2.利用会員の会員名・会員番号・子ども氏名・年齢を入力してください</p>
<img src="image/list/report/houkoku2.png" class="manual-image0" />
<p>3.援助した日時・人数・実施場所・援助時間から算出した報酬を入力してください。</p>
<img src="image/list/report/houkoku3.png" class="manual-image0" />
<p>4.援助中に得た気づき(ヒヤリハットなど)を備考欄に記入してください。</p>
<img src="image/list/report/houkoku4.png" class="manual-image0" />
</div>
`,

'risk-report': `
<div class='manual-box'>
<h2>ヒヤリハット報告書提出方法</h2>
<p>活動報告書のメモ欄に、
<br>その時の状況・事象の程度・発生場所や時間・具体的な内容・未然に防ぐぐための改善点・保護者の対策などを記入して提出してください。</p>
<img src="image/list/risk-report/memo.png" class="manual-image0" />
</div>
`,


//4項目目
'troble': `
<div class='manual-box'>
<h2>トラブル時の対処</h2>
<p>・当事者同士で確認・話し合い</p>
<p>-利用会員と提供会員は対等な立場で活動しています。<br>当事者間で冷静に状況を確認し、話し合いによる解決を試みて下さい。</p>
<p>・ケガや事故が発生した場合</p>
<p>-ケガや事故が発生した場合は、応急処置を行い、必要に応じて医療機関へ搬送してください。警察や消防など公的機関への連絡が必要な場合は、ためらわず実施してください。</p>
<p>・センターのアドバイザーへの相談・報告</p>
<p>-解決が難しい場合は必ずセンターへ連絡してください</p>
</div>
`,

'personal-info': `
<div class='manual-box'>
<h2>個人情報扱いの注意点</h2>
<p>1.第三者へ利用・提供会員の情報を渡さないでください。<br>氏名、住所、電話番号、勤務先、子どもの情報などは、本人の同意なく他の人へ教えないでください。<br>
SNSやメール、掲示板などでの情報共有も厳禁です。</p>
<p></p>
<p>2.活動で知った会員についての情報を外部に漏らさない。<br>サポート内容や家庭の事情、子どもの様子など、活動中に知った情報は外部に漏らさないようにしてください。<br>活動が終了した後も守秘義務は続きます。</p>
<p></p>
<p>3.不審な問い合わせはファミリーサポートセンターに報告してください。<br>会員の連絡先を教えてほしい」などの問い合わせがあっても、必ずセンターに確認してください。<br></p>
<p></p>
<p>4.情報漏えいをしてしまった場合はファミリーサポートセンターに連絡してください。<br>紛失、誤送信、誤って他の人に見せてしまったなど、少しでも心配なことがあれば、すぐにセンターへ相談してください。</p>
</div>
`,

};


function loadPage(key){
document.getElementById("content-area").innerHTML = pages[key] || "<p>内容がありません。</p>";
}