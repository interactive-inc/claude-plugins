<?php

namespace App\Console;

use Illuminate\Console\Command as LaravelCommand;
use Psr\Log\LoggerInterface;

/**
 * Custom base command class with unified logging
 *
 * All application commands should extend this class instead of
 * Illuminate\Console\Command for consistent logging behavior.
 *
 * Logger can be injected via constructor (recommended) or setLogger() method.
 */
abstract class Command extends LaravelCommand
{
    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Create a new command instance with optional logger injection
     *
     * @param LoggerInterface|null $logger
     * @return void
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * Set the logger instance (alternative to constructor injection)
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Write an info message to the console and log file
     *
     * @param string $string
     * @param int|string|null $verbosity
     * @return void
     */
    public function info($string, $verbosity = null): void
    {
        parent::info($string, $verbosity);

        if ($this->logger) {
            $this->logger->info($string);
        }
    }

    /**
     * Write an error message to the console and log file
     *
     * @param string $string
     * @param int|string|array|null $verbosity
     * @param array|null $context
     * @return void
     */
    public function error($string, $verbosity = null, array $context = null): void
    {
        // Support context as second parameter
        if (is_array($verbosity) && is_null($context)) {
            $context   = $verbosity;
            $verbosity = null;
        }

        parent::error($string, $verbosity);

        if ($this->logger) {
            $this->logger->error($string, $context ?? []);
        }
    }

    /**
     * Write a warning message to the console and log file
     *
     * @param string $string
     * @param int|string|null $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null): void
    {
        parent::warn($string, $verbosity);

        if ($this->logger) {
            $this->logger->warning($string);
        }
    }
}
