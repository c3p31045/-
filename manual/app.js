// app.js
const pages = {
'login': `
<div class='manual-box'>
<h2>ログイン方法</h2>
<p>ここにログイン手順を記載します。</p>
</div>
`,


'rules': `
<div class='manual-box'>
<h2>運用ルール</h2>
<p>ここに運用ルールを記載します。</p>
</div>
`,


'list-view': `
<div class='manual-box'>
<h2>依頼一覧の見方</h2>
<p>1. 職員トップページからマッチング候補決定を選択</p>
<img src="image/toko.png" class="manual-image" />
<p>2. 援助依頼一覧から見たい依頼内容をクリック</p>
<div class='image-box'>依頼一覧画面のスクリーンショット</div>
</div>
`,


'check-points': `
<div class='manual-box'>
<h2>依頼内容の評価ポイント</h2>
<p>ここに評価ポイントを記載します。</p>
</div>
`,

'kari-kettei': `
<div class='manual-box'>
<h2>仮候補の決定法</h2>
<p>ここに評価ポイントを記載します。</p>
</div>
`
};


function loadPage(key){
document.getElementById("content-area").innerHTML = pages[key] || "<p>内容がありません。</p>";
}