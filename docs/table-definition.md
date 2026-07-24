# テーブル定義書

> **ステータス: 確定**（v0.47 / 2026-07-24）
>
> 設計方針・CSV 分析は [データベース仕様書](./specification.md) を参照。

## ドキュメント情報

| 項目 | 内容 |
|------|------|
| プロジェクト名 | いおり書房 EC サイト（iorishobo） |
| DBMS | MySQL 8.x |
| 文字コード | utf8mb4（照合順序 `utf8mb4_unicode_ci`。§1.6） |
| バージョン | 0.47（確定） |
| 最終更新日 | 2026-06-25 |

---

## 1. 共通定義

### 1.1 住所カラムセット

カラーミー購入フォームの **address1 / address2** に対応。  
（city / street 等への分割は **行わない**）

| 物理名 | 論理名 | 型 | NULL | チェックアウト | カラーミー対応 |
|--------|--------|-----|------|--------------|--------------|
| postal_code | 郵便番号 | CHAR(7) | NO* | 必須 | 郵便番号 |
| prefecture | 都道府県 | VARCHAR(20) | NO* | 必須 | 都道府県 / 都道府県名 |
| address_line1 | 住所１ | VARCHAR(255) | NO* | 必須 | address1 / 住所 |
| address_line2 | 住所２ | VARCHAR(255) | YES | 任意 | address2 / 住所 |

\* 移行データ import 直後のみ NULL 許容の場合あり。

