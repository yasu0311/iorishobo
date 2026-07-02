# タスク管理

> **プロジェクト名:** いおり書房 EC サイト（iorishobo）  
> **最終更新日:** 2026-07-02（静的ページ・サイトマップ追記）

---

## 進め方

大まかな実装順は次の 3 フェーズです。

| フェーズ | 内容 | 目的 |
|---------|------|------|
| **1. データ移行** | カラーミーショップ CSV → 自社 DB | 商品・顧客・過去注文を引き継ぐ |
| **2. ショップ機能** | EC の業務ロジック・画面（機能優先） | 購入・管理ができる状態にする |
| **3. サイトデザイン** | 見た目・UX の仕上げ | 公開品質の UI に整える |

設計の詳細は [データベース仕様書](./specification.md)、[テーブル定義書](./table-definition.md)、[ディレクトリ構成](./directory-structure.md) を参照。

### ステータス凡例

| 記号 | 意味 |
|------|------|
| `[ ]` | 未着手 |
| `[~]` | 作業中 |
| `[○]` | 完了 |
| `[×]` | 見送り・不要 |

---

## フェーズ 1: カラーミーショップからのデータ移行

仕様書が確定してからマイグレーション・インポートを実装する。  
本番移行は顧客・注文とも **数千件規模** を想定。

### 1.1 前提・環境

- [○] MySQL 8.x への接続設定（`.env` / `config/database.php`）
- [○] [テーブル定義書](./table-definition.md) の最終確認
- [○] `config/shop.php` と `.env.example` のキー定義（店舗名・事業者名・代表者・振込先・代引手数料・インボイス番号）
- [○] 本番用 CSV の取得手順を文書化（商品・オプション・顧客・受注一括）

### 1.2 データベース基盤

- [○] マイグレーション作成（`categories`, `products`, `product_images`, `product_variants`）
- [○] マイグレーション作成（`customers`, `users` 拡張）
- [○] マイグレーション作成（`shipping_methods`, `coupons`）
- [○] マイグレーション作成（`carts`, `cart_items`）
- [○] マイグレーション作成（`orders`, `order_items`, `refunds`, `watchlist_entries`）
- [○] Eloquent モデル一式とリレーション定義
- [○] `Enums`（`OrderStatus`, `PaymentStatus`, `PaymentMethod`, `DeviceType`）
- [○] `ShippingMethodSeeder`（クリックポスト・ゆうパック等の初期マスタ）
- [○] `AdminUserSeeder`（初期管理者）

### 1.3 CSV インポート基盤

- [○] `app/Services/Colorme/CsvReader.php`（文字コード・ヘッダー処理）
- [○] 移行ログ出力の共通仕組み（スキップ行・エラー行の記録）
- [○] 異常行スキップルールの実装（必須列が空の行はログに残してスキップ）

### 1.4 商品データ移行

- [○] `ProductImporter` — `product.csv` → `categories`, `products`
  - [○] 大/小カテゴリーの作成・`slug` = `id` の文字列化
  - [○] `colorme_product_id` / `slug`（数字 ID ベース）
  - [○] `is_published`（掲載設定の変換）
  - [○] `stock_managed`（在庫管理 on/off）
  - [○] 定価・型番・ISBN・重量・販売期間等の **スキップ列** を無視
- [○] `ProductImporter` — オプション CSV → `product_variants`
  - [○] `colorme_option_id`・価格・`attributes` JSON
  - [○] オプションなし商品は親名と同じ名前のバリアント 1 行
  - [○] 在庫数は `stock_managed = true` の商品のみ取り込み
- [○] `ImageDownloader` — 商品画像 URL からローカル保存（`storage/app/public/products/`）
- [○] `product_images` 登録（`sort_order` 0 = メイン、1〜9 = その他）
- [○] Artisan コマンド `ImportColormeProducts`
- [○] Artisan コマンド `DownloadProductImages`
- [○] 移行テスト（`tests/Feature/Colorme/`）— サンプル CSV で件数・代表データを検証

### 1.5 顧客データ移行

- [○] `CustomerImporter` — `customer.csv` → `customers`
  - [○] 住所は `address_line1` に全文、`address_line2` は NULL（自動分割しない）
  - [○] FAX 列は取り込まない
