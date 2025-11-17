---
name: jobantenna-phpunit-runner
description: Execute PHPUnit tests in JobAntenna's Docker environment (Laradock). Use PROACTIVELY when the user requests to run PHPUnit tests, execute specific test files or filters, or verify test results in the JobAntenna project. This skill MUST BE USED for any PHPUnit test execution requests. This skill launches a specialized agent to handle time-consuming test execution efficiently without blocking the main conversation.
---

# JobAntenna PHPUnit Test Runner

## Overview

Execute PHPUnit tests within JobAntenna's Laradock-based Docker environment. This skill provides a specialized workflow for running tests asynchronously through a dedicated test runner agent, optimized for handling time-consuming test execution without blocking the main conversation.

## When to Use This Skill

Invoke this skill when the user requests:
- "Run PHPUnit tests"
- "Execute UserTest"
- "Run tests for ApplicationTest"
- "Test the Application model"
- "Run all unit tests"
- "Execute tests/Unit/Models/ApplicationTest.php"

## Core Workflow

### 1. Identify Test Scope

Determine what tests to run based on user request:
- **Specific test class**: `--filter=UserTest`
- **Specific file**: `tests/Unit/Models/ApplicationTest.php`
- **All tests**: Run without filter

### 2. Launch Test Runner Agent

Use the Task tool to launch the `phpunit-test-runner` agent (defined in `agents/phpunit-test-runner.md`) with the test specification:

```
Task tool with:
- subagent_type: "jobantenna-phpunit-runner:phpunit-test-runner"
- description: "Run PHPUnit tests: [test-name]"
- prompt: "Execute PHPUnit tests with the following specification: [filter or file path]"
```

The agent will:
1. Build the appropriate Docker command
2. Execute tests in the workspace container
3. Monitor execution progress
4. Parse results and generate a report

### 3. Report Results

Once the agent completes, summarize the test results to the user:
- Number of tests executed
- Pass/fail status
- Any errors or failures encountered

## Docker Environment Details

The JobAntenna project uses Laradock for Docker containerization. Key details are documented in `references/docker-environment.md` for the test runner agent to reference.

### Quick Reference

- **Container**: workspace
- **Working directory**: `/var/www` (maps to server/)
- **PHPUnit path**: `./vendor/bin/phpunit`
- **PHPUnit version**: 9.6.16
- **Laradock location**: `../laradock` relative to project root

## Test Execution Commands

The test runner agent will use these commands:

### Via Docker Compose (Recommended)

```bash
cd /path/to/laradock
docker-compose exec workspace bash -c "./vendor/bin/phpunit [options]"
```

### Common Options

- `--filter=TestClassName` - Run specific test class
- `tests/Unit/SomeTest.php` - Run specific test file
- `--version` - Check PHPUnit version

## Example Usage

**User**: "Run the UserTest"

**Assistant**:
1. Identify scope: `--filter=UserTest`
2. Launch agent: Task tool with phpunit-test-runner, prompt "Execute PHPUnit tests with filter: UserTest"
3. Wait for agent completion
4. Report: "Executed 43 tests from UserTest. All tests passed successfully."

## Resources

### agents/phpunit-test-runner.md

Specialized agent that handles actual test execution:
- Docker command construction
- Test execution monitoring
- Result parsing and reporting
- Error handling

### references/docker-environment.md

Detailed Docker environment configuration for the test runner agent, including:
- Full Laradock directory structure
- Container mounting details
- Environment variables
- Test directory structure
