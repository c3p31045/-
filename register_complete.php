<!-- register_complete.php -->
<?php
// 単純な完了画面
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css">
<title>登録完了 | 流山市ファミリーサポートセンター</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
  font-family: "Noto Sans JP", sans-serif;
  background: #f4f7fa;
  margin: 0;
  padding: 0;
}
.card {
  max-width: 480px;
  margin: 80px auto;
  padding: 30px;
  background: #fff;
  border: 1px solid #d8e1e8;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.card h1 {
  color: #0b6a57;
  margin-bottom: 20px;
}
.btn {
  display: inline-block;
  margin-top: 20px;
  padding: 12px 24px;
  background: #0b6a57;
  color: #fff;
  font-weight: bold;
  text-decoration: none;
  border-radius: 8px;
}
</style>
</head>
<body>

<div class="card">
  <h1>登録が完了しました</h1>
  <p>ご登録ありがとうございました。</p>
  <p>下のボタンからトップページへ戻れます。</p>

  <!-- login.html へ遷移 -->
  <a href="login.html" class="btn">トップページへ戻る</a>
</div>

</body>
</html>
