---
name: laravel-command-creator
description: Laravel Artisanコマンドを実証済みパターンとLaravel 9+ベストプラクティスに従って実装。バッチ処理コマンド、スケジュールタスク、データメンテナンススクリプト、長時間実行ワーカー、大量データ処理、サービス統合、エラー耐性、シグナルハンドリング、並行実行防止、標準化ログを必要とする任意のコンソールコマンド作成時に使用。Laravel公式推奨に準拠した包括的なリファレンスと即利用可能テンプレートを提供
---

# Laravel Command Patterns

## Overview

Implement Laravel Artisan commands following proven architectural patterns from a production-grade monorepo project (JobAntenna v4), enhanced with Laravel 9+ best practices from official documentation. This skill provides battle-tested patterns for command structure, service integration, large dataset processing, error handling, signal handling, concurrent execution prevention, scheduled execution, and user interaction.

## When to Use This Skill

Use this skill when:
- Creating new Artisan commands for batch processing, data maintenance, or scheduled tasks
- Implementing commands that process large datasets efficiently
- Integrating service classes with console commands
- Setting up scheduled command execution in Kernel.php
- Standardizing error handling and logging across commands
- Building commands with dry-run capabilities
- Creating long-running workers with graceful shutdown
- Preventing concurrent command execution across multiple servers
- Needing command templates that follow Laravel best practices

## Default Settings and Conventions

This skill assumes the following project structure and conventions:

### Project Structure

```
app/
├── Console/
│   ├── Command.php              # Custom base command class
│   ├── Commands/                # Your command classes
│   └── Kernel.php               # Schedule definitions
├── Services/                    # Business logic services
└── Models/                      # Eloquent models

assets/templates/               # Ready-to-use command templates
references/                     # Detailed implementation guides
tests/
├── Unit/Console/Commands/      # Unit tests with mocks
└── Feature/Console/Commands/   # Integration tests
```

### Naming Conventions

- **Commands:** `job-antenna:{name}` or `{project-prefix}:{name}`
- **Service Classes:** `{Name}Service` (e.g., `PointCalculationService`)
- **Templates:** Descriptive names (e.g., `BatchProcessingCommand.php`)

### Default Options

Common command options to include:
- `--dry-run` : Preview changes without execution
- `--limit=N` : Limit number of records to process
- `--date=YYYY/MM/DD` : Specify target date

### Timezone

Always use **Asia/Tokyo** timezone explicitly in date/time operations:
```php
CarbonImmutable::parse($input, 'Asia/Tokyo')->startOfDay()
```

### Return Codes

- Success: `Command::SUCCESS` (or `0`)
- Failure: `Command::FAILURE` (or `1`)

### Logging

All commands automatically log to both console and log files via custom base `Command` class.

## Core Patterns

### 1. Custom Base Command Class

**Purpose:** Extends `Illuminate\Console\Command` to provide unified logging that outputs to both console and log files.

**Key Concept:** Override `info()` and `error()` methods to automatically log all command output, ensuring consistent observability.

**Template:** `assets/templates/Command.php` | **Details:** See `references/command-patterns.md` Section 1

### 2. Service Class Integration

**Purpose:** Delegate business logic to service classes using dependency injection for testability and reusability.

**Key Concept:** Use type-hinted service parameters in `handle()` method. Laravel's container automatically resolves dependencies.

**Template:** `assets/templates/ServiceIntegrationCommand.php` | **Details:** See `references/command-patterns.md` Section 2

### 3. Large Data Processing

**Purpose:** Process large datasets efficiently using `cursor()` and generators to minimize memory usage.

**Key Concept:** Use `cursor()` instead of `get()` for one-record-at-a-time loading. Display progress bars and collect errors without halting execution.

**Template:** `assets/templates/BatchProcessingCommand.php` | **Details:** See `references/command-patterns.md` Section 3

### 4. Dry-Run Mode

**Purpose:** Provide `--dry-run` option to safely preview changes before actual execution.

**Key Concept:** Check option early, display what *would* happen using table output, then return without making changes.

**Details:** See `references/command-patterns.md` Section 4

### 5. Scheduled Execution

**Purpose:** Standardize scheduled command execution in `app/Console/Kernel.php` with timezone, callbacks, and command chaining.

**Key Concept:** Use template method pattern for consistent timezone, `onOneServer()`, success/failure callbacks. Support command chaining with `after()`.

**Template:** `assets/templates/ScheduledCommand.php` | **Details:** See `references/command-patterns.md` Section 5

### 6. Error Handling Strategies

**Purpose:** Implement hierarchical error handling for resilient batch processing.

