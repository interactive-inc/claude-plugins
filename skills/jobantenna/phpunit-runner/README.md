# JobAntenna PHPUnit Test Runner

JobAntenna の Laradock ベース Docker 環境で PHPUnit テストを実行する専門スキルです。

## 概要

このスキルは、JobAntenna プロジェクトの Laradock Docker 環境内で PHPUnit テストを非同期実行するための特化したワークフローを提供します。時間のかかるテスト実行を専用のテストランナーエージェントを通じて処理し、メイン会話をブロックしないように最適化されています。

## インストール

### 前提条件

- Claude Code CLI がインストールされている
- JobAntenna プロジェクト環境（Laradock）が稼働している

### マーケットプレースの追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

```bash
/plugin install jobantenna@interactive-claude-plugins
```

## 主な機能

- **非同期テスト実行**: 専用エージェントによる時間のかかるテスト実行
- **Docker 環境統合**: Laradock workspace コンテナでのシームレスな実行
- **柔軟なテスト選択**: 特定のクラス、ファイル、またはすべてのテストを実行可能
- **詳細な結果レポート**: テスト数、成功/失敗ステータス、エラー詳細
- **バックグラウンド実行**: メイン会話をブロックせずにテスト実行

## このスキルを使用するタイミング

ユーザーが以下のようなリクエストをした場合にこのスキルを起動します：

- "PHPUnit テストを実行して"
- "UserTest を実行"
- "ApplicationTest のテストを走らせて"
- "Application モデルをテスト"
- "すべてのユニットテストを実行"
- "tests/Unit/Models/ApplicationTest.php を実行"

### 使用例

```
ユーザー: "UserTest を実行してください"
→ スキルが起動し、phpunit-test-runner エージェントを使用してテストを実行
```

## ファイル構成

```
skills/jobantenna/phpunit-runner/
├── README.md                      # このファイル
├── SKILL.md                       # スキル定義とワークフロー
├── agents/
│   └── phpunit-test-runner.md     # PHPUnit テスト実行専門エージェント
└── references/
    └── docker-environment.md      # Docker 環境設定と詳細
```

## コアワークフロー

### 1. テストスコープの特定

ユーザーリクエストに基づいて実行するテストを決定：

- **特定のテストクラス**: `--filter=UserTest`
- **特定のファイル**: `tests/Unit/Models/ApplicationTest.php`
- **すべてのテスト**: フィルタなしで実行

### 2. テストランナーエージェントの起動

Task ツールを使用して `phpunit-test-runner` エージェント（`agents/phpunit-test-runner.md` で定義）をテスト仕様とともに起動：

```
Task tool:
- subagent_type: "jobantenna:phpunit-runner:phpunit-test-runner"
- description: "Run PHPUnit tests: [test-name]"
- prompt: "Execute PHPUnit tests with the following specification: [filter or file path]"
```

エージェントは以下を実行：
1. 適切な Docker コマンドの構築
2. workspace コンテナでのテスト実行
3. 実行進捗の監視
4. 結果の解析とレポート生成

### 3. 結果のレポート

エージェントが完了したら、テスト結果をユーザーに要約：
- 実行されたテスト数
- 成功/失敗ステータス
- 発生したエラーや失敗

## Docker 環境詳細

JobAntenna プロジェクトは Docker コンテナ化に Laradock を使用しています。重要な詳細は `references/docker-environment.md` にドキュメント化されており、テストランナーエージェントが参照します。

### クイックリファレンス

- **コンテナ**: workspace
- **作業ディレクトリ**: `/var/www`（server/ にマッピング）
- **PHPUnit パス**: `./vendor/bin/phpunit`
- **PHPUnit バージョン**: 9.6.16
- **Laradock 場所**: プロジェクトルートから相対的に `../laradock`

## テスト実行コマンド

テストランナーエージェントは以下のコマンドを使用します：

### Docker Compose 経由（推奨）

```bash
cd /path/to/laradock
docker-compose exec workspace bash -c "./vendor/bin/phpunit [options]"
```

### 一般的なオプション

- `--filter=TestClassName` - 特定のテストクラスを実行
- `tests/Unit/SomeTest.php` - 特定のテストファイルを実行
- `--version` - PHPUnit バージョンを確認

## 使用例

### 例1: 特定のテストクラスの実行

**ユーザー**: "UserTest を実行して"

