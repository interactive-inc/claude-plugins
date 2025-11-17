# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## リポジトリ概要

このリポジトリは Interactive Inc. が提供する Claude Code 用のプラグインマーケットプレースです。複数のプラグインを含み、それぞれが専門的なスキルとエージェントを提供します。

## プラグイン構造

### マーケットプレース設定

- 設定ファイル: `.claude-plugin/marketplace.json`
- マーケットプレース名: `interactive-claude-plugins`
- 所有者: Interactive Inc.

### ディレクトリ構造

```
.
├── .claude-plugin/
│   └── marketplace.json     # プラグインマーケットプレース定義
└── skills/                  # スキル実装
    ├── general/             # 汎用スキル
    │   └── claude-skills-review/  # スキルレビュー
    └── jobantenna/          # プロジェクト固有スキル
        ├── laravel-command/ # Laravel コマンド実装
        └── laravel-mail/    # Laravel メール実装
```

## 利用可能なプラグイン

### 1. general

汎用的な開発支援プラグイン。

**含まれるスキル:**

- **claude-skills-review**: Claude Code スキル自体をベストプラクティスに照らして包括的にレビュー
  - 7つの観点から評価（命名、description、Progressive Disclosure など）
  - A-F 評価とスコアを算出
  - チェックリスト: `skills/general/claude-skills-review/CHECKLIST.md`
  - 改善例: `skills/general/claude-skills-review/EXAMPLES.md`

### 2. jobantenna

ジョブアンテナプロジェクト固有の開発支援プラグイン。

**含まれるスキル:**

- **laravel-command**: Laravel Artisan コマンドの実装とレビュー
  - 本番環境で実証済みのパターン（JobAntenna v4 から抽出）
  - Laravel 9+ 公式推奨に準拠
  - 7種類のテンプレート: `skills/jobantenna/laravel-command/assets/templates/`
  - リファレンス: `skills/jobantenna/laravel-command/references/command-patterns.md`
  - 専門レビューエージェント: `skills/jobantenna/laravel-command/agents/laravel-command-reviewer.md`
  - クイックスタート: `skills/jobantenna/laravel-command/QUICK_START.md`

- **laravel-mail**: Laravel メール機能の実装とレビュー
  - Mailable、Notification、Twig テンプレートの作成
  - JobAntenna プロジェクトの確立されたパターンに準拠
  - Sanitize Traits によるデータ変換: `skills/jobantenna/laravel-mail/references/sanitize-traits-reference.md`
  - テスト実装パターン: `skills/jobantenna/laravel-mail/references/mail-test-patterns.md`
  - 専門レビューエージェント: `skills/jobantenna/laravel-mail/agents/laravel-mail-reviewer.md`
  - クイックスタート: `skills/jobantenna/laravel-mail/QUICK_START.md`

## スキル開発のベストプラクティス

### スキルファイル構成

各スキルは以下の構造を持ちます：

```
skills/{skill-name}/
├── SKILL.md              # メインスキル定義（YAML frontmatter + 説明）
├── REPORT_TEMPLATE.md    # 出力テンプレート（該当する場合）
├── CHECKLIST.md          # 評価基準（該当する場合）
├── EXAMPLES.md           # 使用例（該当する場合）
└── assets/               # テンプレートやリソース（該当する場合）
```

### SKILL.md の frontmatter

必須フィールド:
- `name`: Gerund 形式（動詞+-ing）、小文字、ハイフン区切り
- `description`: 三人称、具体的なキーワード、トリガーワード含む、1024文字以内

### Progressive Disclosure

- SKILL.md は 500 行以下を推奨
- 詳細情報は外部ファイルに分離（参照の深さは 1 階層のみ）
- 必要時のみ読み込まれる構造

## エージェント開発のベストプラクティス

### エージェントファイル構成

エージェントファイルは Markdown 形式で、スキル内の `agents/` ディレクトリに配置します：

- YAML frontmatter（name, description）
- 役割と専門性の説明
- レビュー観点
- 改善提案方法
- 出力形式
- 最終報告形式への参照

**配置例:**
```
skills/jobantenna/laravel-command/
├── agents/
│   └── laravel-command-reviewer.md
├── SKILL.md
└── ...
```

### 最終報告形式

エージェントは作業完了時に以下の 5 つのセクションを含む統一形式で報告：

1. 実行結果サマリー
2. 対象ファイル一覧（相対パス）
3. 具体的な提案内容（サンプルコード付き）
4. 意思決定が必要な事項
5. 次のアクション

## 新規プラグインの追加方法

1. `.claude-plugin/marketplace.json` に新しいプラグイン定義を追加
2. 必要なスキルを `skills/` ディレクトリに作成
3. 必要なエージェントを `agents/` ディレクトリに作成
4. 各ファイルが構造ベストプラクティスに従っているか確認

## スキルの使用方法

### スキルのレビュー

`claude-skills-review` スキルを使用して新規スキルの品質を評価します。対象スキルのディレクトリパスを指定して依頼してください。

**使用例:**
```
このスキルをレビューしてください: skills/jobantenna/laravel-command
```

### Laravel コマンドの実装

`laravel-command` スキルを使用してコマンドを実装またはレビューします。

**実装例:**
```
ユーザーの未承認データを削除するバッチコマンドを作成してください
```

**レビュー例:**
```
app/Console/Commands/DeleteUnverifiedUsers.php をレビューしてください
```

### Laravel メールの実装

`laravel-mail` スキルを使用してメール機能を実装またはレビューします。

**実装例:**
```
パスワードリセット完了メールを作成してください
```

**レビュー例:**
```
app/Mail/PasswordResetComplete.php をレビューしてください
```

## プラグインのインストールと利用

### マーケットプレース追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインインストール

```bash
/plugin install general@interactive-claude-plugins
/plugin install jobantenna@interactive-claude-plugins
```

### インストール済みプラグインの確認

```bash
/plugin list
```

## 注意事項

- すべてのスキルとエージェントは日本語で記述されています
- ベストプラクティスに厳格に準拠していますが、プロジェクト固有の事情も考慮します
- スキルの Progressive Disclosure により、コンテキストウィンドウを効率的に使用します