**移行時**（[仕様書 §3.9](./specification.md#39-カラーミー移行時の住所)）: 郵便番号・都道府県は CSV 列をそのまま取り込む。`住所` 列は `address_line1` に全文を入れ、`address_line2` は NULL（自動分割しない）。

### 1.2 金額

すべて **INT UNSIGNED・円・税込**（内税方式）。

### 1.3 消費税

ショップ全体で固定。**商品ごとの税率列は持たない**（全商品 10%）。

| 項目 | 方針 |
|------|------|
| 表示形式 | **内税**（ページに表示する価格に消費税を含む） |
| 税率 | **10% のみ**（軽減税率 8% の商品なし） |
| 端数処理 | **切り捨て**（円未満を切り捨て） |
| 商品マスタ | 税率・軽減税率フラグのカラムは**設けない** |
| 税率別内訳 | **持たない**（8% / 10% 混在がないため） |

**新規注文の `orders.tax_amount` 算出**（カラーミー「消費税(商品合計に対する)」と同義）:

```
tax_amount = floor(subtotal × 10 / 110)                    … クーポンなし
tax_amount = floor((subtotal - discount) × 10 / 110)       … クーポンあり（§13.1）
```

- 対象は **商品合計（subtotal）のみ**。送料・決済手数料の税額は本列に含めない（移行データと同じ考え方）。
- 移行注文は CSV の値をそのまま入れる（再計算しない）。

**領収書・インボイス**: 全商品 10% のため、「10% 対象（税込）○○円・消費税 ○○円」で表示可能。適格請求書の登録番号は [仕様書 §3.18](./specification.md#318-ショップ固定情報config) の `config/shop.php` から掲載する（DB なし）。

### 1.4 必須/任意（アプリケーション）

| 項目 | ルール |
|------|--------|
| フリガナ | **任意**（配送先フリガナは sales CSV で 75% が空） |
| 電話・携帯 | **どちらか 1 つ必須** |
| address_line2 | **任意** |

### 1.5 氏名

| 項目 | 方針 |
|------|------|
| 形式 | **`name` 1 フィールドのみ**（`last_name` / `first_name` は持たない） |
| 対象 | `customers.name`、`orders.buyer_name` / `shipping_name`、`users.name` |
| フリガナ | `name_kana` / `shipping_name_kana` も 1 フィールド（任意） |

### 1.6 DB 設定

| 項目 | 方針 |
|------|------|
| 文字コード | `utf8mb4` |
| 照合順序 | `utf8mb4_unicode_ci` |
| タイムゾーン | **Asia/Tokyo**（Laravel アプリ設定。`ordered_at` 等は JST で保存・表示） |
| 移行 CSV の日時 | JST としてそのまま取り込む |

---

## 2. テーブル一覧

| No | 物理名 | 論理名 | 種別 |
|----|--------|--------|------|
| 1 | categories | カテゴリ | マスタ |
| 2 | products | 商品（親） | マスタ |
| 3 | product_images | 商品画像 | マスタ |
| 4 | product_variants | 商品バリアント | マスタ |
| 5 | customers | 顧客 | マスタ |
| 6 | shipping_methods | 配送方法 | マスタ |
| 7 | coupons | クーポン | マスタ |
| 8 | carts | カート | トランザクション |
| 9 | cart_items | カート明細 | トランザクション |
| 10 | orders | 注文 | トランザクション |
| 11 | order_items | 注文明細 | トランザクション |
| 12 | refunds | 返金 | トランザクション |
| 13 | users | ユーザー | マスタ（Laravel 標準 + is_admin） |
| 14 | watchlist_entries | 要注意リスト | 運用 |

**作らないテーブル**: `saved_addresses`（複数お届け先の登録・選択は不要。会員は `customers` をチェックアウト初期値に使う。[仕様書 §3.19](./specification.md#319-保存配送先)）

**顧客の考え方（カラーミー型）**: 購入者は会員・非会員を問わず `customers` に保持する。ログインできる会員のみ `users` に登録する。詳細は §8。

---

## 3. リレーション

| 親 | 子 | 外部キー | 削除時 |
|----|-----|---------|--------|
| categories | categories | parent_id | SET NULL |
| categories | products | category_id | SET NULL |
| products | product_images | product_id | CASCADE |
| products | product_variants | product_id | CASCADE |
| users | carts | user_id | CASCADE |
| users | customers | user_id | SET NULL |
| customers | orders | customer_id | SET NULL |
| users | orders | user_id | SET NULL |
| carts | cart_items | cart_id | CASCADE |
| product_variants | cart_items | product_variant_id | CASCADE |
| shipping_methods | orders | shipping_method_id | SET NULL |
| coupons | carts | coupon_id | SET NULL |
| coupons | orders | coupon_id | SET NULL |
| orders | order_items | order_id | CASCADE |
| orders | refunds | order_id | CASCADE |
| users | refunds | recorded_by | SET NULL |
| product_variants | order_items | product_variant_id | SET NULL |
| customers | watchlist_entries | customer_id | SET NULL |
| orders | watchlist_entries | source_order_id | SET NULL |
| users | watchlist_entries | created_by | SET NULL |
| users | watchlist_entries | deactivated_by | SET NULL |

---

## 4. categories（カテゴリ）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | parent_id | 親カテゴリ ID | BIGINT UNSIGNED | YES | FK | NULL = 大カテゴリ |
| 3 | name | カテゴリ名 | VARCHAR(255) | NO | | |
| 4 | slug | スラッグ | VARCHAR(255) | NO | UK | 数字 ID ベース。`categories.id` の文字列（§3.21） |
| 5 | sort_order | 表示順 | INT UNSIGNED | NO | | デフォルト 0 |
| 6 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 7 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 5. products（商品・親）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | colorme_product_id | カラーミー商品 ID | BIGINT UNSIGNED | YES | UK | 新規商品は NULL |
| 3 | category_id | カテゴリ ID | BIGINT UNSIGNED | YES | FK | |
| 4 | name | 商品名 | VARCHAR(255) | NO | | |
| 5 | slug | スラッグ | VARCHAR(255) | NO | UK | 数字 ID ベース。移行=商品 ID、新規=`products.id`（§3.21） |
| 6 | short_description | 簡易説明 | TEXT | YES | | |
| 7 | description | 商品説明 | LONGTEXT | YES | | HTML 可 |
| 8 | base_price | 基本価格 | INT UNSIGNED | NO | | 税込。オプションあり商品は表示用 |
| 9 | stock_managed | 在庫管理 | BOOLEAN | NO | | true = 在庫管理する。§5 在庫・§7 参照 |
| 10 | is_published | 掲載 | BOOLEAN | NO | | true = 掲載する。会員限定は使わない（§5 掲載設定） |
| 11 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 12 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 13 | updated_at | 更新日時 | TIMESTAMP | YES | | |

**型番（SKU）**: カラーミー `product.csv` に列はあるが **本ショップでは未使用のため DB に持たない**。移行時もスキップする。

**定価**: カラーミー「定価」列は **使わない**。表示・保存するのは **販売価格のみ**（`base_price` および `product_variants.price`）。移行時もスキップする。

**ISBN/JAN**: カラーミー「JAN/ISBN (GTIN)」列は **未入力のため DB に持たない**。移行時もスキップする。

**重量**: カラーミー「重量」列は **使わない**。送料は全国一律のため DB に持たない。移行時もスキップする。

**在庫管理**: 商品ごとにオン/オフ。**一部の商品のみ** `stock_managed = true`（カラーミー実態どおり）。詳細は §7 と [仕様書 §3.7.6](./specification.md#376-在庫管理)。

**販売期間**: カラーミー「販売開始/終了日付・時間」列は **使わない**。掲載中（`is_published`）なら常時購入可。移行時もスキップする。

**掲載設定**: カラーミーは 4 状態あるが、本ショップは **掲載する / 掲載しない** のみ（`is_published`）。会員のみ掲載・会員のみ購入は **使わない**。

**SEO**: カラーミー「タイトル・キーワード・ページ概要」列は **使わない**。DB に meta 列は持たない。ページ title 等はアプリ側で商品名から自動生成する。

**購入数量制限**: カラーミー「最小/最大購入数量」列は **使わない**。最小 1・上限なし（在庫上限は `stock_managed` 時のみ）。移行時もスキップする。

**ギフト**: カラーミー「ギフト設定の無効化」列は **使わない**。移行時もスキップする。

---

## 6. product_images（商品画像）

カラーミー `product.csv` の「商品画像 URL」「その他画像 1〜9 URL」に対応。  
**sort_order = 0 がメイン画像**（一覧・OGP 等はここを参照）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | product_id | 商品 ID | BIGINT UNSIGNED | NO | FK | |
| 3 | path | 画像パス | VARCHAR(500) | NO | | 移行時は自サーバー（`storage/`）へダウンロードしたローカルパス |
| 4 | sort_order | 表示順 | INT UNSIGNED | NO | | 0 = メイン |
| 5 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 6 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 7. product_variants（商品バリアント）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | product_id | 商品 ID | BIGINT UNSIGNED | NO | FK | |
| 3 | colorme_option_id | カラーミーオプション ID | BIGINT UNSIGNED | YES | UK | |
| 4 | name | 表示名 | VARCHAR(255) | NO | | |
| 5 | attributes | オプション属性 | JSON | YES | | 軸名をキーにした JSON（例: `{"学年":"１年生"}`） |
| 6 | price | 販売価格 | INT UNSIGNED | NO | | 税込 |
| 7 | stock | 在庫数 | INT UNSIGNED | NO | | デフォルト 0。親が `stock_managed = false` のときは参照しない |
| 8 | is_active | 有効 | BOOLEAN | NO | | |
| 9 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 10 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 11 | updated_at | 更新日時 | TIMESTAMP | YES | | |

オプション CSV の「型番」列も **取り込まない**（§5 型番方針と同じ）。

**在庫数の移行**

| 条件 | `stock` の値 |
|------|-------------|
| 親が `stock_managed = true` かつオプションあり | オプション CSV の「在庫数」 |
| 親が `stock_managed = true` かつオプションなし | product.csv の「在庫数」 |
| 親が `stock_managed = false` | `0`（チェックアウトでは無視） |

**オプションなし商品**（単品のみ）: `product_variants` に **1 行だけ**登録する。`name` は **親商品の `products.name` と同じ**（移行・新規とも）。

**アプリ側のルール**（`stock_managed = true` のときのみ）:

- カート追加・チェックアウト時: `stock >= 数量` を満たさなければ購入不可
- カート表示時: 明細ごとに在庫を再チェックし、`quantity > stock` なら **警告表示**し、**チェックアウトをブロック**（在庫確保はしない）
- `stock = 0`: 売り切れ表示（購入ボタン無効）
- **在庫減算のタイミング**（決済方法により異なる。§13.4）:

| 決済方法 | 在庫減算 |
|----------|----------|
| `stripe` | `payment_status = paid` 時（Webhook 後） |
| `bank_transfer` | `payment_status = paid` 時（入金確認後） |
| `cod` | チェックアウト送信時（`pending` のまま。未入金でも発送するため） |

- 未発送キャンセル時: 減算済みの明細のみ在庫を戻す
- 在庫の予約（カート保持中の確保）は **行わない**（同時購入でオーバーセルしうる。小規模ショップ想定）

---

## 8. customers（顧客）

カラーミーと同様、**購入者（会員・非会員）を顧客マスタとして保持**する。ログインできる会員のみ `users` と紐付く。FAX は持たない（実データ常に空）。

### 8.1 会員と非会員顧客

| 種別 | `user_id` | ログイン | 判定 |
|------|-----------|----------|------|
| **会員顧客** | あり | 可 | `users` と 1:1 |
| **非会員顧客** | NULL | 不可 | ゲスト購入者・移行の「ユーザー登録=無」等 |

会員かどうかは **専用フラグを持たず** `user_id IS NOT NULL` で判別する（カラーミー「ユーザー登録」列に相当）。

### 8.2 カラム

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | colorme_customer_id | カラーミー顧客 ID | BIGINT UNSIGNED | YES | UK | 移行顧客のみ。新規ゲスト・会員は NULL |
| 3 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK, UK | 会員のみセット。非会員は NULL |
| 4 | name | 氏名 | VARCHAR(100) | NO | | |
| 5 | name_kana | フリガナ | VARCHAR(100) | YES | | **任意** |
| 6 | email | メール | VARCHAR(255) | YES | IDX | find or create 用。UK は付けない（空欄・重複があり得る） |
| 7 | postal_code | 郵便番号 | CHAR(7) | YES | | §1.1 |
| 8 | prefecture | 都道府県 | VARCHAR(20) | YES | | §1.1 |
| 9 | address_line1 | 住所 | VARCHAR(255) | YES | | §1.1 |
| 10 | address_line2 | 建物名 | VARCHAR(255) | YES | | §1.1 |
| 11 | phone | 電話番号 | VARCHAR(20) | YES | | |
| 12 | mobile | 携帯番号 | VARCHAR(20) | YES | | |
| 13 | note | 備考 | TEXT | YES | | 社内メモ |
| 14 | registered_at | 登録日時 | TIMESTAMP | YES | | 移行用（カラーミー顧客登録日） |
| 15 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 16 | updated_at | 更新日時 | TIMESTAMP | YES | | |

### 8.3 移行（customer.csv）

[仕様書 §3.20](./specification.md#320-顧客会員の移行) に従う。**「ユーザー登録=無」も `customers` に移行**する。

| ユーザー登録 | メール | `customers` | `users` | `customers.user_id` |
|--------------|--------|-------------|---------|---------------------|
| **有** | あり | 移行 | 作成（ランダムハッシュ。初回は再設定） | 紐付ける |
| **有** | なし | 移行 | 作らない | NULL |
| **無** | — | **移行** | 作らない | NULL |

**移行時の異常行**: 必須列が空の行は **スキップ**し、移行ログに記録する（[仕様書 §3.20](./specification.md#320-顧客会員カラーミー型)）。

### 8.4 新規サイトのチェックアウト

| 操作 | ルール |
|------|--------|
| **ゲスト購入** | `buyer_email` で既存顧客を検索（正規化して照合）。なければ `customers` を新規作成（`user_id = NULL`）。`orders.customer_id` をセット。`orders.user_id = NULL` |
| **会員購入**（ログイン） | 紐付く `customers` を `orders.customer_id` にセット。`orders.user_id` もセット |
| **チェックアウト初期表示**（会員） | `customers` の氏名・住所・電話・メール等を購入者欄に表示（編集可） |
| **注文確定時** | フォーム内容を `orders.buyer_*` にスナップショット。**`customers` は自動更新しない** |
| **後から会員登録** | 同じメールの既存 `customers` に `user_id` を付ける。**過去注文の `orders.user_id` はメール一致だけでは更新しない**（マイページに過去ゲスト注文を出さない） |
| **マイページでメール変更** | `users.email` と `customers.email` を **両方同期更新** |

**ゲスト顧客の find or create**: `email` を正規化（前後空白除去・小文字化）して検索。見つかれば再利用、なければ新規行を作成。

---

## 9. shipping_methods（配送方法）

全国一律の送料。**地域別・重量別の料金表は持たない**（クリックポスト / ゆうパック等は方法ごとに 1 レコード）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | name | 名称 | VARCHAR(255) | NO | | 例: クリックポスト |
| 3 | slug | スラッグ | VARCHAR(50) | NO | UK | |
| 4 | base_fee | 基本送料 | INT UNSIGNED | NO | | 円・全国一律 |
| 5 | free_shipping_threshold | 送料無料ライン | INT UNSIGNED | YES | | クーポン適用後の商品合計（`subtotal - discount`）がこの金額以上で送料 0 円。NULL = なし |
| 6 | is_active | 有効 | BOOLEAN | NO | | |
| 7 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 8 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 9 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 10. coupons（クーポン）

新規サイト専用。カラーミーからクーポン定義の CSV 移行は **ない**（過去注文の割引額は `orders.discount` / `discount_name` に保存）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | code | クーポンコード | VARCHAR(50) | NO | UK | チェックアウトで入力するコード |
| 3 | name | 表示名 | VARCHAR(255) | NO | | 注文の `discount_name` にコピー |
| 4 | discount_amount | 割引額 | INT UNSIGNED | NO | | 円・定額のみ（率割引は使わない） |
| 5 | min_order_amount | 最低注文金額 | INT UNSIGNED | YES | | 商品合計（税込）の下限。NULL=制限なし |
| 6 | starts_at | 開始日時 | TIMESTAMP | YES | | NULL=開始制限なし |
| 7 | ends_at | 終了日時 | TIMESTAMP | YES | | NULL=終了制限なし |
| 8 | max_uses | 利用上限回数 | INT UNSIGNED | YES | | **全ユーザー合計**。NULL=無制限。1 人 1 回制限は設けない |
| 9 | used_count | 利用回数 | INT UNSIGNED | NO | | 加算タイミングは在庫減算と同じ（§7・§13.4） |
| 10 | is_active | 有効 | BOOLEAN | NO | | |
| 11 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 12 | updated_at | 更新日時 | TIMESTAMP | YES | | |

**割引額**（チェックアウト時）: `orders.discount = min(discount_amount, subtotal)`（商品合計を超えない）。

**`used_count` 加算**: 在庫減算と同タイミング（`stripe`・`bank_transfer` は `paid` 時、`cod` はチェックアウト送信時）。チェックアウト送信時に `orders.discount` 等のスナップショットは保存する。

**適用ルール**: 1 注文につき **1 クーポンのみ**。有効期間内・`is_active`・`used_count < max_uses`（設定時）・`subtotal >= min_order_amount`（設定時）を満たすこと。

---

## 11. carts（カート）

チェックアウト前の買い物かご。**カラーミー CSV の移行対象外**（新規サイト専用）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK, UK | ログイン時。ゲストは NULL |
| 3 | session_id | セッション ID | VARCHAR(255) | YES | UK | ゲスト時。Laravel セッション ID |
| 4 | coupon_id | クーポン ID | BIGINT UNSIGNED | YES | FK | 適用中のクーポン。未適用は NULL |
| 5 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 6 | updated_at | 更新日時 | TIMESTAMP | YES | | |

- `user_id` と `session_id` の **どちらか一方** でカートを特定する（アプリ側で保証）。ログイン時は `user_id` をセットし **`session_id = NULL`**
- **ログイン時マージ**（ゲストカート → 会員カート）:
  - 同一 `product_variant_id` は **数量を合算**（UK `(cart_id, product_variant_id)`）
  - ゲストのみの明細は会員カートへ移動
  - `coupon_id`: 会員カートに既にあればそれを優先。なければゲストの `coupon_id` を引き継ぐ
  - マージ後: ゲストカート行を削除
- **会員カートの掃除**: **行わない**（期限なし）
- **ゲストカートの掃除**: `user_id IS NULL` かつ `updated_at` から **90 日以上**経過した行を定期削除（Laravel スケジュール）

---

## 12. cart_items（カート明細）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | cart_id | カート ID | BIGINT UNSIGNED | NO | FK | |
| 3 | product_variant_id | バリアント ID | BIGINT UNSIGNED | NO | FK | |
| 4 | quantity | 数量 | INT UNSIGNED | NO | | |
| 5 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 6 | updated_at | 更新日時 | TIMESTAMP | YES | | |

- UK: `(cart_id, product_variant_id)` … 同一バリアントは 1 行、数量で管理。
- 単価は **持たない**。チェックアウト時に `product_variants.price` を参照する。

---

## 13. orders（注文）

sales_all.csv 相当。**購入者（buyer_*）と配送先（shipping_*）を両方保持**。

### 13.1 注文共通

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | colorme_sales_id | カラーミー売上 ID | BIGINT UNSIGNED | YES | UK | |
| 3 | customer_id | 顧客 ID | BIGINT UNSIGNED | YES | FK | 移行: §8.3・§13.6。新規: **ゲスト・会員とも必ずセット**（§8.4） |
| 4 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK | 移行: **常に NULL**。新規: **ログイン購入時のみ**。マイページはこの列で絞る（§13.6） |
| 5 | order_number | 注文番号 | VARCHAR(50) | NO | UK | §13.5 参照。お客様表示・問い合わせ用 |
| 6 | ordered_at | 受注日時 | TIMESTAMP | NO | | |
| 7 | device | デバイス | VARCHAR(50) | YES | | 新規: User-Agent から PC / モバイル等を判定。移行: sales CSV 列 2 |
| 8 | subtotal | 商品合計 | INT UNSIGNED | NO | | 税込 |
| 9 | tax_amount | 消費税 | INT UNSIGNED | NO | | 商品合計に対する税額。§1.3 参照 |
| 10 | shipping_fee | 送料 | INT UNSIGNED | NO | | |
| 11 | payment_fee | 決済手数料 | INT UNSIGNED | NO | | 代引き時のみ（§3.12）。Stripe・振込は 0。移行は CSV のまま |
| 12 | discount | 割引金額 | INT UNSIGNED | NO | | クーポン適用額。未使用は 0 |
| 13 | discount_name | 割引名称 | VARCHAR(255) | YES | | クーポン表示名のスナップショット |
| 14 | coupon_id | クーポン ID | BIGINT UNSIGNED | YES | FK | 使用クーポン。削除後は NULL |
| 15 | coupon_code | クーポンコード | VARCHAR(50) | YES | | 受注時のコードスナップショット |
| 16 | point_discount | ポイント割引 | INT UNSIGNED | NO | | ショップポイント分。未使用は 0 |
| 17 | external_point_discount | 外部ポイント割引 | INT UNSIGNED | NO | | GMO ポイント等。未使用は 0 |
| 18 | total | 総合計 | INT UNSIGNED | NO | | 税込 |
| 19 | payment_method | 決済方法 | VARCHAR(30) | NO | | §13.4 参照 |
| 20 | payment_status | 入金状態 | VARCHAR(30) | NO | | §13.4 参照 |
| 21 | shipping_status | 発送状態 | VARCHAR(30) | NO | | §13.4 参照 |
| 22 | shipped_at | 発送日時 | TIMESTAMP | YES | | 発送済にした日時 |
| 23 | tracking_number | 追跡番号 | VARCHAR(50) | YES | | 発送後に管理画面で入力（任意） |
| 24 | shipping_method_id | 配送方法 ID | BIGINT UNSIGNED | YES | FK | **新規注文**で `shipping_methods` をセット。**移行注文は NULL**（`shipping_method_name` のみ） |
| 25 | shipping_method_name | 配送方法名 | VARCHAR(255) | YES | | 受注時の名称スナップショット |
| 26 | customer_note | 注文備考 | TEXT | YES | | sales 列 33 |
| 27 | shipping_note | 配送先備考 | TEXT | YES | | sales 列 54 |
| 28 | stripe_payment_intent_id | Stripe PI ID | VARCHAR(255) | YES | UK | `payment_method = stripe` のときのみ。1 PI = 1 注文（§13.4） |
| 29 | cancelled_at | キャンセル日時 | TIMESTAMP | YES | | 管理画面でキャンセルした日時 |
| 30 | cancel_reason | キャンセル理由 | TEXT | YES | | 任意 |
| 31 | refund_amount | 返金合計 | INT UNSIGNED | NO | | 返金の累計。未返金は 0 |
| 32 | refunded_at | 最終返金日時 | TIMESTAMP | YES | | 直近の返金記録日時 |
| 33 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 34 | updated_at | 更新日時 | TIMESTAMP | YES | | |

**金額の関係**（新規注文）:

```
total = subtotal + shipping_fee + payment_fee - discount - point_discount - external_point_discount
tax_amount = floor((subtotal - discount) × 10 / 110)   … クーポン適用後の商品合計から消費税を算出
```

クーポン未使用時は `discount = 0`、`coupon_id` / `coupon_code` / `discount_name` は NULL。移行注文は CSV の値をそのまま保存（税額は再計算しない）。

受注後は `subtotal` / `total` / 明細は **書き換えない**。減額は `refunds`、増額は別途請求 or 新規注文（[仕様書 §3.17](./specification.md#317-キャンセル返金注文後の金額変更)）。

**ギフト（のし・ラッピング等）**: 本ショップでは **使わない**。sales_all の熨斗・メッセージカード・ラッピング関連列は移行時 **スキップ**（[仕様書 §3.16](./specification.md#316-ギフトのしラッピング等)）。

### 13.2 購入者（buyer_*）— sales_all「購入者」列

| No | 物理名 | 論理名 | 型 | NULL | CSV 列 |
|----|--------|--------|-----|------|--------|
| 35 | buyer_name | 購入者氏名 | VARCHAR(100) | NO | 購入者 名前 |
| 36 | buyer_email | 購入者メール | VARCHAR(255) | NO | 購入者 メールアドレス |
| 37 | buyer_phone | 購入者電話 | VARCHAR(20) | YES | 購入者 電話番号 |
| 38 | buyer_mobile | 購入者携帯 | VARCHAR(20) | YES | 購入者 携帯番号 |
| 39 | buyer_postal_code | 購入者郵便番号 | CHAR(7) | NO | 購入者 郵便番号 |
| 40 | buyer_prefecture | 購入者都道府県 | VARCHAR(20) | NO | 購入者 都道府県 |
| 41 | buyer_address_line1 | 購入者住所 | VARCHAR(255) | NO | 購入者 住所 |
| 42 | buyer_address_line2 | 購入者建物名 | VARCHAR(255) | YES | 移行時は NULL。新規注文のみ |

※ 購入者フリガナ列は **sales_all.csv に存在しない** ため持たない。

**`buyer_email` の用途**: その注文の購入者連絡先スナップショット。注文確認メール・領収書の宛先は **常に `orders.buyer_email`**（会員・ゲスト共通）。ログイン用の `users.email` やプロフィールの `customers.email` とは別。

### 13.3 配送先（shipping_*）— sales_all「配送先」列

| No | 物理名 | 論理名 | 型 | NULL | CSV 列 |
|----|--------|--------|-----|------|--------|
| 43 | shipping_name | 配送先氏名 | VARCHAR(100) | NO | 配送先 名前 |
| 44 | shipping_name_kana | 配送先フリガナ | VARCHAR(100) | YES | 配送先 フリガナ・**任意** |
| 45 | shipping_phone | 配送先電話 | VARCHAR(20) | NO | 配送先 電話番号 |
| 46 | shipping_postal_code | 配送先郵便番号 | CHAR(7) | NO | 配送先 郵便番号 |
| 47 | shipping_prefecture | 配送先都道府県 | VARCHAR(20) | NO | 配送先 都道府県名 |
| 48 | shipping_address_line1 | 配送先住所 | VARCHAR(255) | NO | 配送先 住所 |
| 49 | shipping_address_line2 | 配送先建物名 | VARCHAR(255) | YES | 移行時は NULL。新規注文のみ |

### 13.4 決済方法・ステータス

**決済方法（`payment_method`）— 新規注文**

| 値 | 表示名 | 入金の扱い |
|----|--------|-----------|
| `stripe` | クレジットカード（Stripe） | **自動**：決済成功で `payment_status = paid` |
| `cod` | 代金引換 | **手動**：管理画面で入金確認 |
| `bank_transfer` | 銀行振込 | **手動**：振込確認後に管理画面で入金済に。**入金済になるまで発送しない** |

**移行専用（新規チェックアウトでは選択不可）**

| 値 | 表示名 | 備考 |
|----|--------|------|
| `amazon_pay` | Amazon Pay | 過去注文の移行のみ。[仕様書 §3.12](./specification.md#312-決済入金発送ステータス) |

**移行（sales_all.csv）**: 上記 4 値にマッピング（[仕様書 §3.12](./specification.md#312-決済入金発送ステータス)）。マッピングできない決済方法はスキップ。

**Stripe（`stripe_payment_intent_id`）**

| 項目 | 方針 |
|------|------|
| 保存先 | `orders.stripe_payment_intent_id`（**UK**。別テーブルは作らない） |
| いつセット | チェックアウト時に PaymentIntent 作成後、Stripe が返した `pi_...` をその注文行に保存 |
| 1 PI = 1 注文 | 同じ PI を複数注文に付けられない（UK）。チェックアウトは **1 送信 = 1 注文** をアプリ側で保証 |
| Webhook | `payment_intent.succeeded` で該当注文を `paid` に。再送時は冪等（既に `paid` なら何もしない） |
| 非 Stripe | 代引き・振込・移行注文は NULL（UK の対象外） |

**チェックアウト送信（①）と Stripe 決済（②）**

| 段階 | 操作 | `orders` | `payment_status` |
|------|------|----------|------------------|
| ① | 自社サイトの「注文する」押下 | **作成** | `pending` |
| ② | Stripe でカード決済成功（Webhook） | 更新 | **`paid`** |

チェックアウト画面を**開いただけ**では `orders` は作らない。

**Stripe 未完了注文**（① 後に② せず離脱）: 管理・マイページ一覧からは除外。**作成（`ordered_at`）から 7 日経過で自動キャンセル**（`payment_status` / `shipping_status` → `cancelled`）。在庫は減算前のため戻し不要。必要なら管理画面で手動キャンセル（§13.7）も可。

**銀行振込の案内**: DB に振込期限列は持たない。注文完了画面・メールに **「7 日以内にお振込みください」** と案内する（自動キャンセルはしない）。

**在庫減算・クーポン `used_count` 加算**（`stock_managed` / クーポン使用時）:

| 決済方法 | タイミング |
|----------|------------|
| `stripe` | `payment_status = paid`（Webhook 後） |
| `bank_transfer` | `payment_status = paid`（入金確認後） |
| `cod` | チェックアウト送信時（①・`pending`） |

**発送の前提（新規注文）**

| 決済方法 | 発送してよい条件 |
|---------|----------------|
| `stripe` | `payment_status = paid`（決済成功後） |
| `bank_transfer` | `payment_status = paid`（**振込確認後**。未入金のまま発送しない） |
| `cod` | `payment_status = pending` でも可（代金は配達時回収） |

**入金状態（`payment_status`）**

| 値 | 意味 |
|----|------|
| `pending` | 未入金 |
| `paid` | 入金済（一部返金済みの場合もこのまま。`refund_amount` を参照） |
| `refunded` | 全額返金済（`refund_amount >= total`） |
| `cancelled` | キャンセル（未入金で取り消し等） |

**発送状態（`shipping_status`）**

| 値 | 意味 | 備考 |
|----|------|------|
| `unshipped` | 未発送 | 受注直後の初期値 |
| `partially_shipped` | 一部発送 | 分納中。発送メールは送らない。キャンセル不可 |
| `shipped` | 発送済 | `shipped_at` をセット。追跡番号は任意で `tracking_number` に保存 |
| `cancelled` | キャンセル | |

**運用フロー**

1. 受注 → `shipping_status = unshipped`。入金は決済方法により初期値が異なる（[仕様書 §3.12](./specification.md#312-決済入金発送ステータス)）。
2. 管理画面から B2・ゆうパック等の送り状 CSV を出力（**アプリ機能・将来実装**）。住所は `shipping_address_line1` と `shipping_address_line2` を結合（line2 が NULL なら line1 のみ）。
3. 分納する場合は管理画面で **一部発送** に更新（メールなし）。全品発送後に **発送済** に更新（`shipped_at`、必要なら `tracking_number`、発送メール送信）。
4. 代引き・銀行振込は、入金確認後に管理画面で **入金済** に更新。

### 13.5 注文番号（`order_number`）

お客様への表示・メール・問い合わせ・管理画面検索に使う。**数字のみ**（英字は使わない）。

#### 採番ルール

| 種別 | 採番 | 例 |
|------|------|-----|
| **移行注文** | sales_all.csv の **売上 ID** を文字列化してそのまま使う | `12345678` |
| **新規注文** | **0〜9 のランダム 10 桁**（先頭 0 あり。例: `0492817536`） | `8392017465` |

移行注文は `colorme_sales_id` と同じ数値。カラーミー時代の注文番号をそのまま残せる。

#### なぜ連番にしないか

`10001` → `10002` のように **1 ずつ増える番号は、次の番号が予想しやすい**。注文番号だけで他人の注文を推測されるリスクを下げるため、新規分は **ランダムな数字** にする。

#### 新規注文の採番手順（アプリ側）

```
1. 乱数で 10 桁の数字文字列を生成（例: PHP random_int + ゼロ埋め、または CSPRNG）
2. orders.order_number に同じ値が無いか確認（UK）
3. 重複していれば 2 をやり直す（最大 10 回程度）
4. 注文レコード保存時に order_number を確定
```

移行済みの売上 ID と偶然一致した場合も 2 で弾かれる。10 桁ランダムなら衝突確率は極めて低い。

#### 移行分と新規分の見分け方

番号の桁数・形式では区別しない（どちらも数字のみ）。**`colorme_sales_id` が NULL かどうか**で移行／新規を判別する。

#### 使わない方式

- 連番（`1`, `2`, `3` …）
- 日付 + 連番（`20260622-001` …）
- 英字を含む番号

移行注文は [仕様書 §3.12](./specification.md#312-決済入金発送ステータス) に従いマッピングする。`customer_id` / `user_id` は §13.6。キャンセル・返金列は移行時は未使用なら 0 / NULL。

### 13.6 顧客・会員の紐付け（`customer_id` / `user_id`）

| 列 | 役割 |
|----|------|
| `customer_id` | **顧客マスタへの参照**（管理画面で顧客単位の注文履歴）。ゲスト・会員ともセット |
| `user_id` | **マイページ用**（ログインアカウントへの参照）。ログイン購入時のみセット |

#### 移行（sales_all.csv）

| 列 | 方針 |
|----|------|
| `customer_id` | `購入者 顧客ID` が移行済み `customers.colorme_customer_id` と一致するときセット（会員・非会員顧客とも） |
| `user_id` | **常に NULL**（ログイン状態は CSV から確定できない） |
| `shipping_method_id` | **常に NULL**（配送方法名は `shipping_method_name` スナップショットのみ） |
| `buyer_*` | 常にスナップショットとして移行 |

#### 新規サイト

| 購入形態 | `customer_id` | `user_id` | マイページ |
|----------|---------------|-----------|------------|
| ゲスト | find or create した顧客 | NULL | 表示しない |
| 会員（ログイン） | 紐付く顧客 | ログインユーザー | 表示する |

**メール一致だけで過去注文の `user_id` を更新しない**（家族共有メール・ゲスト購入のプライバシー）。

#### 管理画面

全注文を `customer_id` / `buyer_*` / 注文番号で検索。顧客詳細から `customer_id` 経由で注文一覧を表示できる。

### 13.7 キャンセル・返金

**発送済み（`shipping_status = shipped`）および一部発送（`partially_shipped`）の注文はキャンセル不可**。返金のみ（`refunds`、一部返金可）。

| 操作 | 条件 | 更新する列 | 備考 |
|------|------|-----------|------|
| 注文キャンセル | 未発送（`unshipped`）・未入金（`pending`） | `cancelled_at`, `cancel_reason`, `payment_status` → `cancelled`, `shipping_status` → `cancelled` | 在庫減算済みなら戻す（代引きは①で減算済みのため） |
| 注文キャンセル | 未発送・入金済（`paid`） | `cancelled_at`, `cancel_reason`, `shipping_status` → `cancelled` | `payment_status` は `paid` のまま。返金で `refunded` |
| 返金記録 | — | `refunds` に 1 行追加、`orders.refund_amount` 加算、`refunded_at` 更新 | 詳細は §14 |

全額返金後は `payment_status = refunded`。一部返金は `paid` のまま。`orders.total` は変更しない。

---

## 14. refunds（返金）

管理画面からの返金記録。**1 注文に複数行**（一部返金の履歴）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | order_id | 注文 ID | BIGINT UNSIGNED | NO | FK | |
| 3 | amount | 返金額 | INT UNSIGNED | NO | | 円 |
| 4 | reason | 理由 | TEXT | YES | | |
| 5 | stripe_refund_id | Stripe Refund ID | VARCHAR(255) | YES | | Stripe 返金時のみ |
| 6 | recorded_by | 記録者 | BIGINT UNSIGNED | YES | FK | `users.id`（管理者） |
| 7 | created_at | 記録日時 | TIMESTAMP | YES | | |

**フロー**

1. 管理者が管理画面で返金額・理由を入力
2. `payment_method = stripe` のときは **Stripe Refund API を試行** → 成功時 `stripe_refund_id` を保存
3. Stripe 失敗時・代引き・振込は **手動返金**（`stripe_refund_id` = NULL）。振込後に同じ `refunds` へ記録
4. `orders.refund_amount` += `amount`、`orders.refunded_at` = 今回の `created_at`
5. `refund_amount >= orders.total` なら `payment_status = refunded`

増額用のテーブルは持たない（[仕様書 §3.17](./specification.md#317-キャンセル返金注文後の金額変更)）。

---

## 15. order_items（注文明細）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | order_id | 注文 ID | BIGINT UNSIGNED | NO | FK | |
| 3 | colorme_sales_detail_id | カラーミー明細 ID | BIGINT UNSIGNED | YES | UK | |
| 4 | product_variant_id | バリアント ID | BIGINT UNSIGNED | YES | FK | |
| 5 | product_name | 商品名 | VARCHAR(255) | NO | | スナップショット |
| 6 | variant_label | 選択内容 | VARCHAR(255) | YES | | 例: 学年：３年生 |
| 7 | unit_price | 単価 | INT UNSIGNED | NO | | 税込 |
| 8 | quantity | 数量 | INT UNSIGNED | NO | | |
| 9 | subtotal | 小計 | INT UNSIGNED | NO | | 税込 |
| 10 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 11 | updated_at | 更新日時 | TIMESTAMP | YES | | |

`sales_all.csv` の「購入商品 型番」列も **取り込まない**。

---

## 16. users（ユーザー）

Laravel 12 標準を拡張。ログイン認証。**住所・電話は `customers` に保持**。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | name | 氏名 | VARCHAR(255) | NO | | |
| 3 | email | メール | VARCHAR(255) | NO | UK | |
| 4 | email_verified_at | 確認日時 | TIMESTAMP | YES | | 新規会員登録は認証必須。移行会員は移行時にセット（スキップ扱い） |
| 5 | password | パスワード | VARCHAR(255) | NO | | 移行会員はランダムハッシュ（§8.3） |
| 6 | is_admin | 管理者 | BOOLEAN | NO | | `true` = 管理画面にアクセス可。デフォルト `false` |
| 7 | remember_token | トークン | VARCHAR(100) | YES | | |
| 8 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 9 | updated_at | 更新日時 | TIMESTAMP | YES | | |

**管理者（方式 A）**: 会員と同一の `users` テーブルでログインし、`is_admin = true` のユーザーのみ管理画面へ入る。詳細は [仕様書 §3.15](./specification.md#315-管理者認証)。

- 一般会員は `is_admin = false`
- 管理者の追加は DB または将来の管理画面で `is_admin` を `true` にする（初回はシーダーで 1 件作成想定）
- 管理者も同じアカウントで会員購入は **可能**（`customers` 紐付けは任意）

**メール認証**: **新規会員登録のみ必須**（認証完了までログイン不可）。**移行会員**は `email_verified_at` を移行時にセットし認証フローをスキップ。

**マイページでメール変更**したときは、紐付く `customers.email` も **同期更新**（§8.4）。

---

## 17. watchlist_entries（要注意リスト）

過去にトラブルがあった購入者を、管理者が手動登録するリスト。**新規注文を管理画面で開いたときに警告表示**する（フロント・チェックアウトでは表示しない）。購入のブロックは行わない。

`customers.note` は社内メモ全般。本テーブルの `reason` は **注文画面に出す警告文** 用とする。

### 17.1 カラム

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | customer_id | 顧客 ID | BIGINT UNSIGNED | YES | FK | 顧客が特定できるとき。ゲスト顧客も可（§8） |
| 3 | email | メール | VARCHAR(255) | YES | | 照合用。正規化済みで保存（§17.2） |
| 4 | phone | 電話番号 | VARCHAR(20) | YES | | 照合用。数字のみで保存（§17.2） |
| 5 | reason | 理由 | TEXT | NO | | 管理画面の警告バナーに表示 |
| 6 | is_active | 有効 | BOOLEAN | NO | | `true` = 照合対象。デフォルト `true` |
| 7 | source_order_id | 起因注文 ID | BIGINT UNSIGNED | YES | FK | 登録元の注文（任意） |
| 8 | created_by | 登録者 | BIGINT UNSIGNED | YES | FK | `users.id`（管理者） |
| 9 | created_at | 登録日時 | TIMESTAMP | YES | | |
| 10 | deactivated_at | 解除日時 | TIMESTAMP | YES | | `is_active = false` にした日時 |
| 11 | deactivated_by | 解除者 | BIGINT UNSIGNED | YES | FK | `users.id`（管理者） |

- `customer_id` / `email` / `phone` の **いずれか 1 つ以上** をセットする（アプリ側で保証）。
- 解除は行を削除せず `is_active = false` とする（履歴を残す）。再登録は新規行を追加する。

### 17.2 照合キー

新規注文 `orders` を管理画面で表示するとき、**`is_active = true` の行** のうち次の **いずれか** に一致すれば警告する。

| 照合元（watchlist） | 照合先（orders） | 備考 |
|--------------------|-----------------|------|
| `customer_id` | `orders.customer_id` | 会員。連絡先変更後もヒット |
| `email` | `buyer_email` | 会員・ゲスト共通 |
| `phone` | `buyer_phone` または `buyer_mobile` | 会員・ゲスト共通 |

**正規化**（登録時・照合時の双方で同じ処理）:

| 項目 | ルール |
|------|--------|
| email | 前後空白除去 + 小文字化 |
| phone | 数字以外を除去（ハイフン・括弧等を除去） |

複数行ヒットした場合は **すべての `reason` を表示** する（重複登録は運用で避ける）。

### 17.3 登録・解除（管理画面）

| 操作 | 入力・更新 |
|------|-----------|
| 注文詳細から登録 | `source_order_id` = 当該注文。`customer_id` = `orders.customer_id`（あれば）。`email` / `phone` は `buyer_email` / `buyer_phone` / `buyer_mobile` からコピー（正規化して保存） |
| 顧客詳細から登録 | `customer_id` = 当該顧客。`email` / `phone` は `customers.email` / `phone` / `mobile` からコピー |
| 解除 | `is_active = false`、`deactivated_at` / `deactivated_by` をセット |

キャンセル・返金からの **自動登録は行わない**（管理者が手動で登録する）。

### 17.4 移行

カラーミー CSV に要注意フラグ相当の列はないため、**移行時の一括投入は行わない**。運用開始後に管理画面から登録する。

---

## 18. 改訂履歴

| バージョン | 日付 | 内容 |
|-----------|------|------|
| 0.1 | 2026-06-21 | 初版草案 |
| 0.2 | 2026-06-21 | CSV 実データ反映 |
| 0.3 | 2026-06-21 | 住所を address_line1/2 に修正。customer_addresses 削除。buyer_* に統一 |
| 0.4 | 2026-06-22 | product_images 追加。shipping_methods に送料無料ライン追加。送料は全国一律 |
| 0.5 | 2026-06-22 | orders に移行用の金額・配送スナップショット列を追加 |
| 0.6 | 2026-06-22 | carts / cart_items 追加 |
| 0.7 | 2026-06-22 | 消費税方針を確定（内税・10% 固定・税率列なし） |
| 0.8 | 2026-06-22 | 型番（SKU）は未使用のため持たない方針を明記 |
| 0.9 | 2026-06-22 | 定価は未使用。販売価格のみ保持 |
| 0.10 | 2026-06-22 | ISBN/JAN 列を削除（未使用） |
| 0.11 | 2026-06-22 | 重量列を削除（未使用） |
| 0.12 | 2026-06-22 | 在庫管理ルールを明文化（一部商品のみ管理） |
| 0.13 | 2026-06-22 | 販売期間は未使用（期間制限なし） |
| 0.14 | 2026-06-22 | 掲載設定は boolean のみ（会員限定なし） |
| 0.15 | 2026-06-22 | 商品別 SEO 列は持たない |
| 0.16 | 2026-06-22 | 購入数量の最小/最大制限は未使用 |
| 0.17 | 2026-06-22 | 決済 3 種・入金/発送ステータス定義。tracking_number 追加 |
| 0.18 | 2026-06-22 | 銀行振込は入金確認後にのみ発送可 |
| 0.19 | 2026-06-22 | coupons テーブル追加。orders に coupon_id / coupon_code |
| 0.20 | 2026-06-22 | クーポンは定額のみ（discount_amount） |
| 0.21 | 2026-06-22 | 注文番号：移行は売上 ID、新規はランダム 10 桁 |
| 0.22 | 2026-06-22 | 注文番号は数字のみ。採番手順を詳述 |
| 0.23 | 2026-06-22 | 管理者アカウントを未決事項に追記 |
| 0.24 | 2026-06-22 | 管理者は users.is_admin（方式 A）で確定 |
| 0.25 | 2026-06-22 | ギフト・のし・ラッピングは未使用 |
| 0.26 | 2026-06-22 | キャンセル・返金（refunds テーブル、orders 列追加） |
| 0.27 | 2026-06-22 | 振込先口座は設定ファイル管理（DB なし） |
| 0.28 | 2026-06-22 | 保存配送先は不要（saved_addresses なし） |
| 0.29 | 2026-06-22 | 会員チェックアウトは customers を初期表示 |
| 0.30 | 2026-06-22 | 氏名は name のみ（姓・名分割なし） |
| 0.31 | 2026-06-22 | 住所移行ルールを §3.9 に合わせて追記 |
| 0.32 | 2026-06-22 | 住所移行は分割しない方針に確定 |
| 0.33 | 2026-06-22 | インボイス登録番号は config 管理（§3.18） |
| 0.34 | 2026-06-22 | 代引き手数料ルールを追記 |
| 0.35 | 2026-06-22 | 顧客・会員移行ルール（§3.20） |
| 0.36 | 2026-06-22 | 決済方法は 3 種のみ（移行マッピング） |
| 0.37 | 2026-06-22 | 移行専用 amazon_pay を追加 |
| 0.39 | 2026-06-22 | slug は ID ベース（§3.21） |
| 0.40 | 2026-06-22 | キャンセル・返金・注文後金額変更（§3.17） |
| 0.41 | 2026-06-24 | watchlist_entries 追加（要注意客・管理画面警告・照合キー案 5） |
| 0.42 | 2026-06-24 | 顧客をカラーミー型に変更（全会員・非会員を customers に保持）。user_id UK・email IDX。orders.customer_id を常時セット。§13.6 追加 |
| 0.43 | 2026-06-24 | orders.stripe_payment_intent_id に UK。Stripe PI の保存ルールを §13.4 に追記 |
| 0.44 | 2026-06-24 | 在庫・クーポン加算タイミング、キャンセル詳細、Stripe 未完了注文、振込案内 7 日、メール認証、ゲストカート 90 日、device 記録 |
| 0.45 | 2026-06-24 | カートマージ詳細、会員カート期限なし、在庫不足のカート警告、オプションなしバリアント名、移行 shipping_method_id=NULL、DB 照合順序・TZ（§1.6） |
| 0.46 | 2026-06-25 | 最終確認完了。ステータスを確定に更新 |
| 0.47 | 2026-07-24 | Stripe 未完了注文は 7 日で自動キャンセル（一覧除外と併用） |
