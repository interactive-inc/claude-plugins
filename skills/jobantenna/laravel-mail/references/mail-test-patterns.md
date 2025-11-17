# Mail Test Patterns

This reference document provides comprehensive testing patterns for email functionality in the JobAntenna Laravel project.

## Table of Contents

1. [Test Structure](#test-structure)
2. [MailFake Custom Assertions](#mailfake-custom-assertions)
3. [Test Data Management](#test-data-management)
4. [Testing Patterns](#testing-patterns)
5. [Mock and Stub Strategies](#mock-and-stub-strategies)

## Test Structure

### Basic Test Class Structure

```php
<?php

namespace Tests\Feature\Mail;

use App\Mail\YourMailableName;
use App\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Tests\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\WithFaker;

class YourMailableNameTest extends TestCase
{
    use WithFaker;

    protected function beforeTest()
    {
        $this->seedOnce([
            \Database\Seeders\VirtualResourcesTableSeeder::class,
            \Database\Seeders\TermsTableSeeder::class,
        ]);
    }

    /**
     * @test
     */
    public function html(): void
    {
        $mailer = Mail::fake();

        Mail::to('test@example.com')
            ->send(new YourMailableName($this->seedApplication()));

        $mailer->assertEqualHtml(
            spaceless(file_get_contents(
                base_path('tests/data/email_template_test/to_consumer/your_template.html')
            )),
            'Tests\spaceless'
        );
    }

    /**
     * @test
     */
    public function text(): void
    {
        $mailer = Mail::fake();

        Mail::to('test@example.com')
            ->send(new YourMailableName($this->seedApplication()));

        $mailer->assertEqualText(
            spaceless(file_get_contents(
                base_path('tests/data/email_template_test/to_consumer/your_template_plain.txt')
            )),
            'Tests\spaceless'
        );
    }

    private function seedApplication(): Application
    {
        return (new Application())
            ->setAttribute('created_at', new Carbon('2024-01-01 12:00:00'))
            ->setRelation('user', $this->seedUser())
            ->setRelation('joboffer', $this->seedJoboffer());
    }

    private function seedUser(): User
    {
        return new User([
            'first_name' => '太郎',
            'last_name'  => '山田',
        ]);
    }
}
```

## MailFake Custom Assertions

The project provides a custom `MailFake` class with enhanced assertions.

### assertEqualHtml

Compare HTML email body with expected output:

```php
$mailer = Mail::fake();

Mail::to('test@example.com')->send(new YourMailable($data));

$mailer->assertEqualHtml(
    spaceless(file_get_contents(
        base_path('tests/data/email_template_test/to_consumer/your_template.html')
    )),
    'Tests\spaceless'  // Optional adjuster function
);
```

**Adjuster function**: A callable that transforms the actual HTML before comparison (e.g., removing whitespace).

### assertEqualText

Compare plain text email body:

```php
$mailer->assertEqualText(
    spaceless(file_get_contents(
        base_path('tests/data/email_template_test/to_consumer/your_template_plain.txt')
    )),
    'Tests\spaceless'
);
```

### assertEqualSubject

Compare email subject line:

```php
$mailer->assertEqualSubject('応募が完了しました【ジョブアンテナ沖縄】');
```

## Test Data Management

### seedOnce Pattern

Use `seedOnce()` to efficiently seed data without duplication:

```php
protected function beforeTest()
{
    $this->seedOnce([
        \Database\Seeders\VirtualResourcesTableSeeder::class,
        \Database\Seeders\TermsTableSeeder::class,
        \Database\Seeders\AreasTableSeeder::class,
    ]);
}
```

**Benefits**:
- Seeds are run only once per transaction level
- Reduces test execution time
- Prevents duplicate data issues

### Manual Model Construction

Build test models with explicit relationships:

```php
private function seedApplication(): Application
{
    return (new Application())
        ->setAttribute('created_at', new Carbon('1991-07-24 15:06:15'))
        ->setRelation('user', $this->seedUser())
        ->setRelation('joboffer', $this->seedJoboffer())
        ->setRelation('messageRoom', $this->seedMessageRoom());
}

private function seedUser(): User
{
    return new User([
        'first_name' => '太郎',
        'last_name'  => '浜田',
    ]);
}

private function seedJoboffer(): Joboffer
{
    return (new Joboffer(['title' => 'ハイブリッド集積回路']))
        ->setRelation('company', $this->seedCompany())
        ->setRelation('detail', $this->seedDetail())
        ->setRelation('content', $this->seedJobofferContent())
        ->setRelation('addresses', $this->seedAddresses());
}
```

### Using Factories

For simpler test data:

```php
$user = User::factory()->create();
$tempApplication = TempApplication::factory()->create([
    'user_id' => $user->id,
    'provider' => TempApplicationProvider::INDEED,
]);
```

### Expected Output Files

Store expected email output in `tests/data/email_template_test/`:

```
tests/data/email_template_test/
├── to_consumer/
│   ├── joboffer_applied.html
│   ├── joboffer_applied_plain.txt
│   ├── verify.html
│   └── verify_plain.txt
├── to_partner/
│   ├── new_applicant_has_come.html
│   └── new_applicant_has_come_plain.txt
└── to_administrator/
    └── ...
```

## Testing Patterns

### Pattern 1: Basic HTML/Text Test

```php
/**
 * @test
 */
public function html(): void
{
    $mailer = Mail::fake();

    Mail::to('test@example.com')
        ->send(new JobofferApplied($this->seedApplication()));

    $mailer->assertEqualHtml(
        spaceless(file_get_contents(
            base_path('tests/data/email_template_test/to_consumer/joboffer_applied.html')
        )),
        'Tests\spaceless'
    );
}

/**
 * @test
 */
public function text(): void
{
    $mailer = Mail::fake();

    Mail::to('test@example.com')
        ->send(new JobofferApplied($this->seedApplication()));

    $mailer->assertEqualText(
        spaceless(file_get_contents(
            base_path('tests/data/email_template_test/to_consumer/joboffer_applied_plain.txt')
        )),
        'Tests\spaceless'
    );
}
```

### Pattern 2: Data-Driven Test

Test multiple variations using `@testWith`:

```php
/**
 * Test third-party provider emails
 *
 * @test
 * @testWith ["indeed"]
 *           ["rikunabi"]
 */
public function htmlFromThirdPartyProvider(string $provider): void
{
    $mailer = Mail::fake();

    Mail::to('test@example.com')
        ->send(new JobofferApplied(
            $this->seedThirdPartyApplication($provider)
        ));

    $mailer->assertEqualHtml(
        spaceless(file_get_contents(
            base_path("tests/data/email_template_test/to_consumer/joboffer_applied_via_{$provider}.html")
        )),
        'Tests\spaceless'
    );
}
```

### Pattern 3: Subject Line Test

```php
/**
 * @test
 */
public function subjectIsSetCorrectly(): void
{
    $mailer = Mail::fake();

    $mail = new JobofferTempAppliedWithEmailVerification(
        $tempApplication,
        $verifyService
    );
    $builtMail = $mail->build();

    self::assertStringContainsString(
        'メールアドレスの確認を行ってください',
        $builtMail->subject
    );
}
```

### Pattern 4: Sanitize Method Test

Test private `sanitize()` method using reflection:

```php
/**
 * @test
 */
public function sanitizeReturnsCorrectDataStructure(): void
{
    $mail = new JobofferApplied($application);

    $reflection = new \ReflectionClass($mail);
    $sanitizeMethod = $reflection->getMethod('sanitize');
    $sanitizeMethod->setAccessible(true);

    $result = $sanitizeMethod->invoke($mail);

    self::assertArrayHasKey('application', $result);
    self::assertArrayHasKey('user', $result);
    self::assertArrayHasKey('company', $result);
    self::assertArrayHasKey('joboffer', $result);

    // Verify nested structure
    self::assertArrayHasKey('email', $result['user']);
    self::assertArrayHasKey('birthday', $result['user']);
}
```

### Pattern 5: Notification Test

Test notification send conditions and channel selection:

```php
/**
 * @test
 */
public function viaReturnsMailChannelForUserNotifiable()
{
    $user = User::factory()->create();
    $tempApplication = TempApplication::factory()->create([
        'user_id' => $user->id,
        'provider' => TempApplicationProvider::INDEED,
    ]);

    $notification = new TempApplicationCreatedWithEmailVerificationNotification($tempApplication);

    $channels = $notification->via($user);

    self::assertEquals(['mail'], $channels);
}

/**
 * @test
 */
public function viaReturnsEmptyArrayForPartnerNotifiable()
{
    $partner = Partner::factory()->create();
    $notification = new TempApplicationCreatedWithEmailVerificationNotification($tempApplication);

    $channels = $notification->via($partner);

    self::assertEquals([], $channels);  // No email sent
}

/**
 * @test
 */
public function toMailReturnsCorrectMailableInstance()
{
    $user = User::factory()->create();
    $notification = new TempApplicationCreatedWithEmailVerificationNotification($tempApplication);

    $mail = $notification->toMail($user);

    self::assertInstanceOf(JobofferTempAppliedWithEmailVerification::class, $mail);
}

/**
 * @test
 */
public function mailIsAddressedToUserEmail()
{
    $user = User::factory()->create(['email' => 'test@example.com']);
    $notification = new TempApplicationCreatedWithEmailVerificationNotification($tempApplication);

    $mail = $notification->toMail($user);

    self::assertEquals(
        [['address' => 'test@example.com', 'name' => null]],
        $mail->to
    );
}
```

## Mock and Stub Strategies

### Strategy 1: Anonymous Class Override

Simple method overrides:

```php
private function seedVerifyServiceForConsumer(): void
{
    $verifyService = new class extends VerifyService {
        public function getVerify($email)
        {
            return [
                'expire' => '1650867455',
                'hash'   => 'ea26a84d7cbba22734f826866cedb6450b9cfbcdd55958e410e51b28533a2013',
            ];
        }
    };

    $this->instance(VerifyService::class, $verifyService);
}
```

### Strategy 2: Mockery Partial Mock

For selective method mocking:

```php
private function seedThirdPartyApplication(string $provider): Application
{
    $joboffer = Mockery::mock(Joboffer::class)->makePartial();
    $joboffer->setAttribute('title', 'ハイブリッド集積回路');
    $joboffer->shouldReceive('getUrlAttribute')
        ->andReturn('http://example.com');

    $application = Mockery::mock(Application::class)->makePartial();
    $application->shouldReceive('thirdPartyProvider')
        ->andReturn(Attribute::make(
            get: fn (): ?TempApplicationProvider => TempApplicationProvider::from($provider)
        ));

    return $application
        ->setAttribute('created_at', new Carbon('1991-07-24 15:06:15'))
        ->setRelation('user', $this->seedUser())
        ->setRelation('joboffer', $joboffer);
}
```

### Strategy 3: Anonymous Class with Method Override

Override specific model methods:

```php
protected function seedUser(): User
{
    $user = new class([
        'first_name' => '陽子',
        'last_name' => '高橋',
        'birthday' => (new Carbon())->subYears(86),
        'gender' => 'female',
    ]) extends User {
        public function mayBeName(Company $company)
        {
            return $this->name;  // Override anonymization
        }
    };

    return $user->setAttribute('id', 9876);
}
```

## Abstract Test Case Pattern

Share common test data across multiple test classes:

```php
// tests/Feature/Mail/Abstracts/WeeklyReportMailTestCase.php
abstract class WeeklyReportMailTestCase extends TestCase
{
    use WithFaker;

    protected function beforeTest()
    {
        $this->seedOnce([
            \Database\Seeders\VirtualResourcesTableSeeder::class,
        ]);
    }

    abstract public function html(): void;
    abstract public function text(): void;

    protected function seedCompanySummaryDecorator(): CompanySummaryDecorator
    {
        return new CompanySummaryDecorator(
            $this->seedCompany(),
            new Carbon('2001-11-11 12:51:10'),
            new Carbon('1987-08-30 16:14:27')
        );
    }

    protected function seedCompany(): Company
    {
        return (new Company(['name' => '有限会社 山岸']))
            ->setAttribute('id', 12345);
    }
}

// tests/Feature/Mail/WeeklyReportMailToPartnerTest.php
class WeeklyReportMailToPartnerTest extends WeeklyReportMailTestCase
{
    /**
     * @test
     */
    public function html(): void
    {
        $mailer = Mail::fake();

        Mail::to('test@example.com')
            ->send(new WeeklyReportMailToPartner(
                $this->seedCompanySummaryDecorator(),
                100,
                50
            ));

        $mailer->assertEqualHtml(
            spaceless(file_get_contents(
                base_path('tests/data/email_template_test/to_partner/weekly_report.html')
            )),
            'Tests\spaceless'
        );
    }

    /**
     * @test
     */
    public function text(): void
    {
        // Similar implementation
    }
}
```

## Helper Functions

### spaceless()

Remove leading whitespace from strings for comparison:

```php
namespace Tests;

function spaceless(string $value): string
{
    return preg_replace('/^\s+/m', '', $value);
}
```

Usage:

```php
$mailer->assertEqualHtml(
    spaceless(file_get_contents(...)),
    'Tests\spaceless'
);
```

### invokePrivateMethod()

Access private methods for testing:

```php
namespace Tests;

function invokePrivateMethod(object $object, string $methodName, array $parameters = [])
{
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method->invokeArgs($object, $parameters);
}
```

Usage:

```php
$result = invokePrivateMethod($mail, 'sanitize', []);
```

## Best Practices

### 1. Always Use spaceless()

When comparing HTML/text output, use `spaceless()` to ignore whitespace differences:

```php
// ✅ Good
$mailer->assertEqualHtml(
    spaceless(file_get_contents(...)),
    'Tests\spaceless'
);

// ❌ Bad - whitespace differences will fail
$mailer->assertEqualHtml(
    file_get_contents(...),
    null
);
```

### 2. Keep Test Data Hierarchical

Build test data in layers for readability:

```php
// ✅ Good - hierarchical
private function seedApplication(): Application
{
    return (new Application())
        ->setRelation('user', $this->seedUser())
        ->setRelation('joboffer', $this->seedJoboffer());
}

private function seedUser(): User { ... }
private function seedJoboffer(): Joboffer { ... }

// ❌ Bad - flat structure
private function seedEverything(): array
{
    // All data in one method
}
```

### 3. Use Abstract Test Cases for Shared Logic

When multiple mail classes share test patterns:

```php
// ✅ Good - shared logic in abstract class
abstract class NewApplicantHasComeTestCase extends TestCase
{
    protected function seedApplication() { ... }
}

class NewApplicantHasComeToPartnerTest extends NewApplicantHasComeTestCase { ... }
class NewApplicantHasComeToAdministratorTest extends NewApplicantHasComeTestCase { ... }

// ❌ Bad - duplicated logic
class NewApplicantHasComeToPartnerTest extends TestCase
{
    protected function seedApplication() { ... }  // Duplicated
}
```

### 4. Test Both HTML and Text Versions

Always test both email formats:

```php
// ✅ Good
public function html(): void { ... }
public function text(): void { ... }

// ❌ Bad - only testing HTML
public function html(): void { ... }
```

### 5. Use seedOnce() for Performance

Avoid redundant seeding:

```php
// ✅ Good
protected function beforeTest()
{
    $this->seedOnce([
        \Database\Seeders\VirtualResourcesTableSeeder::class,
    ]);
}

// ❌ Bad - seeds every time
protected function beforeTest()
{
    $this->seed([
        \Database\Seeders\VirtualResourcesTableSeeder::class,
    ]);
}
```
