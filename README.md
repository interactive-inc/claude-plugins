# Claude Code プラグインマーケットプレース

Interactive Inc. が提供する Claude Code 用のプラグイン集です。

## インストール方法

### 前提条件

- Claude Code がインストールされていること

### マーケットプレースの追加

`/plugins` コマンドからマーケットプレースを追加:

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

マーケットプレース追加後、以下のコマンドでプラグインをインストールできます：

```bash
# claude プラグインのインストール
/plugin install claude@interactive-claude-plugins

# jobantenna プラグインのインストール
/plugin install jobantenna@interactive-claude-plugins
```

## スキル一覧（クイックリファレンス）

| スキル名 | プラグイン | 用途 | ドキュメント |
|---------|-----------|------|------------|
| skills-review | claude | Claude Code スキルのレビュー | [📖](./skills/claude/skills-review/README.md) |
| subagent-review | claude | サブエージェントのレビュー | [📖](./skills/claude/subagent-review/README.md) |
| laravel-command | jobantenna | Laravel コマンド実装・レビュー | [📖](./skills/jobantenna/laravel-command/README.md) |
| laravel-mail | jobantenna | Laravel メール実装・レビュー | [📖](./skills/jobantenna/laravel-mail/README.md) |
| phpunit-runner | jobantenna | PHPUnit テスト実行 | [📖](./skills/jobantenna/phpunit-runner/README.md) |

## 利用可能なプラグイン

### 1. claude プラグイン

Claude Code スキルとサブエージェント開発を支援するプラグインです。

#### 含まれるスキル

**skills-review**
- Claude Code スキルをベストプラクティスに照らして包括的にレビュー
- 6つの観点（Description 品質、Progressive Disclosure、コンテンツ品質、ワークフロー、テンプレート・例、技術的詳細）から評価
- A-F 評価とスコアを算出し、優先度付き改善提案を提供
- 📖 [詳細ドキュメント](./skills/claude/skills-review/README.md)

**subagent-review**
- Claude Code サブエージェント実装をレビュー
- 5つの観点（単一責任原則、システムプロンプト品質、ツールアクセス制限、バージョン管理統合、適切な基盤）から評価
- セキュリティ、フォーカス、効果性を確保するための具体的な改善提案
- 📖 [詳細ドキュメント](./skills/claude/subagent-review/README.md)

---

### 2. jobantenna プラグイン

JobAntenna プロジェクト固有の開発支援プラグインです。Laravel アプリケーション開発を効率化します。

#### 含まれるスキル

**laravel-command**
- Laravel Artisan コマンドを実証済みパターンと Laravel 9+ ベストプラクティスに従って実装・レビュー
- 7つのコマンドテンプレート（Basic、ServiceIntegration、BatchProcessing、Scheduled、LongRunning、Isolatable）
- 8つのコアパターン（カスタムベースクラス、サービス統合、大規模データ処理、Dry-Run、エラーハンドリングなど）
- 10の観点からの包括的なコマンドレビュー機能
- 📖 [詳細ドキュメント](./skills/jobantenna/laravel-command/README.md)

**laravel-mail**
- Laravel メール機能（Mailable、Notification、Twig テンプレート、テスト）を JobAntenna の確立されたパターンに従って実装
- 二層アーキテクチャ（Notification + Mailable）による明確な責任分離
- 8つの Sanitize Traits による安全なデータ変換
- カスタム MailFake による期待値ファイル比較テスト
- 10の観点からのメール実装レビュー機能
- 📖 [詳細ドキュメント](./skills/jobantenna/laravel-mail/README.md)

**phpunit-runner**
- JobAntenna の Laradock Docker 環境で PHPUnit テストを非同期実行
- 専用エージェントによる時間のかかるテスト実行
- 特定のクラス、ファイル、またはすべてのテストを柔軟に選択可能
- メイン会話をブロックしないバックグラウンド実行
- 📖 [詳細ドキュメント](./skills/jobantenna/phpunit-runner/README.md)

#### 含まれるエージェント

**laravel-command-reviewer**
- Laravel Artisan コマンド実装を10の観点から評価
- 実証済みパターンとベストプラクティスへの準拠をチェック
- 優先度付き改善提案とコード例を提供

**laravel-mail-reviewer**
- Laravel メール実装（Mailable、Notification、テンプレート、テスト）を10の観点から評価
- JobAntenna の確立されたパターンへの準拠をチェック
- Sanitize Traits の適切な使用を検証

**phpunit-test-runner**
- Docker 環境での PHPUnit テスト実行を自動化
- テスト結果の解析とレポート生成
- エラー詳細と実行サマリーを提供

## ライセンス

このプラグインマーケットプレースは Interactive Inc. によって管理されています。

## サポート

問題や質問がある場合は、プロジェクトのドキュメントを参照するか、開発チームにお問い合わせください。

---

**Interactive Inc.** - Claude Code プラグインマーケットプレース
