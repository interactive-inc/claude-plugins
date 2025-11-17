# Laravel Artisan Commands: Good and Bad Examples

このドキュメントでは、Laravel Artisan コマンド実装における良い例と悪い例を対比して示します。

---

## 目次

1. [コマンド構造の例](#コマンド構造の例)
2. [サービス統合の例](#サービス統合の例)
3. [エラーハンドリングの例](#エラーハンドリングの例)
4. [大量データ処理の例](#大量データ処理の例)
5. [Dry-runモードの例](#dry-runモードの例)
6. [ネーミングの例](#ネーミングの例)
7. [スケジュール実行の例](#スケジュール実行の例)
8. [テストの例](#テストの例)

---

## コマンド構造の例

### ❌ 悪い例: すべてをhandle()に詰め込む

```php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessUsersCommand extends Command
{
    protected $signature = 'process-users';

    public function handle(): int
    {
        // すべてのロジックがhandle()に詰め込まれている
        $users = \App\Models\User::where('status', 'pending')->get();

        foreach ($users as $user) {
            // ビジネスロジックがコマンドに直接記述
            $user->points = $user->points + 100;
            $user->status = 'processed';
            $user->save();

            // メール送信もコマンドに直接記述
            \Mail::to($user->email)->send(new \App\Mail\UserProcessed($user));
        }

        return 0;
    }
}
```

**問題点**:
- ビジネスロジックがコマンドに密結合
- テストが困難
- コードの再利用ができない
- 責任が分離されていない

### ✅ 良い例: サービスクラスへの委譲

```php
<?php
namespace App\Console\Commands;

use App\Console\Command;
use App\Services\UserProcessingService;

class ProcessUsersCommand extends Command
{
    protected $signature = 'job-antenna:process-users
                            {--dry-run : Preview changes}
                            {--limit=100 : Limit records}';
    protected $description = 'Process pending users';

    public function handle(UserProcessingService $service): int
    {
        try {
            $this->info('Start: Processing users');

            $dryRun = $this->option('dry-run');
            $limit = (int) $this->option('limit');

            $result = $service->processUsers($dryRun, $limit);

            $this->info("Processed: {$result['processed']} users");
            if (!empty($result['errors'])) {
                $this->table(['User ID', 'Error'], $result['errors']);
            }

            $this->info('Done: Processing users');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), [
                'throwable' => $e,
                'class'     => __CLASS__,
            ]);
            return Command::FAILURE;
        }
    }
}
```

**良い点**:
- ビジネスロジックがサービスに分離
- テスト可能
- コマンドとサービスで責任が明確
- Dry-runとlimitオプション対応

---

## サービス統合の例

### ❌ 悪い例: サービスを使わない

```php
public function handle(): int
{
    // データベースクエリがコマンドに直接記述
    $records = DB::table('users')
        ->where('status', 'pending')
        ->where('created_at', '>', now()->subDays(7))
        ->get();

    // ビジネスロジックがコマンドに記述
    foreach ($records as $record) {
        $points = $this->calculatePoints($record);
        DB::table('user_points')->insert([
            'user_id' => $record->id,
            'points' => $points,
            'created_at' => now(),
        ]);
    }

    return 0;
}

private function calculatePoints($record)
{
    // 複雑なビジネスロジック
    return $record->visits * 10 + $record->purchases * 100;
}
```

**問題点**:
- ビジネスロジックがコマンドに密結合
- テストが困難
- 他のコマンドやコントローラーで再利用できない

### ✅ 良い例: ジェネレーターを使ったサービス統合

```php
// Command
public function handle(PointCalculationService $service): int
{
    try {
        $this->info('Start: Calculating points');

        $count = $service->countTargetUsers();
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $processed = 0;
        $errors = [];

        foreach ($service->processUsers() as $result) {
            $progressBar->advance();
            if ($result['success']) {
                $processed++;
            } else {
                $errors[] = [
                    'user_id' => $result['user_id'],
                    'error' => $result['error'],
                ];
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Processed: {$processed} users");
        if (!empty($errors)) {
            $this->table(['User ID', 'Error'], $errors);
        }

        return Command::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage(), ['throwable' => $e]);
        return Command::FAILURE;
    }
}

// Service
class PointCalculationService
{
    public function countTargetUsers(): int
    {
        return User::where('status', 'pending')->count();
    }

    public function processUsers(): \Generator
    {
        $users = User::where('status', 'pending')->cursor();

        foreach ($users as $user) {
            try {
                $points = $this->calculatePoints($user);
                UserPoint::create([
                    'user_id' => $user->id,
                    'points' => $points,
                ]);

                yield [
                    'success' => true,
                    'user_id' => $user->id,
                ];
            } catch (\Throwable $e) {
                yield [
                    'success' => false,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    private function calculatePoints(User $user): int
    {
        return $user->visits * 10 + $user->purchases * 100;
    }
}
```

**良い点**:
- ビジネスロジックがサービスに分離
- ジェネレーターでメモリ効率的
- プログレスバー表示
- エラーを収集して続行
- テスト可能

---

## エラーハンドリングの例

### ❌ 悪い例: エラーで即座に終了

```php
public function handle(): int
{
    $users = User::all();

    foreach ($users as $user) {
        // エラーが発生すると即座に終了
        $this->processUser($user);
    }

    return 0;
}

private function processUser(User $user): void
{
    // エラーハンドリングなし
    $user->points = $user->points + 100;
    $user->save();
}
```

**問題点**:
- 1つのエラーで全体が停止
- エラーログがない
- エラーサマリーがない

### ✅ 良い例: Skip and Continue パターン

```php
public function handle(): int
{
    try {
        $this->info('Start: Processing users');

        $users = User::cursor();
        $processed = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $this->processUser($user);
                $processed++;
            } catch (\Throwable $e) {
                // エラーをログに記録し、処理を続行
                $errors[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];
                $this->error("Error processing user {$user->id}: {$e->getMessage()}");

                // report() でLaravelの例外ハンドラーに送信
                report($e);

                continue;
            }
        }

        // サマリー表示
        $this->info("Successfully processed: {$processed} users");
        if (!empty($errors)) {
            $this->warn("Errors: " . count($errors));
            $this->table(['User ID', 'Error'], $errors);
        }

        return Command::SUCCESS;
    } catch (\Throwable $e) {
        $this->error('Fatal error', ['throwable' => $e]);
        return Command::FAILURE;
    }
}
```

**良い点**:
- 個別エラーで停止しない
- エラーを収集してサマリー表示
- `report()` で集中ログ管理
- 致命的エラーは外側のcatchで処理

---

## 大量データ処理の例

### ❌ 悪い例: get()で全件取得

```php
public function handle(): int
{
    // メモリに全レコードをロード
    $users = User::all();

    foreach ($users as $user) {
        $this->processUser($user);
    }

    return 0;
}
```

**問題点**:
- メモリ使用量が莫大
- 100万レコードで Out of Memory
- プログレスバーがない

### ✅ 良い例: cursor()とプログレスバー

```php
public function handle(): int
{
    try {
        $this->info('Start: Processing users');

        $count = User::count();
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $processed = 0;
        $errors = [];

        // cursor()で1レコードずつメモリ効率的にロード
        User::cursor()->each(function (User $user) use ($progressBar, &$processed, &$errors) {
            try {
                $progressBar->advance();
                $this->processUser($user);
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];
            }
        });

        $progressBar->finish();
        $this->newLine();

        $this->info("Processed: {$processed} users");
        if (!empty($errors)) {
            $this->table(['User ID', 'Error'], $errors);
        }

        return Command::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage(), ['throwable' => $e]);
        return Command::FAILURE;
    }
}
```

**良い点**:
- `cursor()` でメモリ効率的
- プログレスバー表示
- エラー収集して続行
- サマリー表示

---

## Dry-runモードの例

### ❌ 悪い例: Dry-runなし

```php
public function handle(): int
{
    $users = User::where('status', 'pending')->get();

    foreach ($users as $user) {
        // いきなり本番実行
        $user->status = 'processed';
        $user->save();
    }

    return 0;
}
```

**問題点**:
- 実行前にプレビューできない
- 誤った条件で実行するリスク

### ✅ 良い例: Dry-runモード実装

```php
protected $signature = 'job-antenna:process-users
                        {--dry-run : Preview changes}';

public function handle(): int
{
    $dryRun = $this->option('dry-run');

    $targets = User::where('status', 'pending')->get();

    if ($dryRun) {
        // Dry-runモード: プレビュー表示のみ
        $this->table(
            ['User ID', 'Email', 'Action'],
            $targets->map(fn($u) => [
                $u->id,
                $u->email,
                'Would mark as processed',
            ])
        );

        $this->info('Dry-run mode: No actual changes made');
        $this->info("Total affected users: {$targets->count()}");

        return Command::SUCCESS;
    }

    // 本番実行
    foreach ($targets as $user) {
        $user->status = 'processed';
        $user->save();
    }

    $this->info("Processed: {$targets->count()} users");
    return Command::SUCCESS;
}
```

**良い点**:
- 実行前にプレビュー可能
- 安全な確認手段
- テーブル形式で見やすい

---

## ネーミングの例

### ❌ 悪い例: 曖昧で不統一な命名

```php
// コマンド名が曖昧
protected $signature = 'process';  // 何を処理するのか不明
protected $signature = 'user';     // アクションが不明
protected $signature = 'do-task';  // 具体性がない

// プレフィックスなし
protected $signature = 'calculate-points';  // プロジェクト識別不可

// 日付引数の形式が不統一
protected $signature = 'process-users {date}';           // 形式不明
protected $signature = 'send-report {start} {end}';      // 意味不明確
```

**問題点**:
- コマンドの目的が不明確
- プロジェクト識別不可
- 日付形式が曖昧

### ✅ 良い例: 明確で統一された命名

```php
// プロジェクトプレフィックス付き
protected $signature = 'job-antenna:process-users
                        {--dry-run : Preview changes}
                        {--limit=100 : Limit records to process}
                        {--date=yesterday : Target date (YYYY/MM/DD or "yesterday")}';

// サービスクラス名も明確
class UserProcessingService { }
class PointCalculationService { }

// 日付処理も統一
use Carbon\CarbonImmutable;

$date = CarbonImmutable::parse(
    $this->option('date'),
    'Asia/Tokyo'
)->startOfDay();
```

**良い点**:
- プロジェクト名で識別可能
- アクションと対象が明確
- オプションの説明が詳細
- タイムゾーンが明示的

---

## スケジュール実行の例

### ❌ 悪い例: 設定が分散

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // タイムゾーンがバラバラ
    $schedule->command('command1')->daily();
    $schedule->command('command2')->timezone('UTC')->daily();
    $schedule->command('command3')->timezone('Asia/Tokyo')->daily();

    // 成功/失敗のログなし
    $schedule->command('important-task')->daily();

    // 重複実行の防止なし
    $schedule->command('process-data')->everyMinute();
}
```

**問題点**:
- タイムゾーンが不統一
- ログがない
- 重複実行のリスク

### ✅ 良い例: テンプレートメソッドで統一

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // テンプレートメソッドで統一的にスケジュール
    $this->scheduleTemplate($schedule, 'process-users')
        ->dailyAt('1:00')
        ->onOneServer();

    // コマンドチェーン
    $this->scheduleTemplate($schedule, 'calculate-points')
        ->dailyAt('2:00')
        ->after($this->createRunCommands(
            'update-rankings',
            'send-notifications'
        ));

    // 週次実行
    $this->scheduleTemplate($schedule, 'send-weekly-report')
        ->weekly()
        ->tuesdays()
        ->at('8:00');
}

private function scheduleTemplate(Schedule $schedule, string $command): Event
{
    return $schedule->command("job-antenna:{$command}")
        ->timezone('Asia/Tokyo')           // 統一タイムゾーン
        ->onOneServer()                    // 重複防止
        ->onSuccess(fn () => $this->getNoticeLogger()->info("Success: {$command}"))
        ->onFailure(fn () => $this->getNoticeLogger()->warning("Failure: {$command}"));
}

private function createRunCommands(string ...$commands): Closure
{
    return fn () => collect($commands)->each(
        fn ($cmd) => $this->call("job-antenna:{$cmd}")
    );
}
```

**良い点**:
- タイムゾーン統一
- 成功/失敗ログ自動記録
- 重複実行防止
- コマンドチェーン対応

---

## テストの例

### ❌ 悪い例: モックなしの実行テスト

```php
class ProcessUsersCommandTest extends TestCase
{
    public function test_command_runs()
    {
        // 実際にデータベースを変更してしまう
        $this->artisan('process-users')
            ->assertExitCode(0);
    }
}
```

**問題点**:
- 実際のデータベースに影響
- テスト結果が不安定
- ビジネスロジックの検証ができない

### ✅ 良い例: サービスをモックしたテスト

```php
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessUsersCommandTest extends TestCase
{
    /**
     * @test
     */
    public function commandExecutesSuccessfully()
    {
        $this->mock(UserProcessingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processUsers')
                ->once()
                ->with(false, 100)  // dry-run=false, limit=100
                ->andReturn([
                    'processed' => 10,
                    'errors' => [],
                ]);
        });

        $this->artisan('job-antenna:process-users --limit=100')
            ->assertExitCode(0)
            ->expectsOutput('Start: Processing users')
            ->expectsOutput('Processed: 10 users')
            ->expectsOutput('Done: Processing users');
    }

    /**
     * @test
     */
    public function commandWithDryRunOption()
    {
        $this->mock(UserProcessingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processUsers')
                ->once()
                ->with(true, 100)  // dry-run=true
                ->andReturn([
                    'processed' => 0,
                    'errors' => [],
                ]);
        });

        $this->artisan('job-antenna:process-users --dry-run --limit=100')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commandHandlesServiceException()
    {
        $this->mock(UserProcessingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processUsers')
                ->once()
                ->andThrow(new \RuntimeException('Service error'));
        });

        $this->artisan('job-antenna:process-users')
            ->assertExitCode(1);  // FAILURE
    }
}
```

**良い点**:
- サービスをモックして分離テスト
- オプションの動作を検証
- エラーケースもテスト
- 出力メッセージを検証

---

## まとめ

### 良いコマンドの特徴

1. **明確な命名**: プロジェクトプレフィックス + アクション + 対象
2. **サービス分離**: ビジネスロジックはサービスクラスに
3. **エラーハンドリング**: Skip and Continue パターン
4. **メモリ効率**: `cursor()` とジェネレーター使用
5. **Dry-runモード**: 安全なプレビュー機能
6. **プログレスバー**: 長時間処理の進捗表示
7. **統一スケジュール**: テンプレートメソッドで一貫性
8. **テスト可能**: サービスモックで分離テスト

### 避けるべき反パターン

1. ❌ ビジネスロジックをコマンドに直接記述
2. ❌ `get()` や `all()` で全件取得
3. ❌ エラーで即座に終了
4. ❌ Dry-runモードなし
5. ❌ 曖昧な命名
6. ❌ タイムゾーン未指定
7. ❌ ログなし、進捗表示なし
8. ❌ テストなし

これらの例を参考に、保守性と信頼性の高いコマンドを実装してください。