- [○] 会員（ユーザー登録=有 かつ メールあり）→ `users` 作成
  - [○] ランダムパスワード + 移行時は `email_verified_at` をセット
- [○] `customers.user_id` の紐付け
- [○] Artisan コマンド `ImportColormeCustomers`
- [○] 移行テスト — 会員/非会員・住所の取り込み確認

### 1.6 注文データ移行

- [○] `OrderImporter` — `sales_all.csv` → `orders`, `order_items`
  - [○] `buyer_*` / `shipping_*` スナップショット
  - [○] 配送先フリガナは任意（NULL 許容）
  - [○] `customer_id`（顧客 ID 一致時）、`user_id` は **常に NULL**（マイページ非表示）
  - [○] 決済方法の変換（`stripe` / `cod` / `bank_transfer` / `amazon_pay` 移行専用）
  - [○] 金額列・消費税・手数料・クーポン名・ポイント割引等
  - [○] `device`（PC / モバイル）
- [○] 商品明細と `product_variants` の紐付け（カラーミー商品 ID・オプション ID）
- [○] Artisan コマンド `ImportColormeOrders`
- [○] 移行テスト — 購入者≠配送先・Amazon Pay 過去注文の取り込み確認

### 1.7 移行の総合確認

- [○] サンプル CSV で全コマンドを通し実行（`import:colorme-all` + 統合テスト）
- [○] 件数突合（`ColormeMigrationVerifier` — 商品・顧客・注文の CSV vs DB）
- [○] `slug` 衝突・必須欠落の移行ログレビュー（コマンド出力 + SKIP/ERROR 集計）
- [○] 本番 CSV でリハーサル移行（ステージング環境）— `migrate:fresh --seed` 後に `import:colorme-all` で総合判定 OK（2026-06-29）

---

## フェーズ 2: ショップ機能

フェーズ 2 では **動くこと** を優先し、UI は最低限の Blade で実装する。  
見た目の仕上げはフェーズ 3 に回す。

### 2.1 共通基盤

- [○] `config/shop.php` 実装（事業者名・代表者・振込先・代引手数料・送料無料ライン・インボイス番号）
- [○] `ShippingFeeCalculator`（全国一律・`free_shipping_threshold` はクーポン適用後小計で判定）
- [○] `OrderNumberGenerator`（数字のみの注文番号）
- [○] `lang/ja` バリデーションメッセージ（電話 or 携帯どちらか必須等）
- [○] 旧 URL リダイレクト `?pid=` → `/products/{slug}`（301）

### 2.2 商品閲覧（機能のみ）

- [○] 商品一覧（掲載中のみ、`is_published`）
- [○] 商品詳細（バリアント選択・価格表示・在庫表示）
  - [○] `stock_managed = false` は常に購入可
  - [○] `stock_managed = true` かつ `stock = 0` は売り切れ
- [○] カテゴリ一覧・カテゴリ別商品一覧（`/categories/{slug}`）
- [○] ルーティング（`/products/{slug}`）

### 2.3 カート

- [○] `CartService` 実装
- [○] ゲストカート（`session_id`）
- [○] 会員カート（`user_id`、1 ユーザー 1 カート）
- [○] カート追加・数量変更・削除
- [○] 在庫チェック（追加時・表示時 — 超過時は警告＋チェックアウトブロック）
- [○] クーポン適用（1 カート 1 クーポン）
- [○] ログイン時のゲストカート → 会員カートマージ
- [○] `CleanupGuestCarts` コマンド + スケジュール（90 日超のゲストカート削除）

### 2.4 チェックアウト・決済

- [○] チェックアウト画面（購入者・配送先・配送方法・決済方法）
  - [○] 氏名必須、フリガナ任意
  - [○] メール必須、電話 or 携帯どちらか必須
  - [○] 住所 `address_line1` 必須、`address_line2` 任意
  - [○] 購入者と配送先の別人入力対応
- [○] `CheckoutService` — 注文作成・明細スナップショット
- [○] ゲスト購入時の `customers` find or create（メール正規化）
- [○] ログイン購入時の `orders.user_id` / `orders.customer_id` セット
- [○] 税額計算（内税 10% 固定、`floor(subtotal × 10 / 110)`）
- [○] 代金引換（`cod`）— 手数料表示・チェックアウト送信時に在庫減算
- [○] 銀行振込（`bank_transfer`）— 振込案内・入金は手動確認
- [○] Stripe 連携
  - [○] `StripeService`（PaymentIntent 作成）
  - [○] チェックアウト送信 → `orders` を `pending` 作成 → カード決済
  - [○] `StripeWebhookController`（`payment_intent.succeeded` → `paid`、冪等処理）
  - [○] 入金確認後の在庫減算（`stripe` / `bank_transfer`）
