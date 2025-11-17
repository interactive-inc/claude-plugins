# Mail Implementation Patterns

This reference document provides comprehensive patterns for implementing email functionality in the JobAntenna Laravel project.

## Table of Contents

1. [Mailable Class Patterns](#mailable-class-patterns)
2. [Notification Class Patterns](#notification-class-patterns)
3. [Template Patterns](#template-patterns)
4. [Common Patterns](#common-patterns)

## Mailable Class Patterns

### Pattern 1: Simple Mailable

For straightforward emails with minimal data transformation:

```php
<?php

namespace App\Mail;

use App\Models\UserBase;
use App\Services\VerifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class VerifyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private UserBase $user)
    {
        parent::__construct();
    }

    public function build(VerifyService $verifyService): static
    {
        $user = $this->user;
        $base = 'emails.to_' . $user->duty;
        $site = config('siteNames.' . $user->guard_name);

        return $this->view("virtual_resources::{$base}.verify")
            ->text("virtual_resources::{$base}.verify_plain")
            ->subject("メールアドレスの確認【{$site}】")
            ->with(['query' => http_build_query($verifyService->getVerify($user->email))]);
    }

    protected function sanitize(): array
    {
        return [];
    }
}
```

**When to use**: Simple verification emails, password reset emails.

### Pattern 2: Complex Mailable with Multiple Sanitize Traits

For emails requiring data from multiple models:

```php
<?php

namespace App\Mail;

use App\Mail\Traits\SanitizeApplication;
use App\Mail\Traits\SanitizeCompany;
use App\Mail\Traits\SanitizeUser;
use App\Mail\Traits\SanitizeJoboffer;
use App\Mail\Traits\SanitizeMessageRoom;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class JobofferApplied extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use SanitizeApplication, SanitizeCompany, SanitizeUser, SanitizeJoboffer, SanitizeMessageRoom;

    public function __construct(private Application $application)
    {
        parent::__construct();
    }

    public function build(): static
    {
        $siteName = config('siteNames.users');

        // Handle third-party providers (Indeed, etc.)
        if ($this->application->thirdPartyProvider) {
            return $this->view("virtual_resources::emails.to_consumer.joboffer_applied_via_third_party_provider")
                ->text("virtual_resources::emails.to_consumer.joboffer_applied_via_third_party_provider_plain")
                ->subject("{$this->application->thirdPartyProvider->serviceName()}掲載求人への応募が完了しました【{$siteName}】");
        }

        return $this->view('virtual_resources::emails.to_consumer.joboffer_applied')
            ->text('virtual_resources::emails.to_consumer.joboffer_applied_plain')
            ->subject('応募が完了しました【' . $siteName . '】');
    }

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'user'        => $this->sanitizeUserForMe($this->application->user),
            'company'     => $this->sanitizeCompany($this->application->joboffer->company),
            'joboffer'    => $this->sanitizeJoboffer($this->application->joboffer),
            'messageRoom' => $this->sanitizeMessageRoom($this->application->messageRoom),
        ];
    }
}
```

**When to use**: Application submitted, user profile update, complex notifications.

### Pattern 3: Inheritance-Based Mailable

For emails with shared logic across recipient types:

```php
<?php

namespace App\Mail;

use App\Decorators\CompanySummaryDecorator;
use App\Mail\Traits\SanitizeCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

abstract class WeeklyReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use SanitizeCompany;

    public function __construct(
        protected CompanySummaryDecorator $companySummary,
        private int $totalMightGoodCount,
        private int $totalFootprintCount
    ) {
        parent::__construct();
    }

    abstract public function build(): static;

    protected function sanitize(): array
    {
        return [
            'company' => $this->sanitizeCompany($this->companySummary),
            'summary' => [
                'start'      => $this->companySummary->periodStart,
                'end'        => $this->companySummary->periodEnd,
                'mightGoods' => ['total' => $this->totalMightGoodCount],
                'footprints' => ['total' => $this->totalFootprintCount],
            ],
        ];
    }
}

// Concrete implementation
class WeeklyReportMailToPartner extends WeeklyReportMail
{
    public function build(): static
    {
        return $this->view('virtual_resources::emails.to_partner.weekly_report')
            ->text('virtual_resources::emails.to_partner.weekly_report_plain')
            ->subject($this->companySummary->name . ' 様の週間レポートお送りします【' . config('siteNames.partners') . '】');
    }
}
```

**When to use**: Reports sent to multiple user types, recurring notification emails.

## Notification Class Patterns

### Pattern 1: Basic Notification with Send Conditions

```php
<?php

namespace App\Notifications;

use App\Mail\JobofferApplied;
use App\Models\Application;
use App\Models\UserBase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ApplicationForConsumerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Application $application)
    {
    }

    public function via(UserBase $notifiable): array
    {
        if (!($notifiable instanceof User)) {
            return [];
        }

        $exclude = [];

        // Email not verified
        if (is_null($notifiable->email_verified_at)) {
            $exclude[] = 'mail';
        }
        // Dummy email
        elseif ($notifiable->isDummyEmail) {
            $exclude[] = 'mail';
        }
        // User disabled this notification type
        elseif (!$notifiable->setting->email['application']) {
            $exclude[] = 'mail';
        }

        return array_diff(['mail'], $exclude);
    }

    public function toMail(User $notifiable): JobofferApplied
    {
        return (new JobofferApplied($this->application))
            ->to($notifiable->email);
    }
}
```

**Key features**:
- Type check for recipient (`instanceof User`)
- Multiple send condition checks
- User preference respect

### Pattern 2: Multi-Recipient Notification

```php
<?php

namespace App\Notifications;

use App\Mail\NewApplicantHasComeToPartner;
use App\Mail\NewApplicantHasComeToAdministrator;
use App\Models\Application;
use App\Models\UserBase;
use App\Models\Partner;
use App\Models\Administrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ApplicationForReceiverNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Application $application)
    {
    }

    public function via(UserBase $notifiable): array
    {
        if ($notifiable instanceof Partner || $notifiable instanceof Administrator) {
            return ['mail'];
        }
        return [];
    }

    public function toMail(Partner | Administrator $notifiable):
        NewApplicantHasComeToPartner | NewApplicantHasComeToAdministrator
    {
        if ($notifiable instanceof Partner) {
            return (new NewApplicantHasComeToPartner($this->application))
                ->to($notifiable->email);
        }

        return (new NewApplicantHasComeToAdministrator($this->application))
            ->to($notifiable->email);
    }
}
```

**When to use**: Sending to company staff, admin notifications.

### Pattern 3: Unified Notification (Multi-Purpose)

For combining multiple notification purposes into one email:

```php
<?php

namespace App\Notifications;

use App\Mail\JobofferTempAppliedWithPasswordReset;
use App\Models\TempApplication;
use App\Models\UserBase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TempApplicationCreatedWithPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TempApplication $tempApplication,
        private readonly string $resetToken,
    ) {
    }

    public function via(UserBase $notifiable): array
    {
        if (!($notifiable instanceof User)) {
            return [];
        }
        return ['mail'];
    }

    public function toMail(User $notifiable): JobofferTempAppliedWithPasswordReset
    {
        return (new JobofferTempAppliedWithPasswordReset(
            $this->tempApplication,
            $this->resetToken,
        ))->to($notifiable->email);
    }
}
```

**When to use**: Temporary application + password reset, verification + welcome.

## Template Patterns

### HTML Template Structure

```twig
{% extends 'virtual_resources://views/emails/layouts/consumer_layout.twig' %}

{% block title %}
  Your Email Title
{% endblock %}

{% block content %}
  {{ user.name }}様
  <br>
  <br>いつも{{ sites.users.name }}をご利用いただき、ありがとうございます。
  <br>
  <br>【応募日】
  <br>{{ application.appliedDate | date('Y年m月d日') }}
  <br>
  <br>【企業名】
  <br>{{ company.name }}
  <br>
  <br>【職種名】
  <br>{{ joboffer.originalName }}
  <br>
  <br>{{ link_to("#{sites.users.url}mypage/?utm_source=newsletter&utm_medium=email&utm_campaign=your_campaign") }}
{% endblock %}
```

### Text Template Structure

```twig
{% extends 'virtual_resources://views/emails/layouts/consumer_layout_plain.twig' %}

{% block title %}Your Email Title{% endblock %}

{% block content %}{% autoescape false %}
  {{ user.name }}様

  いつも{{ sites.users.name }}をご利用いただき、ありがとうございます。

  【応募日】
  {{ application.appliedDate | date('Y年m月d日') }}

  【企業名】
  {{ company.name }}

  【職種名】
  {{ joboffer.originalName }}

  {{ sites.users.url }}mypage/?utm_source=newsletter&utm_medium=email&utm_campaign=your_campaign
{% endautoescape %}{% endblock %}
```

### Using Utility Macros

```twig
{% import 'emails/util.twig' as util %}

{% block content %}
  【給与】
  <br>{{ util.salaryKindAndRange(joboffer.salary) }}
{% endblock %}
```

## Common Patterns

### Auto-Detection of Recipient Type

```php
public function build(): static
{
    $user = $this->user;

    // Auto-detect recipient type
    $base = 'emails.to_' . $user->duty;  // users|partners|administrators
    $site = config('siteNames.' . $user->guard_name);

    return $this->view("virtual_resources::{$base}.verify")
        ->text("virtual_resources::{$base}.verify_plain")
        ->subject("メールアドレスの確認【{$site}】");
}
```

### Queue Configuration

All emails should implement `ShouldQueue` for async sending:

```php
class YourMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
}
```

### Error Handling in Listeners

```php
public function handle(ApplicationEvent $event): void
{
    try {
        $event->application->user->notify(
            new ApplicationForConsumerNotification($event->application)
        );
    } catch (\Throwable $throwable) {
        report($throwable);  // Always report errors
    }
}
```

### Multi-Receiver Send

```php
// Send to multiple company staff members
$receivers = $application->joboffer->company->notifyReceivers(['applications.view']);

if ($receivers->isEmpty()) {
    throw new WithContextException(
        new \Exception('No one receives this message'),
        $application->toArray()
    );
}

Notification::send($receivers, new ApplicationForReceiverNotification($application));
```

## Configuration Values

### Site Names

```php
config('siteNames.users');          // ジョブアンテナ沖縄
config('siteNames.partners');       // ジョブアンテナ沖縄 パートナーズ
config('siteNames.administrators'); // JOB ANTENNA Administrators
```

### Site URLs

```php
config('sites.consumer');      // Consumer frontend URL
config('sites.partner');       // Partner frontend URL
config('sites.administrator'); // Administrator frontend URL
```

### Operating Company

```php
config('jobantenna.operatingCompany.name');    // インタラクティブ株式会社
config('jobantenna.operatingCompany.address'); // 沖縄県宜野湾市大山3丁目11-32
```
