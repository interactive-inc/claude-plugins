<?php

namespace App\Console\Commands;

use App\Console\Command;

class BasicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-antenna:basic
                            {--dry-run : Simulate without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Basic command template';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('Start: Basic command processing');

            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->info('Dry-run mode: No actual processing');
                return Command::SUCCESS;
            }

            // Your processing logic here

            $this->info('Done: Basic command processing');
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
