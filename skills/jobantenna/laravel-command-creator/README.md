# Laravel Command Creator

Laravel Artisan コマンドを本番環境で実証済みのパターンと Laravel 9+ ベストプラクティスに従って実装するための包括的なスキルです。

## 概要

このスキルは、JobAntenna v4 モノレポプロジェクトから抽出された実戦的なパターンと、Laravel 公式ドキュメントのベストプラクティスを統合し、高品質な Artisan コマンドの実装をサポートします。


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

## 主な機能

- **即利用可能なテンプレート**: 7つのコマンドテンプレートと3つのテストテンプレートを提供
- **実証済みパターン**: 本番環境で検証された8つのコアパターン
- **包括的なドキュメント**: クイックスタートガイド、詳細リファレンス、テストガイド
- **ベストプラクティス準拠**: Laravel 9+ の公式推奨に完全準拠
- **エラーハンドリング**: 階層的エラー処理戦略とグレースフルシャットダウン
- **大規模データ処理**: cursor() とジェネレータを使用したメモリ効率的な実装

## このスキルを使用するタイミング

以下のような場合に使用してください：

- バッチ処理、データメンテナンス、スケジュールタスク用の新しい Artisan コマンドを作成する
- 大規模データセットを効率的に処理するコマンドを実装する
- サービスクラスとコンソールコマンドを統合する
- Kernel.php でスケジュール実行を設定する
- コマンド全体でエラーハンドリングとログを標準化する
- dry-run 機能を持つコマンドを構築する
- グレースフルシャットダウンを持つ長時間実行ワーカーを作成する
- 複数サーバー間でコマンドの並行実行を防止する

## ディレクトリ構造

```
laravel-command-creator/
├── README.md                    # このファイル
├── SKILL.md                     # スキル定義とパターン概要
├── QUICK_START.md               # ステップバイステップガイド
├── TESTING_GUIDE.md             # テストパターンとベストプラクティス
├── assets/
│   └── templates/               # コマンドテンプレート
│       ├── Command.php          # カスタムベースコマンドクラス
│       ├── BasicCommand.php     # シンプルなコマンドテンプレート
│       ├── ServiceIntegrationCommand.php
│       ├── BatchProcessingCommand.php
│       ├── ScheduledCommand.php
│       ├── LongRunningCommand.php
│       ├── IsolatableCommand.php
│       └── tests/               # テストテンプレート
│           ├── BasicCommandTest.php
│           ├── BatchProcessingCommandTest.php
│           └── IntegrationCommandTest.php
├── references/
│   └── command-patterns.md      # 詳細な実装リファレンス
└── scripts/                     # ヘルパースクリプト
```

## クイックスタート

### 1. 基本的なコマンドの作成

```bash
# テンプレートをコピー
cp assets/templates/BasicCommand.php app/Console/Commands/YourCommand.php

# クラス名とシグネチャを更新
# handle() メソッドにビジネスロジックを実装

# テスト実行
php artisan job-antenna:your-command --dry-run
```

### 2. サービス統合コマンドの作成

```bash
# テンプレートをコピー
cp assets/templates/ServiceIntegrationCommand.php app/Console/Commands/YourServiceCommand.php

# サービスクラスを作成
php artisan make:class Services/YourService

# 依存注入を設定してテスト
php artisan job-antenna:your-service-command --dry-run
```

### 3. バッチ処理コマンドの作成

```bash
# テンプレートをコピー
cp assets/templates/BatchProcessingCommand.php app/Console/Commands/YourBatchCommand.php

# モデルとクエリロジックを更新
# 小規模データでテスト
php artisan job-antenna:your-batch-command --limit=10 --dry-run
```

詳細な手順は [QUICK_START.md](./QUICK_START.md) を参照してください。

## 利用可能なテンプレート

### コマンドテンプレート

| テンプレート | 説明 | 主な機能 |
|------------|------|---------|
| **Command.php** | カスタムベースコマンドクラス | 統一ログ（コンソール+ファイル）、コンストラクタインジェクション対応 |
| **BasicCommand.php** | シンプルなコマンド | Dry-run サポート、基本的なエラーハンドリング |
| **ServiceIntegrationCommand.php** | サービス統合コマンド | 依存注入、サービスクラスとの連携 |
| **BatchProcessingCommand.php** | 大規模データ処理 | cursor()、プログレスバー、エラー収集、report() ヘルパー |
| **ScheduledCommand.php** | スケジュールタスク | 日付処理、Asia/Tokyo タイムゾーン対応 |
| **LongRunningCommand.php** | 長時間実行ワーカー | シグナルハンドリング、グレースフルシャットダウン |
| **IsolatableCommand.php** | 並行実行防止コマンド | Isolatable インターフェース、複数サーバー対応 |

### テストテンプレート

| テンプレート | 説明 |
|------------|------|
| **BasicCommandTest.php** | サービスモック、dry-run テスト、オプション検証 |
| **BatchProcessingCommandTest.php** | ジェネレータ/yield パターン、テーブル出力、日付パース |
| **IntegrationCommandTest.php** | ファクトリ、DB アサーション、通知テスト |

## ドキュメント

- **[SKILL.md](./SKILL.md)**: スキル定義と8つのコアパターンの概要
- **[QUICK_START.md](./QUICK_START.md)**: 検証ステップとエラーハンドリングを含む段階的ガイド
- **[TESTING_GUIDE.md](./TESTING_GUIDE.md)**: テストパターン、ベストプラクティス、トラブルシューティング
- **[references/command-patterns.md](./references/command-patterns.md)**: 詳細な実装リファレンスとコード例

## コアパターン

### 1. カスタムベースコマンドクラス
コンソールとログファイルの両方に出力する統一ログを提供します。

### 2. サービスクラス統合
テスタビリティと再利用性のために、ビジネスロジックをサービスクラスに委譲します。

### 3. 大規模データ処理
`cursor()` とジェネレータを使用してメモリ使用量を最小化します。

### 4. Dry-Run モード
実際の実行前に変更を安全にプレビューする `--dry-run` オプションを提供します。

### 5. スケジュール実行
タイムゾーン、コールバック、コマンドチェーンを使用した `app/Console/Kernel.php` のスケジュール実行を標準化します。

### 6. エラーハンドリング戦略
弾力性のあるバッチ処理のための階層的エラーハンドリングを実装します。

### 7. シグナルハンドリング
長時間実行コマンドのグレースフルシャットダウンのために OS シグナル（SIGTERM、SIGQUIT）を処理します。

### 8. 並行実行防止
`Isolatable` インターフェースを使用して、サーバー間で複数のインスタンスが同時実行されるのを防ぎます。

## デフォルト設定と規約

### プロジェクト構造

```
app/
├── Console/
│   ├── Command.php              # カスタムベースコマンドクラス
│   ├── Commands/                # コマンドクラス
│   └── Kernel.php               # スケジュール定義
├── Services/                    # ビジネスロジックサービス
└── Models/                      # Eloquent モデル
```

### 命名規約

- **コマンド**: `job-antenna:{name}` または `{project-prefix}:{name}`
- **サービスクラス**: `{Name}Service` (例: `PointCalculationService`)
- **テンプレート**: 説明的な名前 (例: `BatchProcessingCommand.php`)

### 共通オプション

- `--dry-run`: 実行せずに変更をプレビュー
- `--limit=N`: 処理するレコード数を制限
- `--date=YYYY/MM/DD`: 対象日付を指定

### タイムゾーン

日付/時刻操作では常に **Asia/Tokyo** タイムゾーンを明示的に使用します：

```php
CarbonImmutable::parse($input, 'Asia/Tokyo')->startOfDay()
```

### リターンコード

- 成功: `Command::SUCCESS` (または `0`)
- 失敗: `Command::FAILURE` (または `1`)

## ベストプラクティス

### 実行前の確認事項

1. **Dry-run テスト**: 必ず `--dry-run` で動作確認
2. **小規模テスト**: `--limit` オプションで少数レコードをテスト
3. **ログ確認**: `storage/logs/laravel.log` でエラーを確認
4. **構文チェック**: `php -l` で PHP 構文エラーを確認

### デバッグ方法

1. **詳細ログ**: `-v`, `-vv`, `-vvv` オプションで詳細度を上げる
2. **Tinker**: `php artisan tinker` でクエリをテスト
3. **dd()**: `dd($variable)` で変数の内容を確認
4. **ログ出力**: `$this->info()` や `$this->error()` で状況を記録

## 使用例

### 例1: ポイント計算バッチコマンド

```php
class CalculatePointsCommand extends Command
{
    protected $signature = 'job-antenna:calculate-points
                            {--dry-run : Preview without execution}
                            {--limit= : Limit number of records}';

    public function handle(PointCalculationService $service): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry-run mode: No actual processing');
            return Command::SUCCESS;
        }

        $query = User::where('points_calculated_at', '<', now()->subDay());

        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }

        $users = $query->cursor();
        $bar = $this->output->createProgressBar($query->count());

        foreach ($users as $user) {
            try {
                $service->calculatePoints($user);
                $bar->advance();
            } catch (\Throwable $e) {
                report($e);
                $this->error("Failed for user {$user->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        return Command::SUCCESS;
    }
}
```

### 例2: スケジュール設定

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $this->scheduleTemplate($schedule, 'calculate-points')
        ->dailyAt('1:00')
        ->onOneServer();
}

private function scheduleTemplate(Schedule $schedule, string $command): Event
{
    return $schedule->command("job-antenna:{$command}")
        ->timezone('Asia/Tokyo')
        ->onOneServer()
        ->onSuccess(fn () => $this->getNoticeLogger()->info("Success: {$command}"))
        ->onFailure(fn () => $this->getNoticeLogger()->warning("Failure: {$command}"));
}
```

## トラブルシューティング

### コマンドが登録されない

```bash
# Artisan キャッシュをクリア
php artisan clear-compiled

# クラス名とファイル名が一致していることを確認
```

### 依存注入が動作しない

```bash
# サービスプロバイダーでバインディングを確認
# app/Providers/AppServiceProvider.php
```

### メモリ不足エラー

```php
// get() の代わりに cursor() を使用
$users = User::where('active', true)->cursor(); // ✓ Good
$users = User::where('active', true)->get();    // ✗ Bad
```

## ライセンス

このスキルは JobAntenna v4 プロジェクトのパターンに基づいており、Laravel の公式ベストプラクティスに準拠しています。

## サポート

問題や質問がある場合は、プロジェクトのドキュメントを参照するか、開発チームにお問い合わせください。

---

**関連リソース**:
- [Laravel 公式ドキュメント - Artisan Console](https://laravel.com/docs/artisan)
- [Laravel 公式ドキュメント - Task Scheduling](https://laravel.com/docs/scheduling)
- [Laravel 公式ドキュメント - Eloquent ORM](https://laravel.com/docs/eloquent)
