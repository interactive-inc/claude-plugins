# Testing Guide

このガイドでは、Laravel Artisan コマンドの包括的なテスト方法を説明します。すべてのテストパターンには検証ステップとエラーハンドリングが含まれています。

## Overview

コマンドのテストは以下の3つのレベルで実施します：

1. **ユニットテスト**: サービスをモックしてコマンドロジックを分離テスト
2. **インテグレーションテスト**: 実際のデータベースとサービスで動作確認
3. **エンドツーエンドテスト**: 本番に近い環境での完全な実行テスト

---

## Unit Testing with Service Mocking

### 基本的なコマンド実行テスト

**テストコード**:
```php
use Mockery\MockInterface;
use Tests\TestCase;

class YourCommandTest extends TestCase
{
    /**
     * @test
     */
    public function commandExecutesSuccessfully()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn(['count' => 10]);
        });

        $this->artisan('job-antenna:your-command')
            ->assertExitCode(0);
    }
}
```

**検証**: テストが成功すること
```bash
phpunit --filter=commandExecutesSuccessfully
```

**期待される出力**:
```
OK (1 test, 2 assertions)
```

**エラー時**:
- **Mock未設定エラー**: `shouldReceive()` が呼ばれていることを確認
- **Exit code不一致**: コマンド内のエラーハンドリングを確認
- **Assertion失敗**: モックの期待値とコマンドの動作を確認

### Dry-runオプションのテスト

**テストコード**:
```php
/**
 * @test
 */
public function commandWithDryRunOption()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->withArgs(function ($dryRun) {
                return $dryRun === true;
            })
            ->once()
            ->andReturn(['count' => 0]);
    });

    $this->artisan('job-antenna:your-command --dry-run')
        ->assertExitCode(0);
}
```

**検証**: Dry-runフラグが正しく渡されること
```bash
phpunit --filter=commandWithDryRunOption
```

**期待される出力**:
- テストがパス
- サービスに `dryRun=true` が渡される

**エラー時**:
- **引数不一致**: `withArgs()` の条件を確認
- **フラグ未渡し**: コマンドのオプション定義を確認

---

## Testing Batch Processing Commands

### Generator/Yieldパターンのテスト

**テストコード**:
```php
/**
 * @test
 */
public function commandProcessesGeneratorResults()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processRecords')
            ->andReturn((function () {
                yield ['id' => '1', 'name' => 'Test'];
                yield ['id' => '2', 'name' => 'Another'];
            })());
    });

    $this->artisan('job-antenna:batch-processing')
        ->expectsTable(['id', 'name'], [
            ['1', 'Test'],
            ['2', 'Another'],
        ])
        ->assertExitCode(0);
}
```

**検証**: ジェネレータが正しく処理されること
```bash
phpunit --filter=commandProcessesGeneratorResults
```

**期待される出力**:
- テーブル出力が期待通り
- すべてのレコードが処理される

**エラー時**:
- **テーブル不一致**: `expectsTable()` の期待値を確認
- **Generator未処理**: コマンド内のforeachループを確認
- **型エラー**: ジェネレータの返却値の型を確認

### 大量データ処理のテスト

**テストコード**:
```php
/**
 * @test
 */
public function commandProcessesLargeDataset()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processRecords')
            ->andReturn((function () {
                for ($i = 1; $i <= 1000; $i++) {
                    yield ['id' => (string)$i, 'status' => 'processed'];
                }
            })());
    });

    $this->artisan('job-antenna:batch-processing --limit=1000')
        ->assertExitCode(0);
}
```

**検証**: メモリ使用量が制限内であること
```bash
# メモリリミット付きで実行
php -d memory_limit=128M vendor/bin/phpunit --filter=commandProcessesLargeDataset
```

**期待される出力**:
- テストがパス
- メモリエラーが発生しない

**エラー時**:
- **メモリ不足**: `chunk()` や `lazy()` を使用してメモリ効率を改善
- **タイムアウト**: `--timeout` オプションを追加

