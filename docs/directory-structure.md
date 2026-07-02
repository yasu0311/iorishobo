# ディレクトリ構成

> **プロジェクト名:** いおり書房 EC サイト（iorishobo）  
> **フレームワーク:** Laravel 12  
> **最終更新日:** 2026-06-25

---

## 概要

本リポジトリは Laravel ベースの EC サイト構築プロジェクトです。  
カラーミーショップからの移行データ（CSV）を取り込み、独自 EC サイトとして運用する想定です。

設計の根拠は [データベース仕様書](./specification.md) および [テーブル定義書](./table-definition.md) を参照してください。

### 凡例

| 記号 | 意味 |
|------|------|
| **（既存）** | リポジトリにすでに存在する |
| **（予定）** | 実装時に追加する想定 |
| **（自動生成）** | ビルド・実行時に生成され、原則 git 管理外 |

---

## ディレクトリツリー（目標構成）

```
iorishobo/
├── app/                                    # アプリケーション本体
│   ├── Console/
│   │   └── Commands/                       # （既存・空）Artisan コマンド
│   │       ├── ImportColormeProducts.php   # （予定）商品 CSV 取込
│   │       ├── ImportColormeCustomers.php  # （予定）顧客 CSV 取込
│   │       ├── ImportColormeOrders.php     # （予定）注文 CSV 取込
│   │       ├── DownloadProductImages.php   # （予定）商品画像のローカル保存
│   │       └── CleanupGuestCarts.php       # （予定）ゲストカート定期削除（90日）
│   ├── Enums/                              # （予定）列挙型
│   │   ├── OrderStatus.php                 #   注文ステータス（pending / shipped / cancelled 等）
│   │   ├── PaymentStatus.php               #   入金ステータス（pending / paid 等）
│   │   ├── PaymentMethod.php               #   決済方法（stripe / cod / bank_transfer）
│   │   └── DeviceType.php                  #   デバイス種別（PC / mobile）
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php              # （既存）基底コントローラ
│   │   │   ├── Admin/                      # （予定）管理画面
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── OrderController.php     #   注文一覧・詳細・入金確認・発送・キャンセル
│   │   │   │   ├── ProductController.php   #   商品 CRUD・掲載設定
│   │   │   │   ├── CustomerController.php  #   顧客一覧・詳細
│   │   │   │   ├── CouponController.php    #   クーポン管理
│   │   │   │   ├── ShippingMethodController.php
│   │   │   │   ├── WatchlistController.php #   要注意リスト
│   │   │   │   └── RefundController.php    #   返金処理（Stripe API 連携）
│   │   │   ├── Auth/                       # （予定）認証
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── PasswordResetController.php
│   │   │   │   └── EmailVerificationController.php
│   │   │   ├── Front/                      # （予定）ストアフロント
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── ProductController.php   #   商品一覧・詳細
│   │   │   │   ├── CategoryController.php  #   カテゴリ一覧
│   │   │   │   ├── CartController.php      #   カート操作
│   │   │   │   ├── CheckoutController.php  #   チェックアウト・注文確定
│   │   │   │   ├── MypageController.php    #   会員マイページ・注文履歴
│   │   │   │   ├── ReceiptController.php   #   領収書表示
│   │   │   │   └── LegacyRedirectController.php  # カラーミー ?pid= → 301
│   │   │   └── Webhook/
│   │   │       └── StripeWebhookController.php   # （予定）Stripe Webhook
│   │   ├── Middleware/
│   │   │   └── EnsureAdmin.php             # （予定）is_admin チェック
│   │   └── Requests/                       # （予定）フォームバリデーション
│   │       ├── Front/
│   │       │   ├── AddToCartRequest.php
│   │       │   ├── CheckoutRequest.php
│   │       │   └── UpdateProfileRequest.php
│   │       └── Admin/
│   │           ├── StoreProductRequest.php
│   │           ├── StoreCouponRequest.php
│   │           └── RefundRequest.php
│   ├── Jobs/                               # （予定）非同期ジョブ
│   │   └── SendOrderConfirmationMail.php
│   ├── Mail/                               # （予定）Mailable
│   │   ├── OrderConfirmed.php              #   注文確認メール
│   │   ├── BankTransferInstructions.php    #   振込案内メール
│   │   └── OrderShipped.php                #   発送通知メール
│   ├── Models/
│   │   ├── User.php                        # （既存）Laravel 標準ユーザー
│   │   ├── Category.php                    # （予定）
│   │   ├── Product.php
│   │   ├── ProductImage.php
│   │   ├── ProductVariant.php
│   │   ├── Customer.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Refund.php
│   │   ├── Coupon.php
│   │   ├── ShippingMethod.php
│   │   └── WatchlistEntry.php
│   ├── Policies/                           # （予定）認可
│   │   └── OrderPolicy.php                 #   マイページの注文閲覧制御
│   ├── Providers/
│   │   └── AppServiceProvider.php          # （既存）
│   ├── Services/
│   │   ├── Colorme/                        # （既存・空）カラーミー移行
│   │   │   ├── CsvReader.php               # （予定）CSV 読み込み共通
│   │   │   ├── ProductImporter.php
│   │   │   ├── CustomerImporter.php
│   │   │   ├── OrderImporter.php
│   │   │   └── ImageDownloader.php
│   │   ├── Cart/
│   │   │   └── CartService.php             # （予定）カート操作・ログイン時マージ
│   │   ├── Checkout/
│   │   │   └── CheckoutService.php         # （予定）注文作成・在庫チェック
│   │   ├── Order/
│   │   │   ├── OrderNumberGenerator.php    # （予定）注文番号採番
│   │   │   └── OrderExportService.php      # （予定）ヤマト B2 等への配送 CSV 出力（将来）
│   │   ├── Payment/
│   │   │   └── StripeService.php           # （予定）PaymentIntent・Refund API
│   │   └── Shipping/
│   │       └── ShippingFeeCalculator.php     # （予定）送料計算（全国一律）
│   └── View/
│       └── Components/                     # （予定）Blade コンポーネント
│           ├── ProductCard.php
│           ├── CartSummary.php
│           └── Admin/
│               └── WatchlistAlert.php
├── bootstrap/                              # （既存）フレームワーク起動
│   └── cache/                              # （自動生成）
├── colorme_data/                           # （既存）移行用 CSV（分析・取込元）
│   ├── customer.csv
│   ├── product.csv
│   ├── sales_all.csv
│   └── option_csv_download_*.csv
├── config/                                 # （既存）設定ファイル
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php                        # Stripe 等の外部サービス
│   ├── session.php
│   └── shop.php                            # （予定）店舗情報・振込先・代引手数料・インボイス番号
├── database/
│   ├── factories/
│   │   ├── UserFactory.php                 # （既存）
│   │   ├── ProductFactory.php              # （予定）
│   │   ├── CustomerFactory.php             # （予定）
│   │   └── OrderFactory.php                # （予定）
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php   # （既存）
│   │   ├── 0001_01_01_000001_create_cache_table.php   # （既存）
│   │   ├── 0001_01_01_000002_create_jobs_table.php    # （既存）
│   │   └── create_*_table.php              # （予定）仕様書 §3.1 の各テーブル
│   └── seeders/
│       ├── DatabaseSeeder.php              # （既存）
│       ├── ShippingMethodSeeder.php        # （予定）クリックポスト・ゆうパック等
│       └── AdminUserSeeder.php             # （予定）初期管理者
├── docs/                                   # （既存）プロジェクトドキュメント
│   ├── database.md
│   ├── directory-structure.md              # 本ファイル
│   ├── specification.md
│   └── table-definition.md
├── lang/
│   └── ja/                                 # （予定）日本語バリデーションメッセージ等
├── public/                                 # （既存）Web 公開ディレクトリ
│   ├── index.php
│   ├── build/                              # （自動生成）Vite ビルド成果物
│   └── storage/                            # （自動生成）storage/app/public へのシンボリックリンク
├── resources/
│   ├── css/
│   │   ├── app.css                         # （既存）
│   │   ├── front.css                       # （予定）ストアフロント用
│   │   └── admin.css                       # （予定）管理画面用
│   ├── js/
│   │   ├── app.js                          # （既存）
│   │   ├── bootstrap.js                    # （既存）
│   │   ├── front/                          # （予定）
│   │   │   ├── cart.js
│   │   │   └── checkout.js                 #   Stripe Elements 連携
│   │   └── admin/                          # （予定）
│   └── views/
│       ├── welcome.blade.php               # （既存）初期画面（本番では置き換え）
│       ├── layouts/                        # （予定）
│       │   ├── front.blade.php
│       │   ├── admin.blade.php
│       │   ├── auth.blade.php
│       │   └── error.blade.php
│       ├── components/                     # （予定）共通 UI パーツ
│       ├── front/                          # （予定）ストアフロント
│       │   ├── home/
│       │   ├── products/
│       │   ├── categories/
│       │   ├── cart/
│       │   ├── checkout/
│       │   └── mypage/
│       ├── admin/                          # （予定）管理画面
│       │   ├── dashboard/
│       │   ├── orders/
│       │   ├── products/
│       │   ├── customers/
│       │   ├── coupons/
│       │   ├── shipping-methods/
│       │   └── watchlist/
│       ├── mail/                           # （予定）メールテンプレート
│       └── errors/                         # （予定）404 等
├── routes/
│   ├── web.php                             # （既存）フロント・認証ルート
│   ├── admin.php                           # （予定）管理画面ルート（/admin プレフィックス）
│   └── console.php                         # （既存）Artisan・スケジュール定義
├── storage/
│   ├── app/
│   │   ├── private/                        # （既存）非公開ファイル
│   │   └── public/
│   │       └── products/                   # （予定）商品画像の保存先
│   ├── framework/                          # （既存）キャッシュ・セッション等
│   └── logs/                               # （既存）アプリケーションログ
├── tests/
│   ├── TestCase.php                        # （既存）
│   ├── Feature/
│   │   ├── ExampleTest.php                 # （既存）
│   │   ├── Admin/                          # （予定）管理画面テスト
│   │   ├── Auth/                           # （予定）認証・メール認証
│   │   ├── Checkout/                       # （予定）チェックアウト・決済フロー
│   │   ├── Colorme/                        # （予定）CSV 移行テスト
│   │   └── Front/                          # （予定）商品表示・カート
│   └── Unit/
│       ├── ExampleTest.php                 # （既存）
│       └── Services/                       # （予定）サービスクラスの単体テスト
├── artisan                                   # （既存）
├── composer.json                             # （既存）
├── package.json                              # （既存）
├── phpunit.xml                               # （既存）
└── vite.config.js                            # （既存）
```

