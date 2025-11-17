# JobAntenna Docker Environment Reference

This document provides detailed information about the JobAntenna project's Docker environment for PHPUnit test execution.

## Project Structure

```
/Users/nishikawa/projects/inta/jobantenna/
├── src/                          # Main project directory
│   ├── server/                   # Laravel application
│   │   ├── tests/               # PHPUnit tests
│   │   │   ├── Unit/           # Unit tests
│   │   │   └── Feature/        # Feature tests (if any)
│   │   ├── vendor/bin/phpunit  # PHPUnit executable
│   │   └── phpunit.xml         # PHPUnit configuration
│   ├── consumer/                # Consumer frontend
│   ├── partner/                 # Partner frontend
│   ├── administrator/           # Administrator frontend
│   └── Makefile                 # Docker command shortcuts
└── laradock/                     # Laradock Docker configuration
    └── docker-compose.yml
```

## Docker Configuration

### Laradock Details

- **Location**: `../laradock` (relative to src/)
- **Absolute path**: `/Users/nishikawa/projects/inta/jobantenna/laradock`
- **Compose tool**: `mutagen-compose` (for performance on macOS)

### Workspace Container

- **Container name**: `laradock-jav4-workspace-1`
- **Service name**: `workspace`
- **Working directory**: `/var/www`
- **Mount mapping**: `/var/www` → `src/server/`
- **User**: `laradock`

## PHPUnit Configuration

### Version Information

- **PHPUnit version**: 9.6.16
- **PHP version**: 8.2.*
- **Laravel version**: 9.x

### Execution Paths

From **outside** containers (host machine):
```bash
cd /Users/nishikawa/projects/inta/jobantenna/laradock
docker-compose exec workspace bash -c "./vendor/bin/phpunit [options]"
```

From **inside** workspace container:
```bash
./vendor/bin/phpunit [options]
```

### Common Test Commands

#### Run all tests
```bash
docker-compose exec workspace bash -c "./vendor/bin/phpunit"
```

#### Run specific test class (filter)
```bash
docker-compose exec workspace bash -c "./vendor/bin/phpunit --filter=UserTest"
```

#### Run specific test file
```bash
docker-compose exec workspace bash -c "./vendor/bin/phpunit tests/Unit/UserTest.php"
```

#### Check PHPUnit version
```bash
docker-compose exec workspace bash -c "./vendor/bin/phpunit --version"
```

## Test Directory Structure

```
server/tests/
├── Unit/
│   ├── UserTest.php
│   ├── ApplicationTest.php
│   ├── FootprintTest.php
│   ├── Directives/
│   │   ├── ConvertTempApplicationDirectiveTest.php
│   │   └── KanaNormalizeDirectiveTest.php
│   ├── Decorator/
│   │   ├── UserFollowCompanyDecoratorTest.php
│   │   └── CompanySummaryDecoratorTest.php
│   └── Models/
│       ├── ApplicationTest.php
│       └── PickupTest.php
└── Feature/
    └── (Feature tests, if any)
```

## Environment Requirements

### Prerequisites

1. Docker must be running
2. Laradock containers must be started: `make up`
3. Database (MySQL) container must be available for database-dependent tests

### Make Commands

Available from `src/` directory:

- `make up` - Start Docker environment
- `make down` - Stop Docker environment
- `make bash` - Enter workspace container interactively

## Test Execution Best Practices

### For Test Runner Agent

1. **Always use absolute paths** when changing directories
2. **Use docker-compose exec** rather than entering container interactively
3. **Handle long-running tests** by using background execution and monitoring output
4. **Parse test output** to extract:
   - Number of tests run
   - Number of assertions
   - Pass/fail status
   - Error messages and stack traces

### Expected Output Format

PHPUnit outputs progress as dots (`.`) for each passing test:

```
PHPUnit 9.6.16 by Sebastian Bergmann and contributors.

...........................                                       27 / 43 ( 62%)
................                                                  43 / 43 (100%)

Time: 00:01.234, Memory: 24.00 MB

OK (43 tests, 128 assertions)
```

For failures:

```
PHPUnit 9.6.16 by Sebastian Bergmann and contributors.

.F.

Time: 00:00.123, Memory: 12.00 MB

There was 1 failure:

1) Tests\Unit\UserTest::testExample
Failed asserting that false is true.

/var/www/tests/Unit/UserTest.php:15

FAILURES!
Tests: 3, Assertions: 3, Failures: 1.
```

## Troubleshooting

### Common Issues

1. **Container not running**: Ensure `make up` was executed
2. **Permission errors**: PHPUnit runs as `laradock` user inside container
3. **Database connection errors**: Verify MySQL container is running and `.env` is configured
4. **Path not found**: Remember `/var/www` is the working directory, not `/var/www/server`