---

## Testing Date/Time Arguments

### 日付引数の解析テスト

**テストコード**:
```php
use Carbon\CarbonImmutable;

/**
 * @test
 */
public function commandParsesDateArgument()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processForDate')
            ->withArgs(function ($date) {
                return CarbonImmutable::parse('2024-01-15', 'Asia/Tokyo')
                    ->startOfDay()
                    ->equalTo($date);
            })
            ->once();
    });

    $this->artisan('job-antenna:your-command --date=2024/01/15')
        ->assertExitCode(0);
}
```

**検証**: 日付が正しくパースされること
```bash
phpunit --filter=commandParsesDateArgument
```

**期待される出力**:
- テストがパス
- 日付が Asia/Tokyo タイムゾーンで解析される

**エラー時**:
- **日付形式エラー**: コマンドの日付パース処理を確認
- **タイムゾーン不一致**: `CarbonImmutable::parse()` の第2引数を確認
- **比較失敗**: `startOfDay()` が適用されているか確認

### 日付範囲のテスト

**テストコード**:
```php
/**
 * @test
 */
public function commandProcessesDateRange()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processForDateRange')
            ->withArgs(function ($startDate, $endDate) {
                $start = CarbonImmutable::parse('2024-01-01', 'Asia/Tokyo')->startOfDay();
                $end = CarbonImmutable::parse('2024-01-31', 'Asia/Tokyo')->endOfDay();

                return $start->equalTo($startDate) && $end->equalTo($endDate);
            })
            ->once();
    });

    $this->artisan('job-antenna:your-command --start-date=2024/01/01 --end-date=2024/01/31')
        ->assertExitCode(0);
}
```

**検証**: 日付範囲が正しく処理されること
```bash
phpunit --filter=commandProcessesDateRange
```

**エラー時**:
- **範囲エラー**: 開始日と終了日の順序を確認
- **境界値エラー**: `startOfDay()` と `endOfDay()` の使用を確認

---

## Integration Testing with Factories

### データベース状態変更のテスト

**テストコード**:
```php
use App\Models\YourModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourCommandIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function commandProcessesRecordsSuccessfully()
    {
        // Arrange: Create test data
        $models = YourModel::factory(3)->create(['status' => 'pending']);

        // Act: Execute command
        $this->artisan('job-antenna:your-command')
            ->assertSuccessful();

        // Assert: Verify database state
        $models->each(function ($model) {
            $this->assertDatabaseHas('your_table', [
                'id'     => $model->id,
                'status' => 'processed',
            ]);
        });
    }
}
```

**検証**: データベースの状態が期待通りであること
```bash
phpunit --filter=commandProcessesRecordsSuccessfully
```

**期待される出力**:
- すべてのレコードのステータスが `processed` に更新される
- テストがパス

**エラー時**:
- **Factory未定義**: `YourModelFactory` が存在するか確認
- **データ未更新**: コマンドの処理ロジックを確認
- **トランザクションエラー**: `RefreshDatabase` トレイトが使用されているか確認

### リレーションを含むテスト

**テストコード**:
```php
/**
 * @test
 */
public function commandProcessesRecordsWithRelations()
{
    // Arrange: Create models with relations
    $parent = ParentModel::factory()
        ->has(ChildModel::factory()->count(3), 'children')
        ->create();

    // Act: Execute command
    $this->artisan('job-antenna:your-command --parent-id=' . $parent->id)
        ->assertSuccessful();

    // Assert: Verify relations are processed
    $parent->children->each(function ($child) {
        $this->assertDatabaseHas('children', [
            'id'        => $child->id,
            'processed' => true,
        ]);
    });
}
```

**検証**: リレーションが正しく処理されること
```bash
phpunit --filter=commandProcessesRecordsWithRelations
```

**エラー時**:
- **N+1問題**: `with()` を使ってEager Loadingしているか確認
- **リレーション未定義**: モデルのリレーション定義を確認

