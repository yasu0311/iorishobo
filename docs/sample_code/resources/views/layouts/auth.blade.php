
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
  <title>@yield('title', '認証') | {{ config('app.name') }}</title>
  <link rel="icon" href="{{ asset('favicon.png') }}">
  <link rel="stylesheet" href="{{ asset('css/common/auth.css') }}"/>
  <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}"/>
  @yield('styles')
</head>
<body>
  <div class="container">
    @yield('content')
  </div>
  @yield('scripts')
</body>
</html>