- [○] 注文確認メール（`orders.buyer_email` 宛）
- [○] 振込案内メール（7 日以内の案内文）
- [○] 注文完了画面
- [○] チェックアウト・決済の Feature テスト

### 2.5 会員機能

- [○] 会員登録（メール認証必須、認証完了までログイン不可）
- [○] ログイン・ログアウト・パスワードリセット
- [○] マイページ — プロフィール編集（`users` + `customers` のメール同期）
- [○] マイページ — 注文履歴（`orders.user_id` = ログイン中ユーザーのみ）
- [○] 領収書表示（税込合計・うち消費税 10%・インボイス登録番号）
- [○] `OrderPolicy`（他人の注文閲覧不可）

### 2.6 管理画面

- [○] `EnsureAdmin` ミドルウェア + `routes/admin.php`
- [○] ダッシュボード（未処理注文数など最低限のサマリ）
- [○] 注文管理
  - [○] 一覧・詳細・検索
  - [○] 入金確認（`payment_status = paid` 手動更新）
  - [○] 発送処理（`shipped`、追跡番号入力）
  - [○] 振込未入金の発送ブロック
  - [○] キャンセル（未発送のみ、Stripe は返金確認付き）
- [○] 返金処理
  - [○] Stripe Refund API 連携
  - [○] 手動返金記録（`refunds` テーブル）
  - [○] 在庫戻し（キャンセル・返金時）
- [○] 商品管理（CRUD・掲載 on/off・バリアント・画像）
- [○] 顧客管理（一覧・詳細・注文履歴 `customer_id` 経由）
- [○] クーポン管理（新規登録・有効期限・使用回数上限）
- [○] 配送方法管理（`base_fee`・`free_shipping_threshold`）
- [○] 要注意リスト（`watchlist_entries`）— 注文詳細での警告表示
- [○] 管理画面の Feature テスト（入金・発送・キャンセル・返金）

### 2.7 運用・周辺（機能）

- [○] 発送通知メール
- [○] 404 / エラーページ（最低限）
- [○] ヤマト B2・ゆうパック等への配送 CSV エクスポート
- [○] 静的ページ（`/static/law`・`/static/privacy-policy`・`/static/terms`）
  - [○] 特商法は `config('shop.*')` 参照・`noindex`
  - [○] プライバシーポリシー・利用規約（公開前に文言の最終確認が必要）
- [○] お問い合わせフォーム（入力 → 確認 → 送信、店舗・送信者へメール）
- [○] フッターから静的ページ・お問い合わせへのリンク
- [○] `sitemap.xml`（トップ・商品・カテゴリ・掲載中商品・公開静的ページ）
- [○] `robots.txt`（`APP_URL` ベースで Sitemap URL を出力）
- [○] Feature テスト（`StaticPageTest` / `ContactTest` / `SitemapTest`）

---

## フェーズ 3: サイトデザイン

フェーズ 2 で動作が固まったあと、UI / UX を整える。  
管理画面は機能優先の簡素な UI から、必要に応じて改善する。

**スタイル実装方針**（[sample_code](./sample_code/) を参考）:

- **Tailwind は使わない** — Laravel 初期の `resources/css/app.css`（Tailwind）はストアフロント・管理画面では使用しない
- **プレーン CSS** — `public/css/` に配置し、レイアウトから `asset('css/...')` で読み込む（Vite ビルド不要）
- **レイアウトごとに CSS を分割** — 共通 → レイアウト固有 → ページ固有の順で `<link>` する
- **JS も同様** — `public/js/common/`・`public/js/front/`・`public/js/admin/` に配置（Stripe 等、バンドルが必要なもののみ Vite を使う）
- **レスポンシブ方針** — ストアフロント（ショップページ）は PC / スマホ対応。管理画面は **PC 専用**（スマホ向けレイアウト・ハンバーガーメニューは作らない）

### 3.1 設計・準備

