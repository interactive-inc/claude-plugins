<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Services\YourService;

class ServiceIntegrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Signature Definition Patterns:
     *
     * 1. Multi-line string (current):
     *    protected $signature = 'command:name
     *                            {argument : description}
     *                            {--option : description}';
     *
     * 2. Heredoc format (recommended for complex signatures):
     *    protected $signature = <<<SIGNATURE
     *    command:name
     *    {argument : description}
     *    {--option : description}
     *    SIGNATURE;
     *
     * 3. String concatenation (for dynamic or long signatures):
     *    protected $signature = 'command:name' .
     *        ' {argument : description}' .
     *        ' {--option : description}';
     *
     * Argument patterns:
     * - {argument} : Required argument
     * - {argument?} : Optional argument
     * - {argument*} : Array argument (multiple values)
     * - {argument?*} : Optional array argument
     * - {argument=default} : Argument with default value
     *
     * @var string
     */
    protected $signature = 'job-antenna:service-integration
                            {targetModels?* : Target model names}
                            {--dry-run : Simulate without executing}
                            {--limit=100 : Limit number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command template with service class integration';

    /**
     * Execute the console command.
     * Service classes are injected via DI
     */
    public function handle(YourService $service): int
    {
        try {
            $this->info('Start: Service integration command');

            $targetModels = $this->argument('targetModels') ?? [];
            $dryRun = $this->option('dry-run');
            $limit = (int) $this->option('limit');

            if (empty($targetModels)) {
                $this->error('No target models specified');
                return Command::FAILURE;
            }

            foreach ($targetModels as $model) {
                $this->info("Processing: {$model}");

                if ($dryRun) {
                    $this->info("  Dry-run: Would process {$model}");
                    continue;
                }

                // Delegate business logic to service
                $service->process($model, $limit);

                $this->info("  Completed: {$model}");
            }

            $this->info('Done: Service integration command');
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
