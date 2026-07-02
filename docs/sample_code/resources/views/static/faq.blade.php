@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'よくある質問')

@section('content')

<div class="static-guide">
  <h1>よくある質問</h1>
  <div class="faq">
     <h2>教材を購入したい方</h2>
     
     <div class="faq__item">
          <div class="faq__question">購入の流れを知りたい</div>
          <div class="faq__answer">
               @guest<a href="{{ route('register') }}">会員登録</a>@else「会員登録」@endguestして、<a href="{{ route('member.buy.products.index') }}">教材検索</a>から教材を選び、購入手続きをしてください。
               詳しくは<a href="{{ route('static.how-to-buy') }}">教材入手の流れ</a>をご確認ください。
          </div>
     </div>
     <div class="faq__item">
          <div class="faq__question">購入した教材はどうしたら使えますか。</div>
          <div class="faq__answer">
               @auth<a href="{{ route('member.buy.orders.index') }}">入手済教材</a>@else「入手済教材」@endauthから使いたい商品名をクリックし、
               商品ファイル一覧からダウンロードしてください。
               紙の状態で利用したい場合は、購入者様ご自身で印刷していただく必要があります。
               詳しくは<a href="{{ route('static.how-to-buy') }}">教材入手の流れ</a>をご確認ください。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">購入した教材をどこで確認できますか？</div>
          <div class="faq__answer">
               @auth<a href="{{ route('member.buy.orders.index') }}">入手済教材</a>@else「入手済教材」@endauthから確認できます。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">購入した教材を再ダウンロードできますか？</div>
          <div class="faq__answer">
               はい、一度購入した教材は、@auth<a href="{{ route('member.buy.orders.index') }}">入手済教材</a>@else「入手済教材」@endauthから何度でもダウンロードすることができます。
               ただし、購入済みの教材でも、販売終了した後はダウンロードできなくなります。
          </div>
     </div>
    
     <div class="faq__item">
          <div class="faq__question">会員登録をしなくても購入できますか？</div>
          <div class="faq__answer">
               @guest<a href="{{ route('register') }}">会員登録</a>@else「会員登録」@endguest（無料）が必要です。<a href="{{ route('member.buy.products.index') }}">教材検索</a>は登録なしでも閲覧できます。
          </div>
     </div>
      
     <div class="faq__item">
          <div class="faq__question">支払い方法は何が使えますか？</div>
          <div class="faq__answer">
               現在、クレジットカードのみ対応しております。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">領収書を発行できますか？</div>
          <div class="faq__answer">
               申し訳ありませんが、領収書の発行は承っておりません。
               @auth<a href="{{ route('member.buy.orders.index') }}">入手済教材</a>@else「入手済教材」@endauthの各商品の注文番号から、注文明細をご確認いただけます。また、クレジットカード会社の利用伝票を領収書として代用できます。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">購入した教材が期待と違っていました。返品できますか？</div>
          <div class="faq__answer">
               デジタル商品の性質上、返品・返金はお受けできません。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">教材のサンプルはありますか？</div>
          <div class="faq__answer">
               教材サンプルの有無はショップや商品によって異なります。サンプルが公開されている場合、商品詳細ページの商品ファイル一覧から、サンプルをダウンロードできます。
               内容について不明な点がある場合は、商品ページ「質問メッセージを見る」→「質問・メッセージを新規投稿」からショップに質問してください。
          
          </div>
     </div>
      
     <div class="faq__item">
          <div class="faq__question">教材を他の人と共有できますか？</div>
          <div class="faq__answer">
               購入した際の利用目的と共有方法によります。
               詳しくは、<a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a>や<a href="{{ route('static.terms') }}">利用規約</a>をご確認ください。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">購入した教材を加工して使用してもよいですか？</div>
          <div class="faq__answer">
               はい、できます。詳細は、<a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a>や<a href="{{ route('static.terms') }}">利用規約</a>をご確認ください。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">教材を学校や塾で使用してもいいですか？</div>
          <div class="faq__answer">
               教材購入の際、学校で使用する場合は学校利用を、また塾で使用する場合は商用利用を選択してください。
               教材購入の際に個人利用を選択した場合、教材を学校や塾で使用することはできません。
               詳細は、<a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a>や<a href="{{ route('static.terms') }}">利用規約</a>をご確認ください。
          </div>
     </div>

     <h2>教材を販売したい方</h2>
     <div class="faq__item">
          <div class="faq__question">販売の流れを知りたい</div>
          <div class="faq__answer">
               @guest<a href="{{ route('register') }}">会員登録</a>@else「会員登録」@endguestを完了し、@auth<a href="{{ route('member.sell.shop.show') }}">ショップ設定</a>@else「ショップ設定」@endauthと@auth<a href="{{ route('member.sell.products.index') }}">商品登録</a>@else「商品登録」@endauthを行います。詳しくは、<a href="{{ route('static.how-to-sell') }}">教材販売の流れ</a>をご確認ください。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">販売にかかる費用を知りたい</div>
          <div class="faq__answer">
               教材が売れたときと、売上を出金するときに料金が発生します。教材を出品するだけでは料金は発生しません（月額使用料・販売システム利用料・出品手数料はありません）。<br>
               ・教材が売れたとき（教材価格の15％）<br>
               ・売上を出金するとき（1回の出金につき手数料200円）<br>
               詳しくは<a href="{{ route('static.fee') }}">ご利用料金</a>をご確認ください。
          </div>
     </div>
    
     <div class="faq__item">
          <div class="faq__question">出品に料金はかかりますか？</div>
          <div class="faq__answer">
               出品するだけで発生する料金はありません。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">売上に手数料はかかりますか？</div>
          <div class="faq__answer">
               教材が売れた場合は、教材価格から手数料として15％差し引いた額を売上として計上します。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">売上を出金したい</div>
          <div class="faq__answer">
               マイアカウントの通帳から、出金依頼をすることができます。なお、出金1回につき手数料200円かかります。<br>
               また、残高には発生日から{{ $balanceExpiryMonths }}か月の有効期限がありますので、それまでに出金申請をお願いします。
               有効期限を過ぎた残高は失効されます。
          </div>
     </div>


     <div class="faq__item">
          <div class="faq__question">売上の出金申請には期限がありますか？</div>
          <div class="faq__answer">
               はい、あります。売上由来の残高は、売上発生日から{{ $balanceExpiryMonths }}か月以内に出金申請を行ってください。
               期限を過ぎた残高は出金が利用できません。詳細は<a href="{{ route('static.terms') }}">利用規約</a>をご確認ください。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">販売できる教材の種類に制限はありますか？</div>
          <div class="faq__answer">
               学習目的の教材や教育関連のデジタルコンテンツが対象です。ただし、著作権を侵害する内容や公序良俗に反するものは出品できません。
          </div>
     </div>
     
     
     <div class="faq__item">
          <div class="faq__question">他人の素材を使った教材を販売できますか？</div>
          <div class="faq__answer">
               他人の素材を使った教材を販売する場合は、必ずその素材の利用条件を確認してください。
               必要な場合は著作者から許可を得てください。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">出品した教材の著作権は誰に帰属しますか？</div>
          <div class="faq__answer">
               著作権は販売者に帰属します。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">間違って出品した教材を削除できますか？</div>
          <div class="faq__answer">
               出品管理画面から教材を削除できます。
               ただし、すでに購入されている場合は削除できない場合があります。
               その場合は、教材を販売終了にすることで販売を停止することができます。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">出品した教材の内容を変更したいのですが可能ですか？</div>
          <div class="faq__answer">
               はい、可能です。既に購入されている教材にも反映されます。変更以前にダウンロードされた教材は変更前の内容のままになります。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">購入者からクレームがあった場合、どう対応すればよいですか？</div>
          <div class="faq__answer">
               メッセージ機能を通じて、販売者が購入者に直接対応してください。当事者間での解決を基本とします。
               ただし、悪質な場合のみ、<a href="{{ route('contacts.create') }}">お問い合わせフォーム</a>からサイトの運営者が介入する場合もあります。
          </div>
     </div>

     <div class="faq__item">
          <div class="faq__question">サイトを利用する際の税金について教えてください。</div>
          <div class="faq__answer">
               販売収益は課税対象となる場合があります。詳細は税理士などの専門家にご相談ください。
          </div>
     </div>
     
     <h2>登録・ログイン・機能について</h2>
     <div class="faq__item">
          <div class="faq__question">会員登録は必須ですか？</div>
          <div class="faq__answer">
               <a href="{{ route('member.buy.products.index') }}">教材検索</a>を閲覧するだけなら会員登録は必要ありません。教材を購入したり販売したりする場合は、@guest<a href="{{ route('register') }}">会員登録</a>@else「会員登録」@endguest（無料）が必要です。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">会員登録の方法を教えてください。</div>
          <div class="faq__answer">
               @guest<a href="{{ route('register') }}">新規登録</a>@else「新規登録」@endguestから必要事項を入力し、登録を完了してください。
          </div>
     </div>
    
     <div class="faq__item">
          <div class="faq__question">登録に料金はかかりますか？</div>
          <div class="faq__answer">
               会員登録は無料です。
          </div>
     </div>
    
     <div class="faq__item">
          <div class="faq__question">未成年でも会員登録できますか？</div>
          <div class="faq__answer">
               満15歳以上（中学生を除く）であれば会員登録できますが、教材販売はできません。
               また、教材を購入する際は、保護者の同意が必要です。
               詳しくは<a href="{{ route('static.terms') }}">利用規約</a>をご確認ください。          
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">ログインできません。どうすればいいですか？</div>
          <div class="faq__answer">
               入力したメールアドレスとパスワードが正しいか確認してください。
               それでもログインできない場合、@guest<a href="{{ route('password.request') }}">パスワードを忘れた方はこちら</a>@else「パスワードを忘れた方はこちら」@endguestからパスワードを再設定してください。
          </div>
     </div>
     
     <div class="faq__item">
          <div class="faq__question">パスワードを忘れました。どうすれば再設定できますか？</div>
          <div class="faq__answer">
               ログインページの@guest<a href="{{ route('password.request') }}">パスワードを忘れた方はこちら</a>@else「パスワードを忘れた方はこちら」@endguestからパスワードを再設定できます。
          </div>
     </div>
     <div class="faq__item">
          <div class="faq__question">登録したメールアドレスに通知が届きません。</div>
          <div class="faq__answer">
               迷惑メールフォルダに振り分けられている可能性があるので、確認してください。               
          </div>
     </div>
    
     <div class="faq__item">
          <div class="faq__question">アカウント情報を変更できますか？</div>
          <div class="faq__answer">
               @auth<a href="{{ route('member.profile.show') }}">マイアカウントの登録情報変更</a>@else「マイアカウントの登録情報変更」@endauthから、アカウント名や住所、通知設定などの情報を変更できます。
               なお、名前の変更、法人化については手続きが必要ですので、<a href="{{ route('contacts.create') }}">お問い合わせフォーム</a>からご連絡ください。
          </div>
     </div>
    
  </div>
</div>
@endsection