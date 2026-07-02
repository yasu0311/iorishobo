# デザイン方針

> **プロジェクト名:** いおり書房 EC サイト（iorishobo）  
> **最終更新日:** 2026-07-02  
> **関連:** [タスク管理](./tasks.md) フェーズ 3.1 / [ディレクトリ構成](./directory-structure.md)

---

## 1. トーン & ブランドイメージ

### コンセプト

**「クールで信頼感のある書店」** — ネイビーブルーを基調に、清潔感と落ち着きのある配色で書籍 EC らしい信頼性と読みやすさを両立する。

| 観点 | 方針 |
|------|------|
| **全体の印象** | すっきり・知的・クール。余白を活かし、細いボーダーと淡いブルーグレー背景で整理された印象にする |
| **対象ユーザー** | 書籍・教材を探す一般顧客（幅広い年齢層）。管理画面は店舗スタッフ向け |
| **UI の優先順位** | 商品名・価格・在庫・購入導線を最優先。装飾はコンテンツを邪魔しない範囲に留める |
| **sample_code との関係** | CSS のファイル分割・読み込み順は [sample_code](./sample_code/public/css/) を参考にする。プライマリカラー `#003b83` をストアフロントのアクセントとして採用する |

### ストアフロント vs 管理画面

| 領域 | トーン | 備考 |
|------|--------|------|
| **ストアフロント** | ブルー系・クール・清潔感 | PC / スマホ両対応 |
| **管理画面** | 機能的・中立（グレー基調） | PC 専用。業務効率・視認性を優先し、装飾は最小限 |

---

## 2. カラーパレット

### 2.1 ストアフロント

| 役割 | 変数名 | 値 | 用途 |
|------|--------|-----|------|
| ページ背景 | `--color-bg` | `#f0f4f8` | body 背景（淡いブルーグレー） |
| サーフェス | `--color-surface` | `#ffffff` | カード・パネル・ヘッダー / フッター |
| 本文テキスト | `--color-text` | `#1e293b` | 主要テキスト（スレート系ダーク） |
| 補助テキスト | `--color-muted` | `#64748b` | リード文・フッター・税込表記など |
| ボーダー | `--color-border` | `#e2e8f0` | カード枠・区切り線 |
| アクセント | `--color-accent` | `#003b83` | リンク・価格・プライマリボタン（ネイビーブルー） |
| アクセント（hover） | `--color-accent-hover` | `#002a5c` | リンク hover・ボタン hover |
| 危険・エラー | `--color-danger` | `#dc2626` | バリデーションエラー・売り切れ強調 |
| 画像プレースホルダ | — | `#e8eef4` | 商品画像なし時の背景（変数化は任意） |
| 成功（フラッシュ） | — | 背景 `#ecfdf5` / 枠 `#a7f3d0` / 文字 `#065f46` | `.alert--success`（3.2 で変数化可） |
| エラー（フラッシュ） | — | 背景 `#fef2f2` / 枠 `#fecaca` | `.alert--error`（文字は `--color-danger`） |

**配色の意図:** 背景はクールなブルーグレー、差し色はネイビーで統一。茶系・暖色は使わない。

### 2.2 管理画面

業務画面はストアフロントと同系統のブルー・グレーを基調とする（現行 `layouts/admin.blade.php` 準拠）。3.2 で `public/css/admin/layout.css` に切り出す際に以下の変数を定義する。

| 役割 | 変数名（予定） | 値 | 用途 |
|------|----------------|-----|------|
| ページ背景 | `--admin-color-bg` | `#f3f4f6` | body 背景 |
| 本文テキスト | `--admin-color-text` | `#1f2937` | 通常テキスト |
| ヘッダー背景 | `--admin-color-header` | `#003b83` | 上部バー（ストアフロントと同色） |
| ヘッダー文字 | `--admin-color-header-text` | `#f9fafb` | ヘッダー内リンク |
| リンク | `--admin-color-link` | `#1d4ed8` | 本文中のリンク |
| サーフェス | `--admin-color-surface` | `#ffffff` | カード・テーブル背景 |
| ボーダー | `--admin-color-border` | `#d1d5db` | テーブル・フォーム枠 |

---

## 3. タイポグラフィ

### 3.1 フォントファミリー

Web フォントは読み込まない。**システムフォント**で表示速度と日本語の読みやすさを優先する。

