@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('head_meta')
  <meta name="robots" content="noindex">
@endsection

@section('title', '特定商取引法に基づく表記')

@section('content')

<h1>特定商取引法に基づく表記</h1>
      <div class="width-md table-vertical-responsive">
        <table>
          <tr>
            <th>事業者</th>
            <td>合同会社ころろ</td>
          </tr>
          <tr>
            <th>代表者</th>
            <td>高橋泰宏</td>
          </tr>
          <tr>
            <th>所在地</th>
            <td>奈良県奈良市左京2-3-1-5-504</td>
          </tr>
          <tr>
            <th>連絡先</th>
            <td><a href="{{ route('contacts.create') }}">お問い合わせフォーム</a>よりご連絡ください。</td>
          </tr>
          <tr>
            <th>販売価格</th>
            <td>各商品ページに記載しております。</td>
          </tr>
          <tr>
            <th>役務の内容</th>
            <td>お客様間のダウンロードコンテンツの売買プラットフォームの提供</td>
          </tr>
          <tr>
            <th>役務の提供時期</th>
            <td>会員登録後すぐにご利用いただけます。</td>
          </tr>
          <tr>
            <th>役務の対価</th>
            <td>
              出品者：販売手数料として販売価格の15％<br>
              購入者：料金はかかりません。
            </td>
          </tr>
          <tr>
            <th>送料</th>
            <td>ダウンロードコンテンツという性質上、送料はかかりません。</td>
          </tr>
          <tr>
            <th>役務の対価の支払い時期・方法</th>
            <td>商品が購入され、取引が完了したときに、販売価格から販売手数料を差し引きます。</td>
          </tr>
          <tr>
            <th>上記販売手数料以外の必要料金</th>
            <td>売上金の出金申請時に、1回あたり振込手数料200円が発生します（内容は<a href="{{ route('static.fee') }}">ご利用料金</a>および本サービス上の表示に従います）。</td>
          </tr>
          <tr>
            <th>代金の支払方法</th>
            <td>クレジットカード</td>
          </tr>
          <tr>
            <th>返品・交換</th>
            <td>ダウンロードコンテンツという性質上、返品・交換は受け付けられません。</td>
          </tr>
        </table>
      </div>
    
@endsection