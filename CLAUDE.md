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
    ├── claude/              # Claude Code 開発支援スキル
    │   ├── skill-review/        # スキルレビュー
    │   ├── subagent-review/     # サブエージェントレビュー
    │   ├── hooks-review/        # フック設定レビュー
    │   ├── marketplace-review/  # マーケットプレース検証
    │   ├── mcp-review/          # MCP サーバー設定レビュー
    │   └── slash-command-review/ # スラッシュコマンドレビュー
    └── jobantenna/          # プロジェクト固有スキル
        ├── laravel-command/ # Laravel コマンド実装
        ├── laravel-mail/    # Laravel メール実装
        └── phpunit-runner/  # PHPUnit テスト実行
```

## 利用可能なプラグイン

### 1. claude

Claude Code 開発支援プラグイン。スキルとサブエージェント開発を支援します。

**含まれるスキル:**

- **skill-review**: Claude Code スキル自体をベストプラクティスに照らして包括的にレビュー
  - 6つの観点から評価（Description 品質、Progressive Disclosure、コンテンツ品質、ワークフロー、テンプレート・例、技術的詳細）
  - A-F 評価とスコアを算出し、優先度付き改善提案を提供
  - チェックリスト: `skills/claude/skill-review/CHECKLIST.md`
  - レポートテンプレート: `skills/claude/skill-review/REPORT_TEMPLATE.md`

- **subagent-review**: Claude Code サブエージェント実装をレビュー
  - 5つの観点から評価（単一責任原則、システムプロンプト品質、ツールアクセス制限、バージョン管理統合、適切な基盤）
  - セキュリティ、フォーカス、効果性を確保するための具体的な改善提案
  - ベストプラクティス例: `skills/claude/subagent-review/references/examples.md`

- **hooks-review**: Claude Code フック設定をレビュー・構成し、ワークフロー自動化を支援
  - セキュリティ脆弱性、パフォーマンス問題、ベストプラクティス違反を検出
  - 9種類のフックイベント（PreToolUse、PostToolUse、UserPromptSubmit など）をサポート
  - 優先度付き推奨事項と具体的な修正例を提供

- **marketplace-review**: マーケットプレース設定の検証
  - `.claude-plugin/marketplace.json` の構造と参照パスを自動チェック
  - プラグイン、スキル、エージェントの定義を包括的に検証
  - Python スクリプトによるエラーレポートと品質保証
  - マーケットプレース公開前の必須チェック

- **mcp-review**: MCP サーバー設定のレビュー
  - `.mcp.json` をベストプラクティスに照らして検証
  - 7つの観点（セキュリティ、スコープ管理、トランスポートタイプなど）から評価
  - ハードコードされた秘密情報、不適切な環境変数使用を検出
  - stdio/HTTP/SSE の適切なトランスポート選択をガイド

- **slash-command-review**: スラッシュコマンド実装のレビュー
  - 6つの観点（メタデータ、引数処理、動的機能、セキュリティ、スコープ、スキル境界）から評価
  - A-F 評価とスコアリング、優先度付き改善提案
  - コマンドインジェクション、パストラバーサルなどのセキュリティリスクを検出

### 2. jobantenna

JobAntenna プロジェクト固有の開発支援プラグイン。Laravel アプリケーション開発を効率化します。

**含まれるスキル:**

- **laravel-command**: Laravel Artisan コマンドの実装とレビュー
  - 本番環境で実証済みのパターン（JobAntenna v4 から抽出）
  - Laravel 9+ 公式推奨に準拠
  - 7種類のテンプレート（Basic、ServiceIntegration、BatchProcessing、Scheduled、LongRunning、Isolatable）
  - 8つのコアパターン（カスタムベースクラス、サービス統合、大規模データ処理、Dry-Run、エラーハンドリングなど）
  - リファレンス: `skills/jobantenna/laravel-command/references/command-patterns.md`
  - 専門レビューエージェント: `skills/jobantenna/laravel-command/agents/laravel-command-reviewer.md`

- **laravel-mail**: Laravel メール機能の実装とレビュー
  - Mailable、Notification、Twig テンプレート、テストの作成
  - JobAntenna プロジェクトの確立されたパターンに準拠
  - 二層アーキテクチャ（Notification + Mailable）による明確な責任分離
  - 8つの Sanitize Traits による安全なデータ変換
  - カスタム MailFake による期待値ファイル比較テスト
  - リファレンス: `skills/jobantenna/laravel-mail/references/sanitize-traits-reference.md`
  - 専門レビューエージェント: `skills/jobantenna/laravel-mail/agents/laravel-mail-reviewer.md`

- **phpunit-runner**: PHPUnit テストの実行
  - JobAntenna の Laradock Docker 環境で PHPUnit テストを非同期実行
  - 専用エージェントによる時間のかかるテスト実行
  - 特定のクラス、ファイル、またはすべてのテストを柔軟に選択可能
  - メイン会話をブロックしないバックグラウンド実行
  - 専門実行エージェント: `skills/jobantenna/phpunit-runner/agents/phpunit-test-runner.md`

**含まれるエージェント:**

- **laravel-command-reviewer**: Laravel Artisan コマンド実装を10の観点から評価し、優先度付き改善提案を提供
- **laravel-mail-reviewer**: Laravel メール実装を10の観点から評価し、JobAntenna パターンへの準拠をチェック
- **phpunit-test-runner**: Docker 環境での PHPUnit テスト実行を自動化し、結果レポートを提供

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

`skill-review` スキルを使用して新規スキルの品質を評価します。

**使用例:**
```
このスキルをレビューしてください: skills/jobantenna/laravel-command
```

### サブエージェントのレビュー

`subagent-review` スキルを使用してサブエージェント実装を評価します。

**使用例:**
```
このサブエージェントをレビューしてください: skills/jobantenna/laravel-command/agents/laravel-command-reviewer.md
```

### フック設定のレビュー

`hooks-review` スキルを使用してフック設定をレビューし、セキュリティとベストプラクティスを確認します。

**使用例:**
```
フック設定をレビューしてください
```

### マーケットプレース設定の検証

`marketplace-review` スキルを使用してマーケットプレース設定を検証します。

**使用例:**
```
マーケットプレース設定を検証してください
```

### MCP サーバー設定のレビュー

`mcp-review` スキルを使用して MCP サーバー設定をレビューします。

**使用例:**
```
MCP サーバー設定をレビューしてください
```

### スラッシュコマンドのレビュー

`slash-command-review` スキルを使用してスラッシュコマンド実装をレビューします。

**使用例:**
```
スラッシュコマンドをレビューしてください: .claude/commands/my-command.md
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

### PHPUnit テストの実行

`phpunit-runner` スキルを使用して Docker 環境でテストを実行します。

**使用例:**
```
PHPUnit テストを実行してください
```

**特定のテストクラスを実行:**
```
UserTest クラスのテストを実行してください
```

## プラグインのインストールと利用

### マーケットプレース追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインインストール

```bash
/plugin install claude@interactive-claude-plugins
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