> `vendor/`（Composer）、`node_modules/`（npm）、`.env` は `.gitignore` によりリポジトリ管理外です。

---

## 実装フェーズと追加ディレクトリの対応

仕様書の機能を、おおまかな実装順に整理したものです。

### フェーズ 1: データ移行基盤

カラーミー CSV から DB へデータを取り込む。

| 追加先 | 内容 |
|--------|------|
| `app/Services/Colorme/` | CSV パーサ・各エンティティのインポータ |
| `app/Console/Commands/ImportColorme*.php` | 手動実行用 Artisan コマンド |
| `database/migrations/` | 全テーブルのマイグレーション |
| `app/Models/` | Eloquent モデル一式 |
| `database/seeders/ShippingMethodSeeder.php` | 配送方法マスタの初期データ |

### フェーズ 2: ストアフロント

商品閲覧・カート・チェックアウト。

| 追加先 | 内容 |
|--------|------|
| `app/Http/Controllers/Front/` | 商品・カテゴリ・カート・チェックアウト |
| `app/Services/Cart/` `Checkout/` `Shipping/` | ビジネスロジック |
| `app/Http/Requests/Front/` | 入力バリデーション |
| `resources/views/front/` | 画面テンプレート |
| `resources/js/front/checkout.js` | Stripe Elements |
| `app/Http/Controllers/Webhook/StripeWebhookController.php` | 入金確認 Webhook |
| `app/Services/Payment/StripeService.php` | Stripe API ラッパ |
| `app/Mail/` | 注文確認・振込案内メール |
| `config/shop.php` | 店舗固定情報（振込先・代引手数料・インボイス番号） |

