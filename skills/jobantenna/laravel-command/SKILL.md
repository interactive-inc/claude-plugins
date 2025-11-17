---
name: laravel-command
description: Laravel Artisan コマンドを実証済みパターンと Laravel 9+ ベストプラクティスに従って実装・レビューする。バッチ処理コマンド、スケジュールタスク、データメンテナンス、長時間実行ワーカー、大量データ処理、サービス統合、エラー耐性、シグナルハンドリング、並行実行防止、標準化ログを必要とする任意のコンソールコマンド作成時に使用する。Laravel 公式推奨に準拠した包括的なリファレンス、即利用可能テンプレート、専門レビューエージェントを提供。「Laravel コマンド作成」「Artisan コマンド実装」「バッチ処理」「スケジュール実行」「コマンドレビュー」などの依頼時に使用
---

# Laravel Command Developer

## Overview

Implement and review Laravel Artisan commands following proven architectural patterns from a production-grade monorepo project (JobAntenna v4), enhanced with Laravel 9+ best practices from official documentation. This skill provides battle-tested patterns for command structure, service integration, large dataset processing, error handling, signal handling, concurrent execution prevention, scheduled execution, user interaction, and specialized review capabilities.

## Table of Contents

- [When to Use This Skill](#when-to-use-this-skill)
- [Template Selection Guide](#template-selection-guide)
- [Default Settings and Conventions](#default-settings-and-conventions)
- [Core Patterns](#core-patterns)
- [Quick Start](#quick-start)
- [Detailed Reference](#detailed-reference)
- [Resources](#resources)
- [Command Review](#command-review)

## When to Use This Skill

Use this skill when:

### For Command Implementation
- Creating new Artisan commands for batch processing, data maintenance, or scheduled tasks
- Implementing commands that process large datasets efficiently
- Integrating service classes with console commands
- Setting up scheduled command execution in Kernel.php
- Standardizing error handling and logging across commands
- Building commands with dry-run capabilities
- Creating long-running workers with graceful shutdown
- Preventing concurrent command execution across multiple servers
- Needing command templates that follow Laravel best practices

### For Command Review
- Reviewing existing command implementations for best practice compliance
- Evaluating command quality against proven patterns
- Getting improvement suggestions with specific code examples
- Ensuring maintainability and testability of command code

## Template Selection Guide

Choose the appropriate template based on your command's characteristics:

### For Simple Commands
**Use**: `BasicCommand.php`
- One-time data operations
- Simple maintenance tasks
- Quick utility commands

### For Service Integration
**Use**: `ServiceIntegrationCommand.php`
- Complex business logic delegation
- Multiple service interactions
- High testability requirements

### For Large Data Processing
**Use**: `BatchProcessingCommand.php`
- Processing thousands of records
- Memory-efficient operations required
- Progress tracking needed

### For Scheduled Tasks
**Use**: `ScheduledCommand.php`
- Daily/weekly/monthly execution
- Timezone-aware date handling
- Kernel.php integration

### For Long-Running Workers
**Use**: `LongRunningCommand.php`
- Continuous monitoring tasks
- Queue workers
- Graceful shutdown required

### For Concurrent Execution Prevention
**Use**: `IsolatableCommand.php`
- Critical data operations
- Multi-server environments
- Lock management needed

### Decision Flow

```
Does it run for >1 minute?
  ├─ Yes → LongRunningCommand.php
  └─ No  → Does it process >1000 records?
            ├─ Yes → BatchProcessingCommand.php
            └─ No  → Does it need service classes?
                      ├─ Yes → ServiceIntegrationCommand.php
                      └─ No  → BasicCommand.php
```

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

## Skill Default Behavior

This section clarifies the skill's default behavior when you don't provide specific instructions.

### When Implementing Commands

- **Starting Template**: Use `BasicCommand.php` for simple one-time operations. If you're unsure which template to use, start simple and refactor later as requirements become clear.
- **Error Handling**: Always implement hierarchical error handling with try-catch blocks and `report()` helper for non-fatal exceptions.
- **Timezone Handling**: Always explicitly specify `'Asia/Tokyo'` timezone in all date/time operations using `CarbonImmutable`.
- **Dry-Run Mode**: Include `--dry-run` option for any command that modifies data, with a prominent warning banner displayed at the very beginning.
- **Progress Indication**: For batch processing (>100 records), include progress bars using `$this->output->createProgressBar()`.
- **Service Integration**: Extract business logic to service classes injected via `handle()` method parameters for better testability.

### When Reviewing Commands

- **Evaluation Scope**: All 10 aspects are evaluated equally (Basic Structure, Service Integration, Data Processing, Dry-Run, Error Handling, Timezone, Signal Handling, Concurrent Execution, User Interaction, Testability).
- **Priority Assignment**:
  - **High**: Issues affecting data integrity, production operations, or security
  - **Medium**: Issues affecting maintainability, performance, or best practice compliance
  - **Low**: Issues affecting code style, UX improvements, or optional refactoring
- **Output Format**: Structured review report following the template in `agents/laravel-command-reviewer.md`
- **Recommendations**: All improvement suggestions include specific code examples showing both current and recommended implementations.
- **Quality Assessment**: Overall quality is rated as Excellent / Good / Needs Improvement / Inadequate based on the evaluation summary.

### When User Instructions Conflict

If user instructions conflict with these defaults:
- User's specific requirements always take priority
- If unclear, ask for clarification using the AskUserQuestion tool
- Document deviations from defaults in code comments

## Core Patterns

The patterns are organized by importance and frequency of use:

### Essential Patterns (Must Know) ⭐⭐⭐

These patterns are critical and used in most commands.

#### 1. Custom Base Command Class

**Priority**: Critical | **Frequency**: Every command uses this

**Purpose:** Extends `Illuminate\Console\Command` to provide unified logging that outputs to both console and log files.

**Key Concept:** Override `info()` and `error()` methods to automatically log all command output, ensuring consistent observability.

**Template:** `assets/templates/Command.php` | **Details:** See `references/command-patterns.md` Section 1

#### 2. Service Class Integration

**Priority**: Critical | **Frequency**: Most commands need this

**Purpose:** Delegate business logic to service classes using dependency injection for testability and reusability.

**Key Concept:** Use type-hinted service parameters in `handle()` method. Laravel's container automatically resolves dependencies.

**Template:** `assets/templates/ServiceIntegrationCommand.php` | **Details:** See `references/command-patterns.md` Section 2

### Recommended Patterns (Frequent Use) ⭐⭐

These patterns are commonly needed in batch and data-processing commands.

#### 3. Large Data Processing

**Priority**: High | **Frequency**: Common in batch commands

**Purpose:** Process large datasets efficiently using `cursor()` and generators to minimize memory usage.

**Key Concept:** Use `cursor()` instead of `get()` for one-record-at-a-time loading. Display progress bars and collect errors without halting execution.

**Template:** `assets/templates/BatchProcessingCommand.php` | **Details:** See `references/command-patterns.md` Section 3

#### 4. Dry-Run Mode

**Priority**: High | **Frequency**: Recommended for all data-modifying commands

**Purpose:** Provide `--dry-run` option to safely preview changes before actual execution with clear visual indication.

**Key Concept:** Display prominent warning banner at the very beginning of command execution to clearly indicate dry-run mode. Show what *would* happen using table output, then return without making changes.

**Best Practice:** Always check dry-run flag first and display warning before any other processing to ensure users are immediately aware they are in simulation mode.

**Details:** See `references/command-patterns.md` Section 4

### Situational Patterns (As Needed) ⭐

These patterns are used only when specific requirements apply.

#### 5. Scheduled Execution

**Priority**: Medium | **Frequency**: Only for scheduled tasks

**Purpose:** Standardize scheduled command execution in `app/Console/Kernel.php` with timezone, callbacks, and command chaining.

**Key Concept:** Use template method pattern for consistent timezone, `onOneServer()`, success/failure callbacks. Support command chaining with `after()`.

**Template:** `assets/templates/ScheduledCommand.php` | **Details:** See `references/command-patterns.md` Section 5

#### 6. Error Handling Strategies

**Priority**: High | **Frequency**: Recommended for batch processing

**Purpose:** Implement hierarchical error handling for resilient batch processing.

**Key Concepts:**
- **Skip and Continue:** Catch specific exceptions, log them, and continue processing remaining items
- **Hierarchical Handling:** Nested try-catch for recoverable vs fatal errors
- **report() Helper:** Log non-fatal exceptions to Laravel's exception handler without halting

**Details:** See `references/command-patterns.md` Section 6

#### 7. Signal Handling for Long-Running Commands

**Priority**: Medium | **Frequency**: Only for long-running workers

**Purpose:** Handle OS signals (SIGTERM, SIGQUIT) gracefully for controlled shutdown of long-running commands.

**Key Concept:** Use `trap()` method to register signal handlers. Set flag to stop processing loop after current batch completes.

**Template:** `assets/templates/LongRunningCommand.php` | **Details:** See `references/command-patterns.md` Section 7

#### 8. Preventing Concurrent Execution

**Priority**: Medium | **Frequency**: Critical operations in multi-server environments

**Purpose:** Use `Isolatable` interface to prevent multiple instances from running simultaneously across servers.

**Key Concept:** Implement `Isolatable`, customize `isolatableId()` and `isolationLockExpiresAt()` as needed. Laravel handles locking automatically.

**Template:** `assets/templates/IsolatableCommand.php` | **Details:** See `references/command-patterns.md` Section 8

#### 9. Naming Conventions

**Priority**: Critical | **Frequency**: Every command must follow these

**Purpose:** Follow standardized conventions for command names, return values, date/time handling, and signature options.

**Key Conventions:**
- **Command Names:** Prefix with `job-antenna:{name}` or `{project}:{name}`
- **Return Values:** Use `Command::SUCCESS` (0) and `Command::FAILURE` (1)
- **Date/Time:** Always use `CarbonImmutable::parse()` with explicit timezone (`Asia/Tokyo`)
- **Common Options:** `--dry-run`, `--limit=N`, `--date=yesterday`

**Details:** See `references/command-patterns.md` Section 9

#### 10. Testing Commands

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

### agents/

- **laravel-command-reviewer.md** - Specialized agent for reviewing Laravel Artisan command implementations

This agent evaluates commands against 10 key aspects including structure, service integration, data processing, dry-run implementation, error handling, timezone handling, signal handling, concurrent execution prevention, user interaction, and testability.

## Command Review

This skill includes a specialized review agent that evaluates your Laravel Artisan command implementations against proven patterns and best practices.

### When to Use the Review Function

Use the review agent when:
- You've implemented a new command and want to ensure it follows best practices
- You need to refactor existing commands for better maintainability
- You want to identify potential issues before production deployment
- You're onboarding new team members and want to standardize command quality
- You need specific improvement suggestions with code examples

### Review Process

The review agent evaluates commands based on **10 key aspects**:

1. **Basic Structure and Best Practices** - Naming conventions, signature definition, return codes
2. **Service Class Integration** - Dependency injection, separation of concerns, testability
3. **Large Data Processing** - cursor() usage, progress bars, memory efficiency
4. **Dry-Run Mode** - Warning banner, preview functionality, completion messages
5. **Error Handling** - Hierarchical error processing, report() helper, error collection
6. **Scheduled Execution and Timezone** - Asia/Tokyo timezone, CarbonImmutable, date options
7. **Long-Running and Signal Handling** - trap() method, graceful shutdown, cleanup
8. **Concurrent Execution Prevention** - Isolatable interface, lock management
9. **User Interaction** - Appropriate output methods, table display, confirmation dialogs
10. **Test Ease** - Mockability, query builder separation, test file existence

### Review Output

The agent provides a comprehensive review report including:

- **Overall Quality Assessment** - Excellent / Good / Needs Improvement / Inadequate
- **Evaluation Summary Table** - Rating for each of the 10 aspects
- **Prioritized Improvement Suggestions** - High / Medium / Low priority with:
  - Current implementation code
  - Identified problems
  - Recommended implementation code
  - Expected benefits
- **Next Actions** - Clear steps to improve the command

### How to Request a Review

To review a command, provide the file path and optionally specific areas of concern:

**Example 1: Full Review**
```
Please review app/Console/Commands/CalculatePointsCommand.php
```

**Example 2: Focused Review**
```
Please review the error handling and dry-run implementation in
app/Console/Commands/SyncDataCommand.php
```

**Example 3: Multiple Files**
```
Please review these commands:
- app/Console/Commands/ProcessOrdersCommand.php
- app/Console/Commands/GenerateReportsCommand.php
```

The agent will read the files, evaluate them against the 10 aspects, and provide detailed improvement suggestions with code examples.