---

## Testing Notifications

### 通知送信のテスト

**テストコード**:
```php
use Illuminate\Support\Facades\Notification;

/**
 * @test
 */
public function commandSendsNotifications()
{
    Notification::fake();

    $this->artisan('job-antenna:your-command')
        ->assertSuccessful();

    Notification::assertTimesSent(
        2,
        YourNotification::class
    );
}
```

**検証**: 通知が期待通り送信されること
```bash
phpunit --filter=commandSendsNotifications
```

**期待される出力**:
- 通知が2回送信される
- テストがパス

**エラー時**:
- **通知未送信**: コマンド内の通知処理を確認
- **送信回数不一致**: 通知トリガー条件を確認

### 特定ユーザーへの通知テスト

**テストコード**:
```php
/**
 * @test
 */
public function commandNotifiesSpecificUser()
{
    Notification::fake();

    $user = User::factory()->create();

    $this->artisan('job-antenna:your-command --user-id=' . $user->id)
        ->assertSuccessful();

    Notification::assertSentTo(
        $user,
        YourNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels)
    );
}
```

**検証**: 特定ユーザーに通知が送信されること
```bash
phpunit --filter=commandNotifiesSpecificUser
```

**エラー時**:
- **ユーザー未通知**: ユーザー取得処理を確認
- **チャンネル不一致**: 通知クラスの `via()` メソッドを確認

---

## Test Organization and Best Practices

### テストファイルの構成

**推奨ディレクトリ構造**:
```
tests/
├── Unit/
│   └── Console/
│       └── Commands/
│           ├── YourCommandTest.php          # ユニットテスト
│           └── AnotherCommandTest.php
├── Feature/
│   └── Console/
│       └── Commands/
│           ├── YourCommandIntegrationTest.php  # インテグレーションテスト
│           └── AnotherCommandIntegrationTest.php
└── Helpers/
    └── CommandTestCase.php                  # テスト用ベースクラス
```

**検証**: ディレクトリ構造が正しいこと
```bash
tree tests/
```

### BaseTestCaseの作成

**コード例**:
```php
namespace Tests\Helpers;

use Tests\TestCase;

abstract class CommandTestCase extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // コマンド共通のセットアップ処理
        $this->withoutMockingConsoleOutput();
    }

    /**
     * Assert that command output contains text.
     */
    protected function assertOutputContains(string $text): void
    {
        $this->assertTrue(
            str_contains($this->artisan()->output(), $text),
            "Output does not contain: {$text}"
        );
    }
}
```

**検証**: BaseTestCaseが使用可能であること
```bash
phpunit --filter=CommandTestCase
```

### テストのグルーピング

**アノテーション例**:
```php
/**
 * @test
 * @group commands
 * @group batch-processing
 */
public function commandProcessesBatchSuccessfully()
{
    // テストコード
}
```

**検証**: グループ別にテストを実行
```bash
# バッチ処理のテストのみ実行
phpunit --group=batch-processing

# コマンド全体のテスト実行
phpunit --group=commands
```

**エラー時**:
- **グループ未認識**: `@group` アノテーションが正しく記述されているか確認

---

## Common Testing Patterns

### 1. エラーケースのテスト

**パターン**:
```php
/**
 * @test
 */
public function commandFailsWhenServiceThrowsException()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->andThrow(new \RuntimeException('Service error'));
    });

    $this->artisan('job-antenna:your-command')
        ->assertExitCode(1);
}
```

**検証**: エラーハンドリングが正しいこと
```bash
phpunit --filter=commandFailsWhenServiceThrowsException
```

### 2. オプション組み合わせのテスト

