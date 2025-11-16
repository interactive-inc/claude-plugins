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
├── agents/                  # 専門レビューエージェント
│   └── review/              # コードレビュー用エージェント群
└── skills/                  # スキル実装
    ├── reviewing-skills/    # スキルレビュー
    ├── reviewing-ts-code/   # TypeScript コードレビュー
    └── jobantenna/          # プロジェクト固有スキル
```

## 利用可能なプラグイン

### 1. review-skills

コードレビューと品質評価を支援するプラグイン。

**含まれるスキル:**

- **reviewing-ts-code**: 実装コードを 6 つの専門エージェント（SRP、可読性、KISS、規約、TypeScript、ゴミファイル検出）で多角的にレビュー
  - トレードオフを考慮した優先度付き改善計画を策定
  - 統合レポートテンプレート: `skills/reviewing-ts-code/REPORT_TEMPLATE.md`
  - メトリクス定義: `skills/reviewing-ts-code/METRICS.md`

- **reviewing-skills**: Claude Code スキル自体をベストプラクティスに照らしてレビュー
  - 8 つの観点から評価（命名、description、Progressive Disclosure など）
  - A-F 評価とスコアを算出
  - チェックリスト: `skills/reviewing-skills/CHECKLIST.md`

**含まれるエージェント（agents/review/）:**

- `review-srp-reviewer.md`: Single Responsibility Principle 評価
- `review-human-code-reviewer.md`: Code for Humans（可読性）評価
- `review-kiss-reviewer.md`: KISS 原則（シンプルさ）評価
- `review-coc-reviewer.md`: Convention over Configuration 評価
- `review-typescript-comprehensive.md`: TypeScript 型安全性総合評価
- `review-garbage-detector.md`: 不要ファイル・ゴミファイル検出

### 2. jobantenna

ジョブアンテナプロジェクト固有の開発支援プラグイン。

**含まれるスキル:**

- **laravel-command-patterns**: Laravel Artisan コマンドのベストプラクティス実装パターン
  - 本番環境で実証済みのパターン
  - Laravel 9+ 公式推奨に準拠
  - テンプレート: `skills/jobantenna/laravel-command-patterns/assets/templates/`
  - リファレンス: `skills/jobantenna/laravel-command-patterns/references/command-patterns.md`

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

エージェントファイルは Markdown 形式で、以下を含みます：

- YAML frontmatter（name, description）
- 役割と専門性の説明
- レビュー観点
- 改善提案方法
- 出力形式
- 最終報告形式への参照

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

## テストとレビュー

### スキルのレビュー

`reviewing-skills` スキルを使用して新規スキルの品質を評価：

```bash
# スキルレビューの実行
# 対象スキルのディレクトリパスを指定
```

### コードのレビュー

`reviewing-ts-code` スキルを使用してコード品質を評価：

```bash
# コードレビューの実行
# 対象ファイルまたはディレクトリを指定
```

## プラグインのインストールと利用

マーケットプレース追加:
```bash
/plugin marketplace add interactive-inc/claude-plugins
```

プラグインインストール:
```bash
/plugin install review-skills@interactive-claude-plugins
/plugin install jobantenna@interactive-claude-plugins
```

## 注意事項

- すべてのスキルとエージェントは日本語で記述されています
- ベストプラクティスに厳格に準拠していますが、プロジェクト固有の事情も考慮します
- スキルの Progressive Disclosure により、コンテキストウィンドウを効率的に使用します
