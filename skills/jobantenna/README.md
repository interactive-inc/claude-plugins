# JobAntenna Skills

JobAntenna プロジェクト固有の開発支援スキルを提供するプラグインです。

## 概要

このプラグインは、JobAntenna プロジェクトで培われた実証済みのパターンとベストプラクティスを Claude Code で活用できるようにします。Laravel を中心としたモダンな Web 開発における品質と効率性を向上させるための専門スキルを提供します。

## インストール

### 前提条件

Claude Code CLI がインストールされている必要があります。

### マーケットプレースの追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

```bash
/plugin install jobantenna@interactive-claude-plugins
```

## 含まれるスキル

### laravel-command-creator

Laravel Artisan コマンドを本番環境で実証済みのパターンと Laravel 9+ ベストプラクティスに従って実装するスキル。

**主な機能:**
- バッチ処理コマンドのパターン
- スケジュールタスクの実装
- データメンテナンススクリプト
- 長時間実行ワーカーのシグナルハンドリング
- 大量データ処理の効率的な実装
- サービスクラスとの統合
- エラー耐性のある実装
- 並行実行防止
- 標準化されたログ出力

**使用タイミング:**
- 新しい Artisan コマンドを作成する時
- バッチ処理や定期タスクを実装する時
- 大量データを効率的に処理する必要がある時
- 本番環境での運用を考慮したコマンドが必要な時

**詳細:** [laravel-command-creator/SKILL.md](./laravel-command-creator/SKILL.md)
