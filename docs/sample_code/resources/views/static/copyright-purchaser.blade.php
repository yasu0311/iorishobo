@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', '著作権上の注意点（購入者）')

@section('content')

<div class="copyright-purchaser">
  <h1>著作権上の注意点（購入者）</h1>
  <p class="copyright-purchaser__lead copyright-purchaser__lead--top">購入時に「個人利用」「学校利用」「商用利用」のいずれかを選択します。該当する利用をご確認ください。</p>

  <section class="copyright-purchaser__section">
    <h2>個人利用</h2>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__text">学習者本人やその家族が購入する場合など、教材を使用して収益を得る目的のない利用です。例：お子さんの家庭学習用に購入して兄弟で使う、自分用に購入して家族で使う、など。</p>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">利用範囲</p>
      <p class="copyright-purchaser__text">利用できるのは<strong>購入者とその家族（1家族）</strong>です。兄弟での利用は可能です。友人など家族以外への配布・共有はできません。</p>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">できること・できないこと</p>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ok">OK</p>
        <ul class="copyright-purchaser__list">
          <li>編集・加工、印刷・コピーは自由</li>
          <li>ご自身やご家族の学習に使用してよい</li>
        </ul>
      </div>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ng">NG</p>
        <ul class="copyright-purchaser__list">
          <li>利用範囲外に渡す・共有すること</li>
          <li>不特定多数に公開すること（ウェブサイト、SNSなど）</li>
          <li>当教材を販売・転売すること</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="copyright-purchaser__section">
    <h2>学校利用</h2>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__text">幼稚園、保育園、小学校・中学校、高等学校、大学など、教育施設での利用です。授業・補習・課題などで教員が教材を使用する場合も含みます。例：学校名で購入するとその校内の教員が授業・補習で使用可能。教員名で購入するとその教員が転勤先でも使用可能。</p>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">利用範囲</p>
      <p class="copyright-purchaser__text">利用範囲は注文時にご入力いただく「購入権利者」で決まります。<strong>教員名</strong>を入れるか<strong>学校名</strong>を入れるかで異なります。</p>
      <ul class="copyright-purchaser__list">
        <li><strong>購入権利者を「個人」とする場合</strong>（教員名を入力）：利用できるのは<strong>購入権利者本人のみ</strong>です。1人の教員につき教材データ1つを購入してください。転勤しても本人であれば引き続き利用できます。</li>
        <li><strong>購入権利者を「組織」とする場合</strong>（学校名を入力）：利用できるのは<strong>その学校の範囲内</strong>です。1校につき教材データ1つを購入してください。担当する教員が変わっても、同じ学校であれば引き続き利用できます。</li>
      </ul>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">できること・できないこと</p>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ok">OK</p>
        <ul class="copyright-purchaser__list">
          <li>編集・加工、印刷・コピーは自由</li>
          <li>授業・補習・課題などで教員が使用してよい</li>
        </ul>
      </div>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ng">NG</p>
        <ul class="copyright-purchaser__list">
          <li>利用範囲外に渡す・共有すること</li>
          <li>不特定多数に公開すること（ウェブサイト、SNSなど）</li>
          <li>当教材を販売・転売すること</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="copyright-purchaser__section">
    <h2>商用利用</h2>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__text">塾、予備校、家庭教師など、教材を使用して収益を得る目的のある利用です。授業・補習・課題などで講師が教材を使用する場合も含みます。例：講師名で購入するとその講師が担当する生徒に使用可能（塾講師は1人1購入）。塾名で購入するとその塾内の講師が使用可能。</p>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">利用範囲</p>
      <p class="copyright-purchaser__text">利用範囲は注文時にご入力いただく「購入権利者」で決まります。<strong>講師名・家庭教師名</strong>を入れるか<strong>塾名・予備校名</strong>を入れるかで異なります。</p>
      <ul class="copyright-purchaser__list">
        <li><strong>購入権利者を「個人」とする場合</strong>（講師名・家庭教師名を入力）：利用できるのは<strong>購入権利者本人のみ</strong>です。1人の講師・家庭教師につき教材データ1つを購入してください。その講師が担当する複数の生徒に使用できます。</li>
        <li><strong>購入権利者を「組織」とする場合</strong>（塾名・予備校名を入力）：利用できるのは<strong>その組織の範囲内</strong>です。1組織につき教材データ1つを購入してください。担当する講師が変わっても、同じ組織であれば引き続き利用できます。</li>
      </ul>
    </div>
    <div class="copyright-purchaser__block">
      <p class="copyright-purchaser__label">できること・できないこと</p>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ok">OK</p>
        <ul class="copyright-purchaser__list">
          <li>編集・加工、印刷・コピーは自由</li>
          <li>授業・補習・課題などで講師が使用してよい</li>
        </ul>
      </div>
      <div class="copyright-purchaser__okng">
        <p class="copyright-purchaser__ng">NG</p>
        <ul class="copyright-purchaser__list">
          <li>利用範囲外に渡す・共有すること</li>
          <li>不特定多数に公開すること（ウェブサイト、SNSなど）</li>
          <li>当教材を販売・転売すること</li>
        </ul>
      </div>
    </div>
  </section>
</div>

@endsection
