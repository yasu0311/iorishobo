@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'お問い合わせ内容 確認')

@section('content')
<h1>お問い合わせ内容 確認</h1>
      <div class="width-md table-vertical-responsive">
        <table>
          <tr>
            <th>お名前</th>
            <td>{{ $contact['name'] }}</td>
          </tr>
          <tr>
            <th>メールアドレス</th>
            <td>{{ $contact['email'] }}</td>
          </tr>
          <tr>
            <th>お問い合わせ種類</th>
            <td>{{ $contact['inquiry_type'] }}</td>
          </tr>
          <tr>
            <th>お問い合わせ内容</th>
            <td>{!! nl2br(e($contact['message'])) !!}</td>
          </tr>
        </table>
      </div>

      <div class="center">
        <form method="POST" action="{{ route('contacts.back') }}" class="inline">
          @csrf
          <button type="submit" class="btn btn-white">内容を修正する</button>
        </form>
        <form method="POST" action="{{ route('contacts.store') }}" class="js-disable-on-submit inline">
          @csrf
          <button type="submit" class="btn btn-primary">送信</button>
        </form>
      </div>
@endsection