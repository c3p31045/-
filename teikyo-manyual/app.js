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
<img src="image/base/login/login1.png" class="manual-image" />
<p>2.新規会員登録時に設定したIDとパスワードを入力してください</p>
<img src="image/base/login/login2.png" class="manual-image" />
</div>
`,


'lost': `
<div class='manual-box'>
<h2>運用ルール</h2>
<p>ここに運用ルールを記載します。</p>
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
<h2>援助依頼方法</h2>
<p>1.提供会員トップページから援助依頼を選択</p>
<img src="image/mypage/teikyo-info/teikyo-top1.png" class="manual-image" />
<p>2.希望日時に希望日入力してください</p>
<img src="image/mypage/teikyo-info/teikyo-request.png" class="manual-image" />
<p>3.お子様について子どもの年齢・依頼する預け方を入力してください</p>
<img src="image/mypage/teikyo-info/teikyo-request2.png" class="manual-image" />
<p>3.依頼する詳細な情報を入力してください</p>
<img src="image/mypage/teikyo-info/teikyo-request3.png" class="manual-image" />
</div>
`,

'riyou-info': `
<div class='manual-box'>
<h2>利用会員情報トップページ</h2>
<img src="image/mypage/riyou-info/riyou-top.png" class="manual-image" />
<h2>利用会員データについて</h2>
<p>1.利用会員データを選択</p>
<img src="image/mypage/riyou-info/riyou-.png" class="manual-image" />
<p>2.利用会員のお子様情報などを確認できます</p>
<img src="image/mypage/riyou-info/riyou-.png" class="manual-image" />
</div>
`,

'menber': `
<div class='manual-box'>
<h2>会員情報の変更</h2>
<p>ここに評価ポイントを記載します。</p>
</div>
`,

'decision': `
<div class='manual-box'>
<h2>確定日の確認</h2>
<p>ここに評価ポイントを記載します。</p>
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
<p>ここに評価ポイントを記載します。</p>
</div>
`,


//4項目目
'troble': `
<div class='manual-box'>
<h2>トラブル時の対処</h2>
<p>ここに評価ポイントを記載します。</p>
</div>
`,

'personal-info': `
<div class='manual-box'>
<h2>個人情報扱いの注意点</h2>
<p>ここに評価ポイントを記載します。</p>
</div>
`,

};


function loadPage(key){
document.getElementById("content-area").innerHTML = pages[key] || "<p>内容がありません。</p>";
}