> **方針:** 文書だけでは判断しづらいため、3.2 の CSS 基盤を先に実装してブラウザで確認しながら調整する。以下の未着手項目は実装を見てから必要に応じて追記する。

- [○] デザイン方針の決定（トーン、カラー、タイポグラフィ）— [design-guidelines.md](./design-guidelines.md)
  - [○] `:root` の CSS 変数定義（`--primary-color` 等）を文書化
- [○] 要素整理（ロゴ・配色・雰囲気）— 実装を見て調整（ブルー系・クールで確定）
- [~] 静的アセット配置方針（`public/images/`・`public/favicon.png`）— 実装を見て調整
- [○] **CSS / JS ディレクトリ構成の決定** — `public/css/`・`public/js/` に初版を配置済み
  - [○] [sample_code](./sample_code/public/css/) の構成をベースに、いおり書房向けのファイル一覧を確定する
  - [○] 各 CSS ファイルの責務（レイアウト / コンポーネント / ユーティリティ）— ファイル先頭コメント＋本項
  - [○] レイアウトごとの `<link>` 読み込み順 — `utility` → `component` → `content` → `layout`
  - [○] `public/js/common/common.js`（ハンバーガーメニュー）を配置
- [~] **共通コンポーネント一覧の確認** — 実装を見て判断
  - [ ] [sample_code の components](./sample_code/resources/views/components/) を参照し、流用・新規・不要を仕分けする
  - [ ] フェーズ 2 の既存 partial（`product-card`、`watchlist-warning` 等）を棚卸しし、コンポーネント化するか判断する
  - [ ] ストアフロント / 管理画面 / 認証で必要なコンポーネントを一覧化する（例: `pagination`, `alert`, `badge`, `product-card`）
- [~] ワイヤーフレームまたはモックアップ（主要画面）— 実サイトで代替
- [○] **レスポンシブ方針の決定**
  - [○] ストアフロント — PC / スマホ両対応（768px 以下でドロワーメニュー）
  - [○] 管理画面 — PC 専用（最小幅を想定し、スマホ向けの調整は行わない）

### 3.2 CSS / JS 基盤

3.1 で決めた構成に従い、フェーズ 2 のインライン `<style>` を外部ファイルへ切り出す。

#### CSS ディレクトリ（`public/css/`）

- [○] `common/utility.css` — 表示制御・flex・テキスト揃え・色クラス等のユーティリティ
- [○] `common/auth.css` — 認証画面専用スタイル
- [○] `front/component.css` — ボタン・フォーム・テーブル・見出し等の共通 UI
- [○] `front/content.css` — コンテンツ領域の共通スタイル
- [○] `front/layout.css` — ストアフロント用レイアウト（ヘッダー・フッター・ナビ・`:root` 変数・レスポンシブ）
- [○] `admin/layout.css` — 管理画面レイアウト（PC 専用・固定サイドメニュー・ヘッダー）
- [○] `admin/component.css` — 管理画面の共通 UI（バッジ・テーブル・フォーム等）

#### JS ディレクトリ（`public/js/`）

- [○] `common/common.js` — ストアフロント用ハンバーガーメニュー開閉・マスク等（管理画面では不要）
- [ ] `front/content.js` — ストアフロント固有の UI 操作
- [ ] `admin/content.js` — 管理画面固有の UI 操作
- [ ] Stripe Elements 用 JS は既存の Vite エントリ（`resources/js/front/checkout.js`）を維持

### 3.3 レイアウト・コンポーネント

- [○] `layouts/front.blade.php` — ストアフロント用（3.1 で決めた CSS を読み込み）
  - [○] ヘッダー・フッター・ナビゲーション
  - [○] OGP メタタグ・`@yield('head_meta')` ※現行 `layouts/front.blade.php` に実装済み
  - [○] `@yield('styles')` / `@yield('script')` でページ固有アセットを差し込み
- [○] `layouts/auth.blade.php` — ログイン・会員登録・パスワードリセット用（`auth.css` + `utility.css`）
- [○] `layouts/admin.blade.php` — 管理画面用（PC 専用レイアウト・`admin/layout.css` + `admin/component.css` + `utility.css`）
- [ ] `layouts/mail-html.blade.php` / `layouts/mail-text.blade.php` — メール用レイアウト
- [○] `layouts/error.blade.php` — エラーページ用
- [○] 既存 `layouts/front.blade.php` のインライン CSS を撤去し、外部 CSS へ移行
- [~] 3.1 で確定したコンポーネントを `resources/views/components/` に実装
  - [○] `pagination` — ページネーション UI
  - [○] `alert` — フラッシュメッセージ
  - [○] `input-error` — バリデーションエラー表示
  - [○] 既存 partial のコンポーネント化（`product-card`）※`watchlist-warning` は管理 partial のまま
