<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'お知らせ')</title>
    <style>
        body { font-family: Arial, 'ヒラギノ角ゴ ProN', 'Hiragino Kaku Gothic ProN', 'メイリオ', Meiryo, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 32px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #e9ecef; border-radius: 8px; }
        .plan-info { background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
        <hr style="margin:32px 0 16px 0; border:none; border-top:1px solid #eee;">
        <div style="color:#888; font-size:13px; line-height:1.7;">
            ────────────────────<br>
            あおば教材マーケット<br>
            <a href="{{ config('app.url') }}" style="color:#888; text-decoration:underline;">{{ config('app.url') }}</a>
        </div>
    </div>
</body>
</html> 