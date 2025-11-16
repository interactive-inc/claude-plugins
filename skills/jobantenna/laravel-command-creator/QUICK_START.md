# Quick Start Guide

このガイドでは、Laravel Artisan コマンドを素早く作成するための手順を説明します。すべての手順には検証ステップとエラーハンドリングが含まれています。

## Creating a Basic Command

### ステップ1: テンプレートのコピー

```bash
cp assets/templates/BasicCommand.php app/Console/Commands/YourCommand.php
```

**検証**: ファイルが正しくコピーされたことを確認
```bash
ls -l app/Console/Commands/YourCommand.php
```

**エラー時**: ディレクトリが存在しない場合は作成
```bash
mkdir -p app/Console/Commands
```

### ステップ2: クラス名とシグネチャの更新

- クラス名を変更（例: `YourCommand`）
- `$signature` を更新（例: `job-antenna:your-command`）
- `$description` を更新

**検証**: PHPの構文エラーがないことを確認
```bash
php -l app/Console/Commands/YourCommand.php
```

**期待される出力**: `No syntax errors detected in app/Console/Commands/YourCommand.php`

**エラー時**: エラーメッセージに従って構文を修正

### ステップ3: handle()メソッドの実装

ビジネスロジックを実装します。

**検証**: コマンドが登録されていることを確認
```bash
php artisan list | grep your-command
```

**期待される出力**: `job-antenna:your-command` が表示される

**エラー時**:
- Artisanキャッシュをクリア: `php artisan clear-compiled`
- クラス名とファイル名が一致していることを確認

### ステップ4: Dry-runテスト

```bash
php artisan job-antenna:your-command --dry-run
```

**検証**: 期待される出力を確認
- "Dry-run mode: No actual processing" が表示される
- エラーメッセージが表示されない

**エラー時**:
- エラーメッセージを確認
- `handle()` メソッドをデバッグ
- ログファイルを確認: `storage/logs/laravel.log`

---

## Creating a Service-Integrated Command

### ステップ1: テンプレートのコピー

```bash
cp assets/templates/ServiceIntegrationCommand.php app/Console/Commands/YourServiceCommand.php
```

**検証**: ファイルが存在することを確認
```bash
test -f app/Console/Commands/YourServiceCommand.php && echo "File exists" || echo "File not found"
```

### ステップ2: サービスクラスの作成

```bash
php artisan make:class Services/YourService
```

**検証**: サービスクラスが作成されたことを確認
```bash
ls -l app/Services/YourService.php
```

### ステップ3: handle()のサービス依存を更新

```php
public function handle(YourService $service): int
{
    try {
        $this->info('Start: Processing');
        $service->process();
        $this->info('Done: Processing');
        return Command::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage(), [
            'throwable' => $e,
            'class'     => __CLASS__,
        ]);
        return Command::FAILURE;
    }
}
```

**検証**: 依存注入が正しく動作することを確認
```bash
php artisan job-antenna:your-service-command --dry-run
```

**エラー時**:
- サービスクラスが正しくバインドされているか確認
- `app/Providers/AppServiceProvider.php` でバインディングを確認

### ステップ4: サービスメソッドの実装

サービスクラスにビジネスロジックを実装します。

**検証**: ユニットテストで動作確認
```bash
phpunit --filter=YourServiceTest
```

---

## Creating a Batch Processing Command

### ステップ1: テンプレートのコピー

```bash
cp assets/templates/BatchProcessingCommand.php app/Console/Commands/YourBatchCommand.php
```

### ステップ2: モデルとクエリロジックの更新

対象モデルとクエリ条件を更新します。

**検証**: クエリが正しく動作することを確認（Tinkerで実行）
```bash
php artisan tinker
> YourModel::where('condition', 'value')->count();
```

### ステップ3: 処理ロジックをサービスに実装

バッチ処理のロジックをサービスクラスに実装します。

**検証**: 小さい `--limit` でテスト実行
```bash
php artisan job-antenna:your-batch-command --limit=10 --dry-run
```

**期待される出力**:
- プログレスバーが表示される
- 10件のレコードが処理される
- エラーがない

**エラー時**:
- エラーレコードのIDと内容を確認
- ログファイルで詳細を確認

### ステップ4: 本番実行前の最終確認

```bash
# 少数レコードで本番実行
php artisan job-antenna:your-batch-command --limit=100
```

**検証**:
- 処理が完了すること
- データベースの変更を確認
- エラーサマリーがあれば確認

---

## Setting Up Scheduled Execution

### ステップ1: スケジュールパターンの追加

`app/Console/Kernel.php` にスケジュールを追加:

```php
protected function schedule(Schedule $schedule)
{
    $this->scheduleTemplate($schedule, 'your-command')
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

**検証**: スケジュールリストで確認
```bash
php artisan schedule:list
```

**期待される出力**:
- コマンドがリストに表示される
- 実行時刻が正しい
- タイムゾーンが `Asia/Tokyo`

### ステップ2: タイムゾーンとコールバックの設定

上記のテンプレートに従って設定します。

**検証**: 手動でスケジュール実行をテスト
```bash
php artisan schedule:run
```

### ステップ3: スケジュール実行のテスト

```bash
# 次回実行までの時間を確認
php artisan schedule:list

# デバッグモードで実行
php artisan schedule:work --verbose
```

**エラー時**:
- cronが正しく設定されているか確認
- ログファイルでエラー詳細を確認

---

## Creating a Long-Running Command

### ステップ1: テンプレートのコピー

```bash
cp assets/templates/LongRunningCommand.php app/Console/Commands/YourWorkerCommand.php
```

### ステップ2: バッチ処理ロジックの実装

`processNextBatch()` メソッドを実装します。

**検証**: 1バッチだけ実行してテスト
```bash
php artisan job-antenna:your-worker-command --limit=1
```

### ステップ3: シグナルハンドラの追加

`trap()` メソッドでシグナルハンドリングを実装済み。

**検証**: グレースフルシャットダウンのテスト
```bash
# 別ターミナルでコマンドを起動
php artisan job-antenna:your-worker-command

# プロセスIDを確認
ps aux | grep your-worker-command

# SIGTERM を送信
kill -TERM <pid>
```

**期待される出力**:
- "Received signal 15, shutting down gracefully..." が表示される
- 現在のバッチが完了してから終了する

**エラー時**:
- シグナルハンドラが登録されているか確認
- `$shouldKeepRunning` フラグの制御を確認

---

## Creating an Isolated Command

### ステップ1: テンプレートのコピー

```bash
cp assets/templates/IsolatableCommand.php app/Console/Commands/YourIsolatedCommand.php
```

### ステップ2: Isolatableインターフェースの実装

テンプレートには既に実装済み。必要に応じてカスタマイズ:

```php
public function isolatableId(): string
{
    return $this->getName();
}

public function isolationLockExpiresAt(): ?\DateTimeInterface
{
    return now()->addHour();
}
```

**検証**: 並行実行防止のテスト
```bash
# ターミナル1
php artisan job-antenna:your-isolated-command

# ターミナル2（すぐに実行）
php artisan job-antenna:your-isolated-command
```

**期待される出力**:
- ターミナル2で "Another instance is already running" が表示される
- 2つ目のインスタンスが実行されない

### ステップ3: isolatableId()のカスタマイズ（必要に応じて）

特定の引数に基づいて分離したい場合:

```php
public function isolatableId(): string
{
    return $this->getName() . '-' . $this->option('date');
}
```

**検証**: 異なる引数で並行実行可能なことを確認
```bash
# ターミナル1
php artisan job-antenna:your-isolated-command --date=2024-01-01

# ターミナル2
php artisan job-antenna:your-isolated-command --date=2024-01-02
```

**期待される出力**: 両方のインスタンスが実行される

---

## Best Practices

### 実行前の確認事項

1. **Dry-runテスト**: 必ず `--dry-run` で動作確認
2. **小規模テスト**: `--limit` オプションで少数レコードをテスト
3. **ログ確認**: `storage/logs/laravel.log` でエラーを確認
4. **構文チェック**: `php -l` でPHP構文エラーを確認

### デバッグ方法

1. **詳細ログ**: `-v`, `-vv`, `-vvv` オプションで詳細度を上げる
2. **Tinker**: `php artisan tinker` でクエリをテスト
3. **dd()**: `dd($variable)` で変数の内容を確認
4. **ログ出力**: `$this->info()` や `$this->error()` で状況を記録

### エラー発生時の対処

1. **エラーメッセージを読む**: 具体的な問題を特定
2. **ログファイルを確認**: `storage/logs/laravel.log`
3. **スタックトレースを確認**: エラー発生箇所を特定
4. **ドキュメント参照**: `references/command-patterns.md` で詳細を確認

---

## 次のステップ

- **テストの作成**: `TESTING_GUIDE.md` を参照してユニットテストとインテグレーションテストを作成
- **詳細パターンの確認**: `references/command-patterns.md` で高度なパターンを確認
- **テンプレートのカスタマイズ**: プロジェクト固有の要件に合わせてテンプレートを調整
