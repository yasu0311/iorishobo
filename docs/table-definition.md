# テーブル定義書（草案）

> **ステータス: 検討中（未実装）**
>
> 設計方針・CSV 分析は [データベース仕様書](./specification.md) を参照。

## ドキュメント情報

| 項目 | 内容 |
|------|------|
| プロジェクト名 | いおり書房 EC サイト（iorishobo） |
| DBMS | MySQL 8.x |
| 文字コード | utf8mb4 |
| バージョン | 0.6（草案） |
| 最終更新日 | 2026-06-22 |

---

## 1. 共通定義

### 1.1 住所カラムセット

カラーミー購入フォームの **address1 / address2** に対応。  
（city / street 等への分割は **行わない**）

| 物理名 | 論理名 | 型 | NULL | チェックアウト | カラーミー対応 |
|--------|--------|-----|------|--------------|--------------|
| postal_code | 郵便番号 | CHAR(7) | NO* | 必須 | 郵便番号 |
| prefecture | 都道府県 | VARCHAR(20) | NO* | 必須 | 都道府県 / 都道府県名 |
| address_line1 | 住所 | VARCHAR(255) | NO* | 必須 | address1 / 住所 |
| address_line2 | 建物名 | VARCHAR(255) | YES | 任意 | address2 / 建物名 |

\* 移行データ import 直後のみ NULL 許容の場合あり。

### 1.2 金額

すべて **INT UNSIGNED・円・税込**。

### 1.3 必須/任意（アプリケーション）

| 項目 | ルール |
|------|--------|
| フリガナ | **任意**（配送先フリガナは sales CSV で 75% が空） |
| 電話・携帯 | **どちらか 1 つ必須** |
| address_line2 | **任意** |

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
| 7 | carts | カート | トランザクション |
| 8 | cart_items | カート明細 | トランザクション |
| 9 | orders | 注文 | トランザクション |
| 10 | order_items | 注文明細 | トランザクション |
| 11 | users | ユーザー | マスタ（Laravel 標準） |

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
| orders | order_items | order_id | CASCADE |
| product_variants | order_items | product_variant_id | SET NULL |

---

## 4. categories（カテゴリ）

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | parent_id | 親カテゴリ ID | BIGINT UNSIGNED | YES | FK | NULL = 大カテゴリ |
| 3 | name | カテゴリ名 | VARCHAR(255) | NO | | |
| 4 | slug | スラッグ | VARCHAR(255) | NO | UK | |
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
| 5 | slug | スラッグ | VARCHAR(255) | NO | UK | |
| 6 | isbn | ISBN/JAN | VARCHAR(20) | YES | | |
| 7 | short_description | 簡易説明 | TEXT | YES | | |
| 8 | description | 商品説明 | LONGTEXT | YES | | HTML 可 |
| 9 | base_price | 基本価格 | INT UNSIGNED | NO | | 税込。オプションあり商品は表示用 |
| 10 | weight | 重量 | INT UNSIGNED | YES | | グラム |
| 11 | stock_managed | 在庫管理 | BOOLEAN | NO | | true のときバリアント単位で在庫を見る |
| 12 | is_published | 掲載 | BOOLEAN | NO | | |
| 13 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 14 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 15 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 6. product_images（商品画像）

カラーミー `product.csv` の「商品画像 URL」「その他画像 1〜9 URL」に対応。  
**sort_order = 0 がメイン画像**（一覧・OGP 等はここを参照）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | product_id | 商品 ID | BIGINT UNSIGNED | NO | FK | |
| 3 | path | 画像パス | VARCHAR(500) | NO | | 保存先パスまたは URL |
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
| 7 | stock | 在庫数 | INT | NO | | |
| 8 | is_active | 有効 | BOOLEAN | NO | | |
| 9 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 10 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 11 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 8. customers（顧客）