| 変数名 | スタック | 用途 |
|--------|----------|------|
| `--font-sans` | `"Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif` | 本文・UI・商品名 |
| `--font-serif` | `"Hiragino Mincho ProN", "Yu Mincho", Georgia, serif` | 見出し（h1–h3）・ロゴ（知的・落ち着きのある印象） |

管理画面は見出しもゴシック（`--font-sans` 相当）で統一し、業務画面らしい無骨さを保つ。

### 3.2 サイズ・行間（ストアフロント）

| 要素 | サイズ | 行間 | フォント |
|------|--------|------|----------|
| body | `16px`（1rem） | `1.6` | sans |
| h1 | `1.75rem`（モバイル `1.5rem`） | `1.35` | serif |
| h2 | `1.375rem` | `1.35` | serif |
| ヒーロータイトル | `2rem` | — | serif |
| ナビ・フッター | `0.875rem`〜`0.9375rem` | — | sans |
| 商品カード名 | `0.9375rem` | `1.4` | sans |
| 商品価格 | `0.875rem` | — | sans（色は accent） |

### 3.3 管理画面

| 要素 | サイズ |
|------|--------|
| body | `15px` |
| h1 | `1.5rem` |
| ナビ | `0.875rem` |

---

## 4. `:root` CSS 変数定義

実装時は **ストアフロント** を `public/css/front/layout.css` の先頭、**管理画面** を `public/css/admin/layout.css` の先頭に配置する。

### 4.1 ストアフロント（`front/layout.css`）

```css
:root {
  /* カラー */
  --color-bg: #f0f4f8;
  --color-surface: #fff;
  --color-text: #1e293b;
  --color-muted: #64748b;
  --color-border: #e2e8f0;
  --color-accent: #003b83;
  --color-accent-hover: #002a5c;
  --color-danger: #dc2626;

  /* タイポグラフィ */
  --font-sans: "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
  --font-serif: "Hiragino Mincho ProN", "Yu Mincho", Georgia, serif;

  /* レイアウト・装飾 */
  --max-width: 1080px;
  --radius: 6px;
  --shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}
```

| 変数 | 説明 |
|------|------|
| `--max-width` | ヘッダー・メイン・フッターの最大幅 |
| `--radius` | ボタン・カード・パネルの角丸 |
| `--shadow` | カード・ヘッダーの軽い影（スレート系） |

### 4.2 管理画面（`admin/layout.css`・予定）

```css
:root {
  --admin-color-bg: #f3f4f6;
  --admin-color-text: #1f2937;
  --admin-color-header: #003b83;
  --admin-color-header-text: #f9fafb;
  --admin-color-link: #1d4ed8;
  --admin-color-surface: #ffffff;
  --admin-color-border: #d1d5db;

  --admin-font-sans: "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
  --admin-max-width: 1200px;
  --admin-radius: 4px;
}
```

### 4.3 命名規則

| プレフィックス | スコープ |
|----------------|----------|
| `--color-*` | ストアフロント共通 |
| `--font-*` | ストアフロント共通 |
| `--admin-*` | 管理画面専用（ストアフロント CSS と混在させない） |

ユーティリティクラス（`.text-muted` 等）は `public/css/common/utility.css` に集約し、変数を参照する。

---

## 5. レイアウト・コンポーネントの原則（概要）

詳細はフェーズ 3.1 後続タスクおよび 3.2 以降で確定する。ここでは方針のみ記載する。

- **余白:** セクション間 `2.5rem`、パネル内 `1.5rem` を基準
- **ボタン:** プライマリは `--color-accent` 塗り、セカンダリは白背景＋ボーダー
- **カード:** 白背景・細ボーダー・軽い影。hover で影をやや強く
- **フォーム:** ラベルは `0.875rem`・太字。入力枠は `--color-border`、focus 時 `--color-accent`

---

## 改訂履歴

| 日付 | 内容 |
|------|------|
| 2026-07-02 | 初版 — トーン・カラー・タイポグラフィ・`:root` 変数を文書化 |
| 2026-07-02 | 配色をブルー系・クールトーンに変更（アクセント `#003b83`、背景 `#f0f4f8`） |
| 2026-07-02 | `guest-layout.css` を `front/layout.css` にリネーム（`admin/layout.css` と対になる命名） |