**Key Concepts:**
- **Skip and Continue:** Catch specific exceptions, log them, and continue processing remaining items
- **Hierarchical Handling:** Nested try-catch for recoverable vs fatal errors
- **report() Helper:** Log non-fatal exceptions to Laravel's exception handler without halting

**Details:** See `references/command-patterns.md` Section 6

### 7. Signal Handling for Long-Running Commands

**Purpose:** Handle OS signals (SIGTERM, SIGQUIT) gracefully for controlled shutdown of long-running commands.

**Key Concept:** Use `trap()` method to register signal handlers. Set flag to stop processing loop after current batch completes.

**Template:** `assets/templates/LongRunningCommand.php` | **Details:** See `references/command-patterns.md` Section 7

### 8. Preventing Concurrent Execution

**Purpose:** Use `Isolatable` interface to prevent multiple instances from running simultaneously across servers.

**Key Concept:** Implement `Isolatable`, customize `isolatableId()` and `isolationLockExpiresAt()` as needed. Laravel handles locking automatically.

**Template:** `assets/templates/IsolatableCommand.php` | **Details:** See `references/command-patterns.md` Section 8

### 9. Naming Conventions

**Purpose:** Follow standardized conventions for command names, return values, date/time handling, and signature options.

**Key Conventions:**
- **Command Names:** Prefix with `job-antenna:{name}` or `{project}:{name}`
- **Return Values:** Use `Command::SUCCESS` (0) and `Command::FAILURE` (1)
- **Date/Time:** Always use `CarbonImmutable::parse()` with explicit timezone (`Asia/Tokyo`)
- **Common Options:** `--dry-run`, `--limit=N`, `--date=yesterday`

**Details:** See `references/command-patterns.md` Section 9

### 10. Testing Commands

For comprehensive testing guides, patterns, and best practices, see **[TESTING_GUIDE.md](./TESTING_GUIDE.md)** which covers:

- Unit testing with service mocking
- Batch processing tests (Generator/Yield patterns)
- Date/time argument testing
- Integration testing with factories
- Notification testing
- Test organization and best practices
- Troubleshooting common test issues
- Test templates and examples

## Quick Start

For step-by-step guides with validation and error handling, see **[QUICK_START.md](./QUICK_START.md)** which covers:

- Creating a Basic Command
- Creating a Service-Integrated Command
- Creating a Batch Processing Command
- Setting Up Scheduled Execution
- Creating a Long-Running Command
- Creating an Isolated Command
- Best practices and debugging methods

Each section includes verification steps, expected outputs, and error handling instructions.

## Detailed Reference

For comprehensive implementation details, see `references/command-patterns.md` which covers:

1. **Command Categories** - Data maintenance, scheduled tasks, data generation, synchronization, development support
2. **Base Structure** - Custom base class implementation, standard template
3. **Service Class Integration** - DI patterns, generator usage, detailed business logic
4. **Large Data Processing** - cursor(), batched output, CSV processing, limit options
5. **Error Handling** - Basic try-catch, partial error handling, hierarchical strategies
6. **Scheduled Execution** - Kernel.php patterns, template methods, features
7. **User Interaction** - Confirmation dialogs, table output, CSV output, progress bars, multiple formats
8. **Testing Considerations** - Helper extraction, query builders, service classes, dry-run validation
9. **Project-Specific Conventions** - Naming, logging, return values, standardization

## Resources

### assets/templates/

Ready-to-use command templates:

- **Command.php** - Custom base command class with unified logging (supports constructor injection)
- **BasicCommand.php** - Simple command template with dry-run support
- **ServiceIntegrationCommand.php** - Command with service class integration
- **BatchProcessingCommand.php** - Large data processing with cursor(), progress bar, error collection, and report() helper
- **ScheduledCommand.php** - Scheduled task with date handling
- **LongRunningCommand.php** - Long-running command with signal handling for graceful shutdown
- **IsolatableCommand.php** - Command with Isolatable interface to prevent concurrent execution

Copy these templates to `app/Console/Commands/` and customize as needed.

### assets/templates/tests/

Ready-to-use test templates:

- **BasicCommandTest.php** - Unit test template with service mocking, dry-run testing, and option verification
- **BatchProcessingCommandTest.php** - Test template for generator/yield patterns, table output, and date parsing
- **IntegrationCommandTest.php** - Integration test template with factories, database assertions, and notification testing

Copy these templates to `tests/Unit/Commands/` (unit tests) or `tests/Command/` (integration tests) and customize as needed.

### references/

- **command-patterns.md** - Comprehensive reference with all patterns, code examples, and best practices

Load this reference when implementing complex commands or reviewing command architecture.
