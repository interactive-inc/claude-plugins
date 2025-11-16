<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Services\YourService;

class LongRunningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-antenna:long-running
                            {--dry-run : Simulate without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Long-running command template with signal handling';

    /**
     * Flag to control command execution
     *
     * @var bool
     */
    protected $shouldKeepRunning = true;

    /**
     * Execute the console command.
     */
    public function handle(YourService $service): int
    {
        try {
            $this->info('Start: Long-running task');

            // Register signal handlers for graceful shutdown
            $this->trap([SIGTERM, SIGQUIT], function (int $signal) {
                $this->shouldKeepRunning = false;
                $this->warn("Received signal {$signal}, shutting down gracefully...");
            });

            $dryRun = $this->option('dry-run');
            $processed = 0;

            // Main processing loop
            while ($this->shouldKeepRunning) {
                if ($dryRun) {
                    $this->info('Dry-run: Would process next batch');
                    sleep(1);

                    // Exit after a few iterations in dry-run
                    if (++$processed >= 3) {
                        break;
                    }
                    continue;
                }

                // Check if there's work to do
                $hasWork = $service->hasWork();

                if (!$hasWork) {
                    $this->info('No more work to process');
                    break;
                }

                // Process next batch
                $result = $service->processNextBatch();
                $processed += $result['count'];

                $this->info("Processed: {$result['count']} records (Total: {$processed})");

                // Optional: Add delay between batches
                sleep(1);
            }

            $this->info("Done: Processed {$processed} records total");
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