- [ ] `lang/ja` の文言統一（ボタン・ラベル・エラー表示）

### 3.4 ストアフロント画面

レスポンシブ対応（PC / スマホ）で実装する。

- [○] トップページ
- [○] 商品一覧・カテゴリページのレイアウト
- [○] 商品詳細（画像ギャラリー・オプション選択 UI・売り切れ表示）
- [○] カート画面
- [○] チェックアウト（フォーム UX・送料/手数料の見せ方）
- [○] Stripe Elements のスタイル調整
- [○] 注文完了・領収書
- [○] マイページ（注文履歴・プロフィール）
- [○] ログイン・会員登録・パスワードリセット画面（`auth` レイアウトへ移行）
- [○] 静的ページ・お問い合わせ（特商法・プライバシー・利用規約）のレイアウト仕上げ ※機能はフェーズ 2.7 で実装済み

### 3.5 管理画面デザイン

PC 専用で実装する（スマホ向けレイアウト・ハンバーガーメニューは対象外）。

- [○] 既存 `layouts/admin.blade.php` のインライン CSS を `public/css/admin/` へ切り出し
- [○] 固定サイドメニュー・ヘッダーの実装（PC 画面幅前提）
- [○] 注文一覧・詳細の視認性改善（ステータスバッジ・要注意警告）
- [○] 商品・顧客・クーポン画面のフォーム整理

### 3.6 メールテンプレート

- [ ] 注文確認メール（HTML + テキスト）
- [ ] 振込案内メール
- [ ] 発送通知メール
- [ ] 各クライアントでの表示確認

### 3.7 仕上げ・公開前

- [ ] 実機・主要ブラウザでの表示確認
  - [ ] ストアフロント — PC / スマホ両方
  - [ ] 管理画面 — PC のみ
- [ ] アクセシビリティの最低限チェック（フォーカス・コントラスト・ラベル）
- [○] `robots.txt`・`sitemap.xml`
- [ ] OGP・ファビコン
- [○] フッター情報（店舗名・連絡先・特商法リンク等）の掲載 ※リンク・連絡先は実装済み。デザイン仕上げは 3.4
- [ ] パフォーマンス確認（画像最適化・LCP 等）
- [ ] 静的ページ・お問い合わせの文言最終確認（事業者情報・返品規定等を `.env` / 本文に反映）

---

## 依存関係メモ

```
フェーズ 1（移行）
  └─ 1.2 DB 基盤 → 1.3 インポート基盤 → 1.4 商品 → 1.5 顧客 → 1.6 注文 → 1.7 確認

フェーズ 2（機能）
  └─ 2.1 共通基盤
       ├─ 2.2 商品閲覧
       ├─ 2.3 カート → 2.4 チェックアウト
       ├─ 2.5 会員（カートマージと並行可）
       └─ 2.6 管理画面（注文データがあれば着手可。2.4 完了後に本格運用）

フェーズ 3（デザイン）
  └─ フェーズ 2 の各画面が動いてから着手
```

---

## 改訂履歴

| 日付 | 内容 |
|------|------|
| 2026-06-25 | 初版作成 |
| 2026-07-02 | フェーズ 3 を sample_code 準拠の CSS 方針（Tailwind 不使用・`public/css/` 構成）に更新 |
| 2026-07-02 | フェーズ 3.1 に CSS 構成決定・コンポーネント一覧確認タスクを追加 |
| 2026-07-02 | レスポンシブ方針を明記（ストアフロント対応・管理画面 PC 専用） |
| 2026-07-02 | フェーズ 2.7 に静的ページ・お問い合わせ・sitemap / robots を追加。フェーズ 3.7 の該当項目を進捗反映 |
| 2026-07-02 | フェーズ 3.2–3.3 — 管理画面 CSS 切り出し・認証レイアウト・コンポーネント（alert / input-error / product-card） |
