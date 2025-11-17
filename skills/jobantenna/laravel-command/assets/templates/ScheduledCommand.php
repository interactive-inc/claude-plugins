<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Services\YourService;
use Carbon\CarbonImmutable;

class ScheduledCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-antenna:scheduled
                            {--date=yesterday : Target date (yesterday, today, or YYYY-MM-DD)}
                            {--dry-run : Simulate without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scheduled task command template';

    /**
     * Execute the console command.
     */
    public function handle(YourService $service): int
    {
        try {
            $dryRun = $this->option('dry-run');

            // Display dry-run mode warning at the very beginning
            if ($dryRun) {
                $this->warn('========================================');
                $this->warn('  DRY-RUN MODE: No actual processing');
                $this->warn('========================================');
                $this->newLine();
            }

            $this->info('Start: Scheduled task');

            // Parse date with timezone
            $date = CarbonImmutable::parse(
                $this->option('date'),
                'Asia/Tokyo'
            )->startOfDay();

            $this->info("Target date: {$date->toDateString()}");

            if ($dryRun) {
                $this->info("Preview: Would process records for {$date->toDateString()}");
                $this->newLine();
                $this->info('Dry-run completed successfully');
                return Command::SUCCESS;
            }

            // Delegate to service
            $result = $service->processForDate($date);

            $this->info("Processed: {$result['count']} records");

            $this->info('Done: Scheduled task');
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