### フェーズ 3: 会員機能

| 追加先 | 内容 |
|--------|------|
| `app/Http/Controllers/Auth/` | ログイン・会員登録・メール認証 |
| `app/Http/Controllers/Front/MypageController.php` | 注文履歴・プロフィール編集 |
| `app/Policies/OrderPolicy.php` | `orders.user_id` による閲覧制御 |

### フェーズ 4: 管理画面

`is_admin = true` のユーザーのみアクセス可（`EnsureAdmin` ミドルウェア）。

| 追加先 | 内容 |
|--------|------|
| `routes/admin.php` | `/admin` 配下のルート定義 |
| `app/Http/Controllers/Admin/` | 注文・商品・顧客・クーポン・配送・要注意リスト・返金 |
| `resources/views/admin/` | 管理画面テンプレート |
| `app/Http/Controllers/Admin/RefundController.php` | Stripe Refund API 連携 |

### フェーズ 5: 運用・周辺機能

| 追加先 | 内容 |
|--------|------|
| `app/Console/Commands/CleanupGuestCarts.php` | ゲストカート 90 日超の定期削除 |
| `app/Services/Order/OrderExportService.php` | ヤマト B2・ゆうパック等への配送 CSV 出力（将来） |
| `app/Http/Controllers/Front/LegacyRedirectController.php` | `?pid=` → `/products/{slug}` の 301 リダイレクト |
| `app/Http/Controllers/Front/ReceiptController.php` | 領収書（インボイス対応） |

