@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'お問い合わせフォーム')

@section('content')
<h1>お問い合わせフォーム</h1>

      @if (session('error'))
        <div class="alert alert-danger" role="alert">
          {{ session('error') }}
        </div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('contacts.confirm') }}">
        @csrf
        <div class="width-md table-vertical-responsive">
          <table>
            <tr>
              <th><span class="badge badge-red">必須</span><label for="name">お名前</label></th>
              <td>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value="{{ old('name', $contact['name'] ?? '') }}"
                  required
                  autofocus
                >
              </td>
            </tr>
            <tr>
              <th><span class="badge badge-red">必須</span><label for="email">メールアドレス</label></th>
              <td>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value="{{ old('email', $contact['email'] ?? '') }}"
                  required
                >
              </td>
            </tr>
            <tr>
              <th><span class="badge badge-red">必須</span><label for="inquiry_type">お問い合わせ種類</label></th>
              <td>
                <select id="inquiry_type" name="inquiry_type" required>
                  <option value="" disabled {{ old('inquiry_type', $contact['inquiry_type'] ?? '') === '' ? 'selected' : '' }}>選択してください</option>
                  @foreach(\App\Models\Contact::getInquiryTypes() as $type)
                    <option value="{{ $type }}" {{ old('inquiry_type', $contact['inquiry_type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                  @endforeach
                </select>
              </td>
            </tr>
            <tr>
              <th><span class="badge badge-red">必須</span><label for="message">お問い合わせ内容</label></th>
              <td>
                <div class="js-char-count">
                  <textarea
                    id="message"
                    name="message"
                    rows="8"
                    maxlength="1000"
                    required>{{ old('message', $contact['message'] ?? '') }}</textarea>
                  <div class="gray text-sm" aria-live="polite">0 / 1000 文字</div>
                </div>
              </td>
            </tr>
          </table>
        </div>
        <div class="center">
          <button type="submit" class="btn btn-primary">送信内容確認</button>
        </div>
      </form>
@endsection