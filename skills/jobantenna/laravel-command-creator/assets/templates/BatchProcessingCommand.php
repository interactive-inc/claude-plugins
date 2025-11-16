<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Models\YourModel;
use App\Services\YourService;

class BatchProcessingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-antenna:batch-processing
                            {ids?* : Specific IDs to process}
                            {--dry-run : Simulate without executing}
                            {--limit=1000 : Limit number of records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch processing command template with large data handling';

    /**
     * Execute the console command.
     */
    public function handle(YourService $service): int
    {
        try {
            $this->info('Start: Batch processing');

            $ids = $this->argument('ids');
            $dryRun = $this->option('dry-run');
            $limit = (int) $this->option('limit');

            // Build query
            $query = YourModel::query();

            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }

            if ($limit > 0) {
                $query->limit($limit);
            }

            $count = $query->count();
            $this->info("Processing {$count} records");

            if ($dryRun) {
                $this->table(['ID', 'Name'], $query->get(['id', 'name'])->toArray());
                $this->info('Dry-run mode: No actual processing');
                return Command::SUCCESS;
            }

            // Progress bar
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $processed = 0;
            $errors = [];

            // Use cursor() for memory efficiency
            $query->cursor()->each(function (YourModel $record) use (
                $service,
                $progressBar,
                &$processed,
                &$errors
            ) {
                try {
                    $progressBar->advance();

                    // Delegate to service
                    $service->processRecord($record);

                    $processed++;
                } catch (\Throwable $e) {
                    // Log exception without disrupting execution
                    // Uses Laravel's report() helper to send to exception handler
                    report($e);

                    // Skip and continue on error
                    $errors[] = [
                        'id' => $record->id,
                        'error' => $e->getMessage(),
                    ];
                    $this->error("Error processing ID {$record->id}: {$e->getMessage()}");
                }
            });

            $progressBar->finish();
            $this->newLine();

            $this->info("Processed: {$processed} records");

            if (!empty($errors)) {
                $this->warn("Errors: " . count($errors) . " records");
                $this->table(['ID', 'Error'], $errors);
            }

            $this->info('Done: Batch processing');
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