---

## 各ディレクトリの説明

### `app/`

アプリケーションのビジネスロジックを配置します。コントローラは薄く保ち、複雑な処理は `Services/` に集約する方針です。

| パス | 役割 |
|------|------|
| `Console/Commands/` | `php artisan` で実行するバッチ・移行コマンド |
| `Enums/` | 注文ステータス・決済方法など、DB 値と対応する列挙型 |
| `Http/Controllers/Front/` | お客様向け画面のコントローラ |
| `Http/Controllers/Admin/` | 管理者向け画面のコントローラ |
| `Http/Controllers/Auth/` | ログイン・会員登録・パスワードリセット |
| `Http/Controllers/Webhook/` | Stripe 等の外部サービスからのコールバック |
| `Http/Middleware/EnsureAdmin.php` | 管理画面のアクセス制御 |
| `Http/Requests/` | フォーム入力のバリデーションルール |
| `Jobs/` | メール送信など時間のかかる処理の非同期実行 |
| `Mail/` | Mailable クラス（注文確認・振込案内等） |
| `Models/` | Eloquent モデル（[テーブル定義書](./table-definition.md) の各テーブルに対応） |
| `Policies/` | マイページでの注文閲覧権限など |
| `Services/Colorme/` | カラーミー CSV の読み込み・変換・DB 投入 |
| `Services/Cart/` | カート操作・ゲスト/会員マージ |
| `Services/Checkout/` | 注文作成・在庫チェック・クーポン適用 |
| `Services/Payment/` | Stripe PaymentIntent・Refund |
| `Services/Shipping/` | 送料計算（全国一律・送料無料ライン） |
| `View/Components/` | 再利用可能な Blade コンポーネント |

### `colorme_data/`

カラーミーショップからエクスポートした移行用 CSV を格納します。  
本番移行時も同形式の CSV をここに配置して `ImportColorme*` コマンドで取り込みます。  
取得手順は [カラーミー CSV 取得手順](../docs/colorme-csv-export.md) を参照。

### `config/`

| ファイル | 内容 |
|----------|------|
| `shop.php` **（予定）** | 店舗名・電話・振込先口座・代引手数料・インボイス登録番号。`config('shop.name')` 形式で Blade / メールから参照 |
| `services.php` | Stripe の API キー等（`.env` 経由） |
| その他 | Laravel 標準の各種設定 |

