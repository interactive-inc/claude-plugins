<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Services\YourService;
use Illuminate\Contracts\Console\Isolatable;

/**
 * Command with isolation to prevent concurrent execution
 *
 * Implements Isolatable interface to ensure only one instance
 * of this command runs at a time across multiple servers.
 */
class IsolatableCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-antenna:isolatable
                            {--dry-run : Simulate without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command template with isolation to prevent concurrent execution';

    /**
     * Execute the console command.
     *
     * Laravel automatically applies an atomic lock when this command runs.
     * If another instance is already running, this execution will be skipped.
     */
    public function handle(YourService $service): int
    {
        try {
            $this->info('Start: Isolated command processing');

            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->info('Dry-run: Would process data exclusively');
                return Command::SUCCESS;
            }

            // This code will only execute if no other instance is running
            $result = $service->processExclusively();

            $this->info("Processed: {$result['count']} records");

            $this->info('Done: Isolated command processing');
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
     * Customize the isolation lock ID
     *
     * By default, Laravel uses the command name.
     * Override this to create custom isolation scopes.
     *
     * @return string
     */
    public function isolatableId(): string
    {
        // Example: Isolate per specific parameter
        // return $this->option('scope') ?: $this->getName();

        return $this->getName();
    }

    /**
     * Define when the isolation lock should expire
     *
     * Return a DateTimeInterface to set a custom expiration.
     * If not defined, the lock expires after the command finishes.
     *
     * @return \DateTimeInterface|null
     */
    public function isolationLockExpiresAt(): ?\DateTimeInterface
    {
        // Example: Set lock expiration to 1 hour from now
        // return now()->addHour();

        return null;
    }
}