**処理フロー**:
1. スコープ特定: `--filter=UserTest`
2. エージェント起動: Task ツールで phpunit-test-runner、プロンプト "Execute PHPUnit tests with filter: UserTest"
3. エージェント完了待機
4. レポート: "UserTest から 43 個のテストを実行しました。すべてのテストが正常に合格しました。"

### 例2: 特定のファイルのテスト実行

**ユーザー**: "tests/Unit/Models/ApplicationTest.php を実行してください"

**処理フロー**:
1. スコープ特定: `tests/Unit/Models/ApplicationTest.php`
2. エージェント起動: ファイルパスを指定
3. 結果レポート: "ApplicationTest から 28 個のテストを実行しました。26 個が合格、2 個が失敗しました。"

### 例3: すべてのテストの実行

**ユーザー**: "すべてのユニットテストを実行して"

**処理フロー**:
1. スコープ特定: フィルタなし、すべてのテスト
2. エージェント起動: フィルタオプションなし
3. 結果レポート: "合計 1,234 個のテストを実行しました。1,230 個が合格、4 個が失敗しました。"

## トラブルシューティング

### Docker コンテナが起動していない

```bash
# Laradock ディレクトリに移動
cd ../laradock

# コンテナを起動
docker-compose up -d workspace
```

### PHPUnit が見つからない

```bash
# Composer の依存関係をインストール
docker-compose exec workspace bash -c "composer install"
```

### テストが失敗する

```bash
# 詳細な出力でテストを実行
docker-compose exec workspace bash -c "./vendor/bin/phpunit --verbose"

# 特定のテストのみをデバッグ
docker-compose exec workspace bash -c "./vendor/bin/phpunit --filter=YourTest --debug"
```

### パーミッションエラー

```bash
# workspace コンテナのユーザーを確認
docker-compose exec workspace whoami

# 必要に応じてファイルの所有者を変更
docker-compose exec workspace chown -R laradock:laradock /var/www
```

## ベストプラクティス

1. **小規模テストから開始**: 大規模なテストスイートの前に、まず小さなテストセットで検証
2. **フィルタを使用**: 開発中は `--filter` オプションで関連するテストのみを実行
3. **定期的なクリーンアップ**: テストデータベースを定期的にクリーンアップ
4. **CI/CD との統合**: 本番環境では継続的インテグレーションパイプラインでテストを実行

## 高度な使用法

### テストデータベースの準備

```bash
# テスト用データベースをマイグレーション
docker-compose exec workspace bash -c "php artisan migrate --env=testing"

# テストデータのシード
docker-compose exec workspace bash -c "php artisan db:seed --env=testing"
```

### カバレッジレポートの生成

```bash
# コードカバレッジレポートを生成
docker-compose exec workspace bash -c "./vendor/bin/phpunit --coverage-html coverage"
```

### 並列テスト実行

```bash
# ParaTest を使用した並列実行（インストールされている場合）
docker-compose exec workspace bash -c "./vendor/bin/paratest"
```

## エージェントの詳細

### phpunit-test-runner エージェント

詳細は [agents/phpunit-test-runner.md](./agents/phpunit-test-runner.md) を参照してください。

このエージェントは以下を処理します：
- Docker コマンドの構築
- テスト実行の監視
- 出力のパース
- 結果レポートの生成

## リファレンス

### Docker 環境

詳細な Docker 環境設定とトラブルシューティングガイドは [references/docker-environment.md](./references/docker-environment.md) を参照してください。

## よくある質問

**Q: テスト実行にどのくらい時間がかかりますか？**
A: テストの数と複雑さによります。エージェントはバックグラウンドで実行されるため、メイン会話はブロックされません。

**Q: 複数のテストファイルを同時に実行できますか？**
A: はい、フィルタなしで実行するか、`--filter` で複数のパターンを指定できます。

**Q: テストが失敗した場合、詳細なエラー情報を取得できますか？**
A: はい、エージェントは失敗したテストの詳細なエラーメッセージを含むレポートを提供します。

**Q: CI/CD パイプラインでこのスキルを使用できますか？**
A: このスキルは対話的な開発用に設計されています。CI/CD には直接 PHPUnit コマンドを使用することを推奨します。

## 関連リソース

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Laradock Documentation](https://laradock.io/)

---

このスキルにより、JobAntenna プロジェクトで効率的かつ非同期に PHPUnit テストを実行できます。