### `database/`

| パス | 役割 |
|------|------|
| `migrations/` | テーブル作成・変更（仕様確定後に一括作成） |
| `seeders/ShippingMethodSeeder.php` | クリックポスト・ゆうパック等の初期マスタ |
| `seeders/AdminUserSeeder.php` | 初回デプロイ用の管理者アカウント |
| `factories/` | テスト・開発用のダミーデータ生成 |

### `lang/ja/`

バリデーションエラーメッセージや UI 文言の日本語化。チェックアウトフォームの必須/任意ルール（電話 or 携帯のどちらか必須等）に対応したメッセージを定義します。

### `public/`

Web サーバーのドキュメントルートです。  
`php artisan storage:link` 実行後、`public/storage/` 経由で商品画像（`storage/app/public/products/`）を配信します。

### `resources/`

| パス | 役割 |
|------|------|
| `views/front/` | 商品一覧・詳細・カート・チェックアウト・マイページ |
| `views/admin/` | 注文管理・商品管理・顧客管理・クーポン・要注意リスト |
| `views/mail/` | メール HTML テンプレート |
| `views/layouts/` | フロント・管理画面・ゲスト用レイアウト |
| `js/front/checkout.js` | Stripe Elements によるカード決済 UI |
| `css/front.css` / `admin.css` | 画面別スタイル（Vite でビルド） |

### `routes/`

| ファイル | 役割 |
|----------|------|
| `web.php` | ストアフロント・認証・Webhook ルート |
| `admin.php` **（予定）** | `/admin` プレフィックス付きの管理画面ルート |
| `console.php` | Artisan コマンド定義・ゲストカート削除のスケジュール |

想定 URL 例:

| URL | 用途 |
|-----|------|
| `/products/{slug}` | 商品詳細 |
| `/categories/{slug}` | カテゴリ一覧 |
| `/cart` | カート |
| `/checkout` | チェックアウト |
| `/mypage` | マイページ |
| `/admin/orders` | 管理画面・注文一覧 |
| `/webhook/stripe` | Stripe Webhook エンドポイント |
| `/?pid={id}` | カラーミー旧 URL → 301 リダイレクト |

### `storage/app/public/products/`

商品画像の保存先です。移行時にカラーミーの画像 URL からダウンロードし、`product_images.path` にローカルパスを記録します。

### `tests/`

| パス | 役割 |
|------|------|
| `Feature/Colorme/` | CSV 移行の結合テスト |
| `Feature/Checkout/` | チェックアウト・決済・在庫減算のフロー |
| `Feature/Admin/` | 管理画面の操作（入金確認・発送・キャンセル・返金） |
| `Feature/Auth/` | 会員登録・メール認証 |
| `Unit/Services/` | 送料計算・注文番号採番等の単体テスト |

---

## 主要ファイル（ルート直下）

| ファイル | 説明 |
|----------|------|
| `.env.example` | 環境変数テンプレート（`SHOP_NAME`・`STRIPE_KEY` 等を追記予定） |
| `artisan` | Laravel CLI エントリポイント |
| `composer.json` / `composer.lock` | PHP 依存関係（Stripe SDK 等を追加予定） |
| `package.json` / `package-lock.json` | Node.js 依存関係 |
| `phpunit.xml` | テストランナー設定 |
| `vite.config.js` | CSS / JS のビルド設定 |

---

## 意図的に作らないもの

仕様書で見送りとされている機能に対応するディレクトリ・ファイルは作成しません。

| 機能 | 理由 |
|------|------|
| `shop_settings` テーブル / 設定管理画面 | 店舗情報は `config/shop.php` + `.env` で十分（§3.18） |
| 会員価格・ポイント関連 | 新規サイトでは不使用（§3.7.1） |
| ギフト機能 | UI・DB カラムとも設けない（§3.15） |
| `customer_addresses` | 保存配送先は持たない（§3.19） |
| Amazon Pay 連携 | 移行データの表示のみ。新規決済は Stripe / 代引 / 振込の 3 種 |