customer.csv 相当。**FAX は持たない**（実データ常に空）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | colorme_customer_id | カラーミー顧客 ID | BIGINT UNSIGNED | YES | UK | |
| 3 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK | 会員紐付け |
| 4 | name | 氏名 | VARCHAR(100) | NO | | |
| 5 | name_kana | フリガナ | VARCHAR(100) | YES | | **任意** |
| 6 | email | メール | VARCHAR(255) | YES | | |
| 7 | postal_code | 郵便番号 | CHAR(7) | YES | | §1.1 |
| 8 | prefecture | 都道府県 | VARCHAR(20) | YES | | §1.1 |
| 9 | address_line1 | 住所 | VARCHAR(255) | YES | | §1.1 |
| 10 | address_line2 | 建物名 | VARCHAR(255) | YES | | §1.1 |
| 11 | phone | 電話番号 | VARCHAR(20) | YES | | |
| 12 | mobile | 携帯番号 | VARCHAR(20) | YES | | |
| 13 | note | 備考 | TEXT | YES | | |
| 14 | registered_at | 登録日時 | TIMESTAMP | YES | | 移行用 |
| 15 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 16 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 9. shipping_methods（配送方法）

全国一律の送料。**地域別・重量別の料金表は持たない**（クリックポスト / ゆうパック等は方法ごとに 1 レコード）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | name | 名称 | VARCHAR(255) | NO | | 例: クリックポスト |
| 3 | slug | スラッグ | VARCHAR(50) | NO | UK | |
| 4 | base_fee | 基本送料 | INT UNSIGNED | NO | | 円・全国一律 |
| 5 | free_shipping_threshold | 送料無料ライン | INT UNSIGNED | YES | | 商品合計（税込）がこの金額以上で送料 0 円。NULL = なし |
| 6 | is_active | 有効 | BOOLEAN | NO | | |
| 7 | sort_order | 表示順 | INT UNSIGNED | NO | | |
| 8 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 9 | updated_at | 更新日時 | TIMESTAMP | YES | | |

---

## 10. carts（カート）

チェックアウト前の買い物かご。**カラーミー CSV の移行対象外**（新規サイト専用）。

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK, UK | ログイン時。ゲストは NULL |
| 3 | session_id | セッション ID | VARCHAR(255) | YES | UK | ゲスト時。Laravel セッション ID |
| 4 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 5 | updated_at | 更新日時 | TIMESTAMP | YES | | |

- `user_id` と `session_id` の **どちらか一方** でカートを特定する（アプリ側で保証）。
- ログイン時にゲストカートをユーザーカートへマージする（アプリ側）。

---

## 11. cart_items（カート明細）

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

## 12. orders（注文）

sales_all.csv 相当。**購入者（buyer_*）と配送先（shipping_*）を両方保持**。

### 12.1 注文共通

| No | 物理名 | 論理名 | 型 | NULL | キー | 説明 |
|----|--------|--------|-----|------|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK | |
| 2 | colorme_sales_id | カラーミー売上 ID | BIGINT UNSIGNED | YES | UK | |
| 3 | customer_id | 顧客 ID | BIGINT UNSIGNED | YES | FK | |
| 4 | user_id | ユーザー ID | BIGINT UNSIGNED | YES | FK | |
| 5 | order_number | 注文番号 | VARCHAR(50) | NO | UK | |
| 6 | ordered_at | 受注日時 | TIMESTAMP | NO | | |
| 7 | device | デバイス | VARCHAR(50) | YES | | |
| 8 | subtotal | 商品合計 | INT UNSIGNED | NO | | 税込 |
| 9 | tax_amount | 消費税 | INT UNSIGNED | NO | | |
| 10 | shipping_fee | 送料 | INT UNSIGNED | NO | | |
| 11 | payment_fee | 決済手数料 | INT UNSIGNED | NO | | 新規 Stripe 注文は 0 |
| 12 | discount | 割引金額 | INT UNSIGNED | NO | | クーポン等の合計 |
| 13 | discount_name | 割引名称 | VARCHAR(255) | YES | | クーポン名等。なければ NULL |
| 14 | point_discount | ポイント割引 | INT UNSIGNED | NO | | ショップポイント分。未使用は 0 |
| 15 | external_point_discount | 外部ポイント割引 | INT UNSIGNED | NO | | GMO ポイント等。未使用は 0 |
| 16 | total | 総合計 | INT UNSIGNED | NO | | 税込 |
| 17 | payment_method | 決済方法 | VARCHAR(50) | YES | | |
| 18 | payment_status | 入金状態 | VARCHAR(30) | YES | | |
| 19 | shipping_status | 発送状態 | VARCHAR(30) | YES | | |
| 20 | shipped_at | 発送日時 | TIMESTAMP | YES | | |
| 21 | shipping_method_id | 配送方法 ID | BIGINT UNSIGNED | YES | FK | 新規注文用 |
| 22 | shipping_method_name | 配送方法名 | VARCHAR(255) | YES | | 受注時の名称スナップショット |
| 23 | customer_note | 注文備考 | TEXT | YES | | sales 列 33 |
| 24 | shipping_note | 配送先備考 | TEXT | YES | | sales 列 54 |
| 25 | stripe_payment_intent_id | Stripe PI ID | VARCHAR(255) | YES | | |
| 26 | created_at | 作成日時 | TIMESTAMP | YES | | |
| 27 | updated_at | 更新日時 | TIMESTAMP | YES | | |