**パターン**:
```php
/**
 * @test
 * @dataProvider optionCombinationsProvider
 */
public function commandHandlesOptionCombinations(array $options, int $expectedExitCode)
{
    $command = 'job-antenna:your-command';
    foreach ($options as $key => $value) {
        $command .= " --{$key}={$value}";
    }

    $this->artisan($command)
        ->assertExitCode($expectedExitCode);
}

public function optionCombinationsProvider(): array
{
    return [
        'with dry-run' => [
            ['dry-run' => true],
            0
        ],
        'with limit' => [
            ['limit' => 100],
            0
        ],
        'with both' => [
            ['dry-run' => true, 'limit' => 100],
            0
        ],
    ];
}
```

**検証**: すべてのオプション組み合わせが動作すること
```bash
phpunit --filter=commandHandlesOptionCombinations
```

### 3. プログレスバー表示のテスト

**パターン**:
```php
/**
 * @test
 */
public function commandDisplaysProgressBar()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processRecords')
            ->andReturn((function () {
                for ($i = 1; $i <= 10; $i++) {
                    yield ['id' => (string)$i];
                }
            })());
    });

    $this->artisan('job-antenna:batch-processing')
        ->expectsOutput('Processing records...')
        ->assertExitCode(0);
}
```

---

## Troubleshooting Tests

### よくある問題と対処法

#### 1. Mock未呼び出しエラー

**エラーメッセージ**:
```
Mockery\Exception\InvalidCountException: Method shouldReceive("process")
from Mockery was expected to be called 1 times but called 0 times.
```

**対処法**:
- コマンド内でサービスが実際に呼ばれているか確認
- 依存注入が正しく設定されているか確認
- `shouldReceive()` のメソッド名が正しいか確認

#### 2. データベーステスト失敗

**エラーメッセージ**:
```
Failed asserting that a row in the table [your_table] matches the attributes...
```

**対処法**:
```php
// デバッグ用にデータベースの状態を確認
$this->artisan('job-antenna:your-command');
dd(YourModel::all()->toArray());
```

#### 3. Artisan出力確認失敗

**エラーメッセージ**:
```
Output "Expected text" not found in actual output.
```

**対処法**:
```php
// 実際の出力を確認
$this->artisan('job-antenna:your-command');
dump($this->artisan()->output());
```

---

## Best Practices

### テスト作成時の推奨事項

1. **`@test` アノテーションを使用**: メソッド名に `test` プレフィックス不要
2. **サービスをモック**: コマンドロジックを分離してテスト
3. **すべてのオプションをテスト**: `--dry-run`, `--limit`, カスタムオプション
4. **成功と失敗の両方をテスト**: 正常系と異常系の両方をカバー
5. **Factoryを使用**: テストデータの生成を統一
6. **`setUp()` でクリーンアップ**: 各テストが独立して実行可能に
7. **`@group` でグルーピング**: 関連テストをまとめて実行可能に
8. **Dry-runを別テスト**: 実行とDry-runを分けて検証

### テスト実行のワークフロー

```bash
# 1. すべてのテストを実行
phpunit

# 2. 特定のテストファイルのみ実行
phpunit tests/Unit/Console/Commands/YourCommandTest.php

# 3. 特定のテストメソッドのみ実行
phpunit --filter=commandExecutesSuccessfully

# 4. グループ別に実行
phpunit --group=commands

# 5. カバレッジレポート生成
phpunit --coverage-html coverage/
```

---

## Testing Templates

テストテンプレートは `assets/templates/tests/` ディレクトリに配置されています。

- `BasicCommandTest.php` - 基本的なコマンドテスト
- `ServiceIntegrationCommandTest.php` - サービス統合テスト
- `BatchProcessingCommandTest.php` - バッチ処理テスト
- `CommandIntegrationTest.php` - インテグレーションテスト

これらのテンプレートをコピーして、プロジェクト固有のテストを作成してください。

---

## 次のステップ

- **CI/CD統合**: GitHub ActionsやCircleCIでテストを自動実行
- **カバレッジ向上**: PHPUnit Coverage Reportで未テスト箇所を特定
- **パフォーマンステスト**: 大量データでの実行時間を計測
- **E2Eテスト**: 本番に近い環境での完全なテスト実施
