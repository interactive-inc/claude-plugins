# Laravel Artisan Command Implementation Patterns

This reference documents proven patterns for implementing Laravel Artisan commands based on the JobAntenna v4 project.

## Table of Contents

1. [Command Categories](#command-categories)
2. [Base Structure](#base-structure)
3. [Service Class Integration](#service-class-integration)
4. [Large Data Processing](#large-data-processing)
5. [Error Handling](#error-handling)
6. [Scheduled Execution](#scheduled-execution)
7. [User Interaction](#user-interaction)
8. [Testing Considerations](#testing-considerations)
9. [Signal Handling for Long-Running Commands](#signal-handling-for-long-running-commands)
10. [Preventing Concurrent Execution](#preventing-concurrent-execution)
11. [Testing Commands](#testing-commands)

---

## Command Categories

### Data Maintenance & Batch Processing
- File cleanup for deleted users/companies
- Address data correction
- Location coordinate updates
- Statistics aggregation

### Scheduled Tasks
- Point/level calculations (daily)
- Ranking generation (weekly)
- Follow company notifications
- Weekly reports
- Analytics data sync

### Data Generation & Export
- XML feed generation for external services
- Sitemap generation
- Content archiving

### Data Synchronization
- CSV imports from external sources
- Temporary to permanent record conversion

### Development Support
- GraphQL type generation
- Code scaffolding

---

## Base Structure

### Custom Base Class

All commands extend `App\Console\Command` for unified logging:

```php
<?php
namespace App\Console;

use Illuminate\Console\Command as LaravelCommand;
use Psr\Log\LoggerInterface;

abstract class Command extends LaravelCommand
{
    protected $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // Auto-log to both console and file
    public function info($string, $verbosity = null)
    {
        parent::info($string, $verbosity);
        if ($this->logger) {
            $this->logger->info($string);
        }
    }

    public function error($string, $verbosity = null, array $context = null): void
    {
        if (is_array($verbosity) && is_null($context)) {
            $context   = $verbosity;
            $verbosity = null;
        }
        parent::error($string, $verbosity);
        if ($this->logger) {
            $this->logger->error($string, $context ?? []);
        }
    }
}
```

### Standard Command Template

```php
<?php
namespace App\Console\Commands;

use App\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'job-antenna:example
                            {argument : Description}
                            {--option : Description}
                            {--dry-run : Simulate without executing}';

    protected $description = 'Command description';

    /**
     * Dependency Injection via handle() method
     */
    public function handle(SomeService $service): int
    {
        try {
            $this->info('Start: Command processing');

            // Business logic delegated to service
            $service->process();

            $this->info('Done: Command processing');
            return 0; // SUCCESS
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), [
                'throwable' => $e,
                'class'     => __CLASS__,
            ]);
            return 1; // FAILURE
        }
    }
}
```

### Naming Conventions

- **Command name**: `job-antenna:{name}` prefix
- **Return values**: 0 (success), 1 (failure)
- **Common options**: `--dry-run` for simulation
- **Timezone**: Always use `Asia/Tokyo`

---

## Service Class Integration

### Pattern 1: DI via handle() Parameters

```php
public function handle(
    AntennaLevelService $antennaLevelService,
    ClassAndTableNamesConvertService $convertService
): int {
    $targetModels = $this->argument('targetModels');
    $targetModels = $convertService->tableNamesToClassNames($targetModels);

    $this->info('Start: calculate antenna level');
    $antennaLevelService->calculate($targetModels);
    $this->info('Done: calculate antenna level');

    return 0;
}
```

### Pattern 2: Generator for Memory Efficiency

```php
public function handle(SaveScreenPageViewsService $service): int
{
    $table = [];

    // Service returns Generator
    foreach ($service->run(
        dry: $this->option('dry-run'),
        start: CarbonImmutable::parse($this->option('start-date'), 'Asia/Tokyo'),
        end: CarbonImmutable::parse($this->option('end-date'), 'Asia/Tokyo'),
    ) as $i => $row) {
        $table[] = $row;

        // Output every 100 rows (memory optimization)
        if ($i % 100 == 99) {
            $this->table(array_keys($table[0]), $table);
            $table = [];
        }
    }

    if (!empty($table)) {
        $this->table(array_keys($table[0]), $table);
    }

    return 0;
}
```

### Pattern 3: Service with Detailed Business Logic

```php
// Service class handles conversion logic
class ConvertTempApplicationService
{
    public function __construct(
        private readonly ApplicationEvent $applicationEvent,
        private readonly TempApplicationFailedEvent $tempApplicationFailedEvent,
    ) {}

    public function processPendingApplications(User $user): void
    {
        $pendingTempApplications = $user->tempApplications()
            ->status(TempApplicationManageStatus::PENDING)
            ->get();

        if ($pendingTempApplications->isEmpty()) {
            return;
        }

        if (!$this->isVerifiedUser($user)) {
            return;
        }

        $pendingTempApplications->each(function (TempApplication $tempApplication) {
            $status = $this->handleApplicationConvert($tempApplication);
            $manage = $tempApplication->manage;
            $manage->status = $status;
            $manage->save();
            $this->dispatchTempApplicationStatusEvent($tempApplication);
        });
    }

    public function handleApplicationConvert(
        TempApplication $tempApplication
    ): TempApplicationManageStatus {
        $joboffer = $tempApplication->joboffer;

        if (is_null($joboffer) || $joboffer->trashed()) {
            return TempApplicationManageStatus::JOB_NOT_FOUND;
        }

        if ($joboffer->status !== Joboffer::STATUS_PUBLISH) {
            return TempApplicationManageStatus::JOB_POSTING_EXPIRED;
        }

        $application = $this->createApplication($tempApplication);
        if (!$application) {
            return TempApplicationManageStatus::DUPLICATE_APPLICATION;
        }

        return TempApplicationManageStatus::COMPLETED;
    }
}
```

---

## Large Data Processing

### Pattern 1: cursor() for Efficient Iteration

```php
Company::withTrashed()
    ->cursor()  // Memory-efficient iteration
    ->each(function (Company $company) {
        $stat = $company->stat;

        // Multiple aggregations
        $stat->count_footprints = FootprintJoboffer::query()
            ->whereIn('joboffer_id', $idsQuery)
            ->count();

        $stat->save();
    });
```

### Pattern 2: Batched Table Output

```php
$table = [];

foreach ($service->run(...) as $i => $row) {
    $table[] = $row;

    // Output every 100 rows
    if ($i % 100 == 99) {
        $this->table(array_keys($table[0]), $table);
        $table = [];
    }
}

if (!empty($table)) {
    $this->table(array_keys($table[0]), $table);
}
```

### Pattern 3: CSV Processing with Generator

```php
private function each(string $path): \Generator
{
    return CsvHelper::each($path, [
        '連番',
        '法人番号',
        '処理区分',
        // ... column definitions
    ]);
}

public function handle(KanaNormalizeDirective $kanaNormalize): int
{
    $bar = $this->output->createProgressBar();

    foreach ($this->each($fullPath) as $i => $record) {
        $file = $record['SPLFILEOBJECT'];
        if ($i <= 0) {
            $bar->start($file->getSize());
        }
        $bar->setProgress($file->ftell() ?: 0);

        $data = $this->recordMap($record, $kanaNormalize);
        if (!$dryRun) {
            $this->merge($data);
        }
    }

    $bar->finish();
    return 0;
}
```

### Pattern 4: Limit Option for Sampling

```php
$query = User::onlyTrashed()->limit($this->option('limit'));

if ($userIds) {
    $query->whereIn('id', $userIds);
}

$query->whereHas('attachments');
$users = $query->select(['id'])->get();
```

---

## Error Handling

### Pattern 1: Basic try-catch

```php
try {
    $this->info('Start: processing');
    // Processing
    $this->info('Done: processing');
    return 0;
} catch (\Throwable $e) {
    $this->error($e->getMessage(), [
        'throwable' => $e,
        'class'     => __CLASS__,
    ]);
    return 1;
}
```

### Pattern 2: Partial Error Handling (Skip and Continue)

```php
$badAddresses = [];

foreach ($cursor as $address) {
    try {
        $addressInfo = $improveAddressService->parse(
            $address->region . $address->locality . $address->street
        );
        $address->update($addressInfo);
    } catch (ImproveAddressException $_) {
        // Skip and continue
        $badAddresses[] = $address->toArray();
        continue;
    }
}

// Display invalid data later
if ($badAddresses) {
    $this->table([...], $badAddresses);
}
```

### Pattern 3: Hierarchical Error Handling

```php
try {
    foreach ($cursor as $address) {
        try {
            $progressBar->advance();
            $location = $improveLocationService->fetch($fullAddress);
            $address->location = new Point($location['lat'], $location['lng']);
            $address->save();
        } catch (RequestException $e) {
            // Skip specific exceptions only
            if ($skipZeroResults && $e->response->json('status') === 'ZERO_RESULTS') {
                $this->error('Skip: ZERO_RESULTS id: ' . $address->id);
                continue;
            }
            throw $e;  // Re-throw others
        }
    }
} catch (\Throwable $e) {
    $this->error('Error: IMPROVE LOCATION', ['throwable' => $e]);
} finally {
    $progressBar->finish();
}
```

---

## Scheduled Execution

### Unified Template in Kernel.php

```php
protected function schedule(Schedule $schedule)
{
    // Daily execution + chained commands
    $this->scheduleTemplate($schedule, 'save-screen-page-views')
        ->dailyAt('1:00')
        ->after($this->createRunCommands(...[
            'point',
            'update-stat-page-views',
            'level',
            'sitemap',
            'create-joboffer-data',
        ]));

    // Weekly execution with regional conditions
    $this->scheduleTemplate($schedule, 'ranking')
        ->weekly()
        ->mondays()
        ->at('8:30')
        ->after($this->createRunCommands(...(
            match (config('jobantenna.region')) {
                'hokkaido' => ['save-content --url=feed/doshinapp'],
                default    => [],
            }
        )));
}
```

### Schedule Template Method

```php
private function scheduleTemplate(Schedule $schedule, string $command): Event
{
    static $logChannel;
    $logChannel ??= config('jobantenna.notice_log_channel');

    return $schedule->command("job-antenna:{$command}")
        ->timezone('Asia/Tokyo')           // Unified timezone
        ->onOneServer()                    // Prevent duplicate execution
        ->onSuccess(fn () => $this->getNoticeLogger()->info("Success: {$command}"))
        ->onFailure(fn () => $this->getNoticeLogger()->warning("Failure: {$command}"));
}

private function createRunCommands(string ...$commands): Closure
{
    return fn () => collect($commands)->each(fn ($command) => $this->runCommand($command));
}
```

### Schedule Features

- Explicit timezone (`Asia/Tokyo`)
- Multi-server duplicate prevention
- Success/failure callbacks
- Command chaining (after)
- Regional conditional execution

---

## User Interaction

### Pattern 1: Confirmation Dialog

```php
if (!$this->confirm('Continue?')) {
    return Command::SUCCESS;
}
```

### Pattern 2: Table Output

```php
private function outputTable(\Illuminate\Support\Collection $users): void
{
    $this->info("Target users: {$users->count()}");
    $this->line('');

    $tableData = $users->map(function ($user) {
        $pendingCount = $user->tempApplications->count();
        return [
            $user->id,
            $user->email,
            $pendingCount,
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    })->toArray();

    $this->table(
        ['ID', 'Email', 'Pending Count', 'Created At'],
        $tableData
    );
}
```

### Pattern 3: CSV Output

```php
private function outputCsv(\Illuminate\Support\Collection $users): void
{
    $this->line('id,email,pending_count,created_at');
    foreach ($users as $user) {
        $pendingCount = $user->tempApplications->count();
        $row = [
            $user->id,
            $user->email,
            $pendingCount,
            $user->created_at->format('Y-m-d H:i:s'),
        ];
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $row);
        rewind($fp);
        $csvLine = rtrim(stream_get_contents($fp), "\n\r");
        fclose($fp);
        $this->line($csvLine);
    }
}
```

### Pattern 4: Progress Bar

```php
$count = $builder->count();
$progressBar = $this->output->createProgressBar($count);
$progressBar->start();

foreach ($cursor as $item) {
    $progressBar->advance();
    // Processing
}

$progressBar->finish();
```

### Pattern 5: Multiple Output Formats

```php
protected $signature = 'job-antenna:list
                        {--format=table : Output format (table|ids|csv)}';

public function handle(): int
{
    $format = $this->option('format');
    $allowedFormats = ['table', 'ids', 'csv'];

    if (!in_array($format, $allowedFormats, true)) {
        $this->error("Invalid format: {$format}");
        $this->info('Available formats: ' . implode(', ', $allowedFormats));
        return Command::FAILURE;
    }

    switch ($format) {
        case 'ids':
            $this->outputIds($data);
            break;
        case 'csv':
            $this->outputCsv($data);
            break;
        case 'table':
        default:
            $this->outputTable($data);
            break;
    }
}
```

---

## Testing Considerations

### Pattern 1: Extract Helper Methods

```php
public function handle(ConvertTempApplicationService $service): int
{
    $dryRun = $this->option('dry-run');

    // Query logic extracted to separate command
    $listCommand = new ListFailedConversionUsersCommand();
    $users = $listCommand->getEligibleUsers();  // Reusable

    if ($users->isEmpty()) {
        $this->info('No users to process');
        return Command::SUCCESS;
    }

    // Processing
}
```

### Pattern 2: Extract Query Builders (Testable)

```php
private function queryUsersWithPendingApplications(): Builder
{
    return User::whereHas('tempApplications', function ($query) {
        $query->whereHas('manage', function ($q) {
            $q->where('status', TempApplicationManageStatus::PENDING);
        });
    });
}

private function queryEligibleUsers(): Builder
{
    return $this->queryUsersWithPendingApplications()
        ->whereNotNull('password')
        ->whereNotNull('email_verified_at');
}

public function getEligibleUsers(): Collection
{
    return $this->queryEligibleUsers()
        ->with(['tempApplications'])
        ->orderBy('id')
        ->get();
}
```

### Pattern 3: Business Logic in Service Classes

```php
class ConvertTempApplicationService
{
    // Service responsibilities:
    // - Conversion logic
    // - Event dispatching
    // - Password reset checks

    public function processPendingApplications(User $user): void { }
    public function handleApplicationConvert(TempApplication $tempApplication): TempApplicationManageStatus { }
    private function createApplication(TempApplication $tempApplication): Application|null { }
    public function needsPasswordReset(User $user): bool { }
}
```

### Pattern 4: Dry-Run Validation

```php
if ($dryRun) {
    $pendingApps = $user->tempApplications()
        ->whereHas('manage', function ($q) {
            $q->where('status', TempApplicationManageStatus::PENDING);
        })
        ->with(['joboffer', 'manage'])
        ->get();

    foreach ($pendingApps as $tempApp) {
        $joboffer = $tempApp->joboffer;

        // Display validation results
        if (is_null($joboffer) || $joboffer->trashed()) {
            $this->line("      → Job not found (JOB_NOT_FOUND)");
        } elseif ($joboffer->status !== Joboffer::STATUS_PUBLISH) {
            $this->line("      → Job not published (status: {$joboffer->status}) (JOB_POSTING_EXPIRED)");
        } else {
            $this->line("      → Convertible (COMPLETED)");
        }
    }
    continue;
}
```

---

## Project-Specific Design Conventions

### 1. Command Name Prefix
- All commands use `job-antenna:` prefix
- Consistent in Kernel.php schedule definitions

### 2. Unified Error Handling
```php
catch (\Throwable $e) {
    $this->error($e->getMessage(), ['throwable' => $e, 'class' => __CLASS__]);
    return 1;  // Failure
}
```

### 3. Return Values
- Success: `0` or `Command::SUCCESS`
- Failure: `1` or `Command::FAILURE`

### 4. Unified Logging
- `$this->info()` - Start/end logs
- `$this->error()` - Error logs (auto-logged to file)
- `$this->warn()` - Warning logs
- Scheduled execution logs to `notice_log_channel`

### 5. Dry-Run Option
Many batch commands provide `--dry-run` option to preview targets before execution

### 6. Date/Time Parameter Standardization
```php
$date = Carbon::parse($this->option('date'), 'Asia/Tokyo');
$date = CarbonImmutable::parse($this->option('start-date'), 'Asia/Tokyo')->startOfDay();
```
Always use `Asia/Tokyo` timezone

### 7. Pagination/Limit Options
- `--limit` option to restrict processing count
- Use `cursor()` for memory efficiency with large datasets

---

## Signal Handling for Long-Running Commands

Long-running worker-style commands should handle OS signals gracefully to allow for controlled shutdown.

### Pattern: Graceful Shutdown with trap()

```php
<?php
namespace App\Console\Commands;

use App\Console\Command;
use App\Services\QueueWorkerService;

class LongRunningWorker extends Command
{
    protected $signature = 'job-antenna:worker
                            {--timeout=3600 : Maximum runtime in seconds}';

    protected $description = 'Long-running worker with graceful shutdown';

    private bool $shouldKeepRunning = true;

    public function handle(QueueWorkerService $service): int
    {
        $this->info('Worker started');

        // Register signal handlers
        $this->trap([SIGTERM, SIGQUIT], function (int $signal) {
            $this->shouldKeepRunning = false;
            $this->warn("Received signal {$signal}, shutting down gracefully...");
        });

        $timeout = (int) $this->option('timeout');
        $startTime = time();

        while ($this->shouldKeepRunning) {
            // Check timeout
            if (time() - $startTime >= $timeout) {
                $this->info('Timeout reached, shutting down');
                break;
            }

            // Process next batch
            $hasWork = $service->processNextBatch();

            if (!$hasWork) {
                $this->info('No work available, shutting down');
                break;
            }

            // Small delay to prevent tight loop
            usleep(100000); // 100ms
        }

        $this->info('Worker stopped gracefully');
        return Command::SUCCESS;
    }
}
```

### When to Use

- Queue workers or background processors
- Long-running daemon-like commands
- Commands that need graceful shutdown on deployment
- Background tasks that process data continuously

### Key Features

- `trap([SIGTERM, SIGQUIT], callback)` - Register signal handlers
- `$shouldKeepRunning` flag - Control loop execution
- Timeout protection - Prevent infinite loops
- Graceful cleanup - Finish current work before exit

### Testing Signal Handling

```bash
# Start the worker
php artisan job-antenna:worker &
WORKER_PID=$!

# Send termination signal
kill -TERM $WORKER_PID

# Worker should log "Received signal 15, shutting down gracefully..."
```

**Template:** See `assets/templates/LongRunningCommand.php`

---

## Preventing Concurrent Execution

Use the `Isolatable` interface to prevent multiple instances of a command from running simultaneously across multiple servers.

### Pattern: Isolatable Interface

```php
<?php
namespace App\Console\Commands;

use App\Console\Command;
use App\Services\DataImportService;
use Illuminate\Contracts\Console\Isolatable;

class ImportDataCommand extends Command implements Isolatable
{
    protected $signature = 'job-antenna:import-data
                            {--source= : Data source file path}';

    protected $description = 'Import data from external source (prevents concurrent execution)';

    public function handle(DataImportService $service): int
    {
        try {
            $this->info('Start: Data import');

            $source = $this->option('source');

            if (!$source) {
                $this->error('Source file path is required');
                return Command::FAILURE;
            }

            // This code won't execute if another instance is running
            $result = $service->importFromFile($source);

            $this->info("Imported: {$result['count']} records");
            $this->info('Done: Data import');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), [
                'throwable' => $e,
                'class'     => __CLASS__,
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Customize isolation lock ID
     * Default: command name
     */
    public function isolatableId(): string
    {
        return $this->getName();
    }

    /**
     * Set lock expiration time
     * Default: null (no expiration)
     */
    public function isolationLockExpiresAt(): ?\DateTimeInterface
    {
        // Lock expires after 1 hour
        return now()->addHour();
    }
}
```

### When to Use

- Data import/export commands that modify shared resources
- Batch processing with potential race conditions
- Statistics aggregation that requires exclusive access
- Commands scheduled on multiple servers (via `onOneServer()`)

### How It Works

1. Command attempts to acquire a lock using `isolatableId()`
2. If lock exists, command exits immediately
3. If lock acquired, command executes
4. Lock is released when command completes
5. Lock expires automatically after `isolationLockExpiresAt()` time

### Benefits

- Prevents race conditions in data processing
- Ensures exclusive access to shared resources
- Works across multiple servers automatically (uses cache driver)
- Safe for scheduled tasks on multi-server deployments

### Lock Storage

Locks are stored using Laravel's cache driver (configured in `config/cache.php`):
- Redis: Recommended for multi-server setups
- Database: Works but slower
- File: Only works on single server

**Template:** See `assets/templates/IsolatableCommand.php`

---

## Testing Commands

Comprehensive testing patterns for Laravel Artisan commands.

### Pattern 1: Unit Testing with Service Mocking

Test commands by mocking service dependencies to isolate business logic.

```php
<?php
namespace Tests\Unit\Commands;

use App\Services\YourService;
use Mockery\MockInterface;
use Tests\TestCase;

class BasicCommandTest extends TestCase
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

    /**
     * @test
     */
    public function commandWithArguments()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processIds')
                ->withArgs(function ($ids) {
                    return $ids === ['1', '2', '3'];
                })
                ->once()
                ->andReturn(['count' => 3]);
        });

        $this->artisan('job-antenna:your-command 1 2 3')
            ->assertExitCode(0);
    }
}
```

### Pattern 2: Testing Generator/Yield Patterns

Test commands that use generators for memory-efficient batch processing.

```php
<?php
namespace Tests\Unit\Commands;

use App\Services\YourService;
use Carbon\CarbonImmutable;
use Mockery\MockInterface;
use Tests\TestCase;

class BatchProcessingCommandTest extends TestCase
{
    /**
     * @test
     */
    public function outputTableWithResults()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processRecords')
                ->andReturn((function () {
                    yield ['id' => '1', 'name' => 'Test Record', 'status' => 'processed'];
                    yield ['id' => '2', 'name' => 'Another Record', 'status' => 'processed'];
                })());
        });

        $this->artisan('job-antenna:batch-processing')
            ->expectsTable(
                ['id', 'name', 'status'],
                [
                    ['1', 'Test Record', 'processed'],
                    ['2', 'Another Record', 'processed'],
                ]
            )
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function noResultsYieldsEmptyGenerator()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processRecords')
                ->andReturn((function () {
                    yield from [];
                })());
        });

        $this->artisan('job-antenna:batch-processing')
            ->assertExitCode(0);
    }
}
```

### Pattern 3: Testing Date/Time Arguments

Test date parsing with timezone awareness.

```php
/**
 * @test
 */
public function optionDateParsedCorrectly()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processForDate')
            ->withArgs(function ($date) {
                return CarbonImmutable::parse('2024-01-15', 'Asia/Tokyo')
                    ->startOfDay()
                    ->equalTo($date);
            })
            ->andReturn((function () {
                yield from [];
            })())
            ->once();
    });

    $this->artisan('job-antenna:batch-processing --date=2024/01/15')
        ->assertExitCode(0);
}

/**
 * @test
 */
public function defaultDateIsYesterday()
{
    $this->mock(YourService::class, function (MockInterface $mock) {
        $mock->shouldReceive('processForDate')
            ->withArgs(function ($date) {
                return CarbonImmutable::parse('yesterday', 'Asia/Tokyo')
                    ->startOfDay()
                    ->equalTo($date);
            })
            ->andReturn((function () {
                yield from [];
            })())
            ->once();
    });

    $this->artisan('job-antenna:batch-processing')
        ->assertExitCode(0);
}
```

### Pattern 4: Integration Testing with Factories

Test commands with real database interactions using Laravel Factories.

```php
<?php
namespace Tests\Command;

use App\Models\YourModel;
use App\Notifications\YourNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @group your-command
 */
class IntegrationCommandTest extends TestCase
{
    use WithFaker;

    protected function beforeTest()
    {
        $this->seedOnce([
            \Database\Seeders\YourSeeder::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test data before each test
        YourModel::query()->delete();
    }

    /**
     * @test
     */
    public function commandProcessesRecordsSuccessfully()
    {
        // Arrange: Create test data using factories
        $models = YourModel::factory(3)->create([
            'status' => 'pending',
        ]);

        // Act: Execute the command
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

    /**
     * @test
     */
    public function commandWithSpecificIds()
    {
        // Arrange: Create multiple records
        $models = YourModel::factory(5)->create(['status' => 'pending']);
        $targetIds = $models->take(2)->pluck('id')->toArray();

        // Act: Execute command with specific IDs
        $this->artisan('job-antenna:your-command ' . implode(' ', $targetIds))
            ->assertSuccessful();

        // Assert: Only specified records were processed
        foreach ($targetIds as $id) {
            $this->assertDatabaseHas('your_table', [
                'id'     => $id,
                'status' => 'processed',
            ]);
        }

        // Other records remain unchanged
        $this->assertEquals(
            3,
            YourModel::where('status', 'pending')->count()
        );
    }

    /**
     * @test
     */
    public function dryRunDoesNotModifyDatabase()
    {
        // Arrange: Create test data
        $models = YourModel::factory(3)->create(['status' => 'pending']);

        // Act: Execute command in dry-run mode
        $this->artisan('job-antenna:your-command --dry-run')
            ->assertSuccessful();

        // Assert: Database remains unchanged
        $this->assertEquals(
            3,
            YourModel::where('status', 'pending')->count()
        );
    }
}
```

### Pattern 5: Testing Notifications

Test that commands send notifications correctly.

```php
/**
 * @test
 */
public function commandSendsNotifications()
{
    Notification::fake();

    // Arrange: Create test data
    $models = YourModel::factory(2)->create();

    // Act: Execute the command
    $this->artisan('job-antenna:your-command')
        ->assertSuccessful();

    // Assert: Verify notifications were sent
    Notification::assertTimesSent(
        $models->count(),
        YourNotification::class
    );
}

/**
 * @test
 */
public function commandSkipsRecordsWithMailSettingOff()
{
    Notification::fake();

    // Arrange: Create models with different settings
    $modelWithMailOn = YourModel::factory()->create();
    $modelWithMailOff = YourModel::factory()->create();

    $modelWithMailOff->setting()->update([
        'email' => ['notifications' => false],
    ]);
    $modelWithMailOff->refresh();

    // Act: Execute the command
    $this->artisan('job-antenna:your-command')
        ->assertSuccessful();

    // Assert: Mail-off user should receive notification with empty channels
    Notification::assertSentTo(
        $modelWithMailOff,
        YourNotification::class,
        fn ($notification, $channels) => empty($channels)
    );
}
```

### Best Practices

1. **Test Organization**
   - Unit tests: `tests/Unit/Commands/` - Mock services, test logic
   - Integration tests: `tests/Command/` or `tests/Feature/` - Database, notifications
   - Use `@group` annotation for command-specific tests

2. **Mock Services**
   - Always mock external dependencies
   - Verify method calls with `withArgs()`
   - Test both success and failure paths

3. **Test All Options**
   - Test `--dry-run` mode separately
   - Test with and without arguments
   - Test default values for options

4. **Database Testing**
   - Clean up test data in `setUp()` method
   - Use transactions when possible
   - Seed only necessary data

5. **Notification Testing**
   - Use `Notification::fake()`
   - Verify recipients and channels
   - Test mail setting conditions

**Test Templates:**
- `assets/templates/tests/BasicCommandTest.php`
- `assets/templates/tests/BatchProcessingCommandTest.php`
- `assets/templates/tests/IntegrationCommandTest.php`