### 12.2 購入者（buyer_*）— sales_all「購入者」列

| No | 物理名 | 論理名 | 型 | NULL | CSV 列 |
|----|--------|--------|-----|------|--------|
| 28 | buyer_name | 購入者氏名 | VARCHAR(100) | NO | 購入者 名前 |
| 29 | buyer_email | 購入者メール | VARCHAR(255) | NO | 購入者 メールアドレス |
| 30 | buyer_phone | 購入者電話 | VARCHAR(20) | YES | 購入者 電話番号 |
| 31 | buyer_mobile | 購入者携帯 | VARCHAR(20) | YES | 購入者 携帯番号 |
| 32 | buyer_postal_code | 購入者郵便番号 | CHAR(7) | NO | 購入者 郵便番号 |
| 33 | buyer_prefecture | 購入者都道府県 | VARCHAR(20) | NO | 購入者 都道府県 |
| 34 | buyer_address_line1 | 購入者住所 | VARCHAR(255) | NO | 購入者 住所 |
| 35 | buyer_address_line2 | 購入者建物名 | VARCHAR(255) | YES | （CSV では結合） |

※ 購入者フリガナ列は **sales_all.csv に存在しない** ため持たない。

### 12.3 配送先（shipping_*）— sales_all「配送先」列

| No | 物理名 | 論理名 | 型 | NULL | CSV 列 |
|----|--------|--------|-----|------|--------|
| 36 | shipping_name | 配送先氏名 | VARCHAR(100) | NO | 配送先 名前 |
| 37 | shipping_name_kana | 配送先フリガナ | VARCHAR(100) | YES | 配送先 フリガナ・**任意** |
| 38 | shipping_phone | 配送先電話 | VARCHAR(20) | NO | 配送先 電話番号 |
| 39 | shipping_postal_code | 配送先郵便番号 | CHAR(7) | NO | 配送先 郵便番号 |
| 40 | shipping_prefecture | 配送先都道府県 | VARCHAR(20) | NO | 配送先 都道府県名 |
| 41 | shipping_address_line1 | 配送先住所 | VARCHAR(255) | NO | 配送先 住所 |
| 42 | shipping_address_line2 | 配送先建物名 | VARCHAR(255) | YES | （CSV では結合） |

---

## 13. order_items（注文明細）

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

---

## 14. users（ユーザー）

Laravel 12 標準。認証専用。住所・電話は **customers** に保持。

| No | 物理名 | 論理名 | 型 | NULL | キー |
|----|--------|--------|-----|------|------|
| 1 | id | ID | BIGINT UNSIGNED | NO | PK |
| 2 | name | 氏名 | VARCHAR(255) | NO | |
| 3 | email | メール | VARCHAR(255) | NO | UK |
| 4 | email_verified_at | 確認日時 | TIMESTAMP | YES | |
| 5 | password | パスワード | VARCHAR(255) | NO | |
| 6 | remember_token | トークン | VARCHAR(100) | YES | |
| 7 | created_at | 作成日時 | TIMESTAMP | YES | |
| 8 | updated_at | 更新日時 | TIMESTAMP | YES | |

---

## 15. 改訂履歴

| バージョン | 日付 | 内容 |
|-----------|------|------|
| 0.1 | 2026-06-21 | 初版草案 |
| 0.2 | 2026-06-21 | CSV 実データ反映 |
| 0.3 | 2026-06-21 | 住所を address_line1/2 に修正。customer_addresses 削除。buyer_* に統一 |
| 0.4 | 2026-06-22 | product_images 追加。shipping_methods に送料無料ライン追加。送料は全国一律 |
| 0.5 | 2026-06-22 | orders に移行用の金額・配送スナップショット列を追加 |
| 0.6 | 2026-06-22 | carts / cart_items 追加 |
