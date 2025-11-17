# Sanitize Traits Reference

This reference document provides comprehensive information about all available Sanitize Traits for email data transformation in the JobAntenna project.

## Overview

Sanitize Traits provide reusable data transformation logic for email templates. They convert complex Eloquent models into simple arrays suitable for email rendering.

## Available Traits

### SanitizeUser

**Location**: `app/Mail/Traits/SanitizeUser.php`

Transforms User model data for email display.

**Methods**:

```php
protected function sanitizeUser(User $user, ?Company $company = null): array
```

For displaying user information to **others** (anonymized when needed):

```php
return [
    'id'     => $user->id,
    'name'   => $company ? $user->mayBeName($company) : $user->anonymousLabel,
    'age'    => $user->age,
    'gender' => $user->genderLabel,
];
```

```php
protected function sanitizeUserForMe(User|UserFollowCompanyDecorator $user): array
```

For displaying user information to **themselves** (full name):

```php
return [
    'id'     => $user->id,
    'name'   => $user->name,  // Full name, not anonymized
    'age'    => $user->age,
    'gender' => $user->genderLabel,
];
```

**Usage**:

```php
class JobofferApplied extends Mailable
{
    use SanitizeUser;

    protected function sanitize(): array
    {
        return [
            'user' => $this->sanitizeUserForMe($this->application->user),
        ];
    }
}
```

### SanitizeCompany

**Location**: `app/Mail/Traits/SanitizeCompany.php`

Transforms Company model data.

**Method**:

```php
protected function sanitizeCompany(Company $company): array
```

Returns:

```php
return [
    'id'   => $company->id,
    'name' => $company->name,
    // Additional company fields...
];
```

**Usage**:

```php
class JobofferApplied extends Mailable
{
    use SanitizeCompany;

    protected function sanitize(): array
    {
        return [
            'company' => $this->sanitizeCompany($this->application->joboffer->company),
        ];
    }
}
```

### SanitizeApplication

**Location**: `app/Mail/Traits/SanitizeApplication.php`

Transforms Application model data.

**Method**:

```php
protected function sanitizeApplication(Application $application): array
```

Returns:

```php
return [
    'id'          => $application->id,
    'appliedDate' => $application->created_at,
    'status'      => $application->status,
    // Additional application fields...
];
```

**Usage**:

```php
class JobofferApplied extends Mailable
{
    use SanitizeApplication;

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
        ];
    }
}
```

### SanitizeJoboffer

**Location**: `app/Mail/Traits/SanitizeJoboffer.php`

Transforms Joboffer model data.

**Method**:

```php
protected function sanitizeJoboffer(Joboffer $joboffer): array
```

Returns:

```php
return [
    'id'           => $joboffer->id,
    'title'        => $joboffer->title,
    'originalName' => $joboffer->original_name,
    'salary'       => [
        'kind'    => $joboffer->salary->kind,
        'lowest'  => $joboffer->salary->lowest,
        'highest' => $joboffer->salary->highest,
    ],
    'welfare'      => $joboffer->welfare,
    // Additional joboffer fields...
];
```

**Usage**:

```php
class JobofferApplied extends Mailable
{
    use SanitizeJoboffer;

    protected function sanitize(): array
    {
        return [
            'joboffer' => $this->sanitizeJoboffer($this->application->joboffer),
        ];
    }
}
```

### SanitizeMessageRoom

**Location**: `app/Mail/Traits/SanitizeMessageRoom.php`

Transforms MessageRoom model data.

**Method**:

```php
protected function sanitizeMessageRoom(MessageRoom $messageRoom): array
```

Returns:

```php
return [
    'id' => $messageRoom->id,
    // Additional message room fields...
];
```

**Usage**:

```php
class JobofferApplied extends Mailable
{
    use SanitizeMessageRoom;

    protected function sanitize(): array
    {
        return [
            'messageRoom' => $this->sanitizeMessageRoom($this->application->messageRoom),
        ];
    }
}
```

### SanitizeTempApplication

**Location**: `app/Mail/Traits/SanitizeTempApplication.php`

Transforms TempApplication model data.

**Method**:

```php
protected function sanitizeTempApplication(TempApplication $tempApplication): array
```

Returns:

```php
return [
    'id'       => $tempApplication->id,
    'provider' => $tempApplication->provider,
    // Additional temp application fields...
];
```

**Usage**:

```php
class JobofferTempApplied extends Mailable
{
    use SanitizeTempApplication;

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeTempApplication($this->tempApplication),
        ];
    }
}
```

### SanitizeAddress

**Location**: `app/Mail/Traits/SanitizeAddress.php`

Transforms single Address model data.

**Method**:

```php
protected function sanitizeAddress(Address $address): array
```

Returns:

```php
return [
    'id'         => $address->id,
    'prefecture' => $address->prefecture,
    'city'       => $address->city,
    'street'     => $address->street,
    // Additional address fields...
];
```

### SanitizeAddresses

**Location**: `app/Mail/Traits/SanitizeAddresses.php`

Transforms collections of Address models.

**Method**:

```php
protected function sanitizeAddresses(Collection $addresses): array
```

Returns array of sanitized addresses.

### SanitizeTerm

**Location**: `app/Mail/Traits/SanitizeTerm.php`

Transforms term definitions (salary types, employment types, etc.).

**Methods**:

```php
protected function sanitizeSalaryKind(SalaryKind $salaryKind): array
protected function sanitizeEmploymentType(EmploymentType $employmentType): array
```

Returns:

```php
return [
    'id'   => $term->id,
    'name' => $term->name,
];
```

### SanitizeMightGoodUsageHistory

**Location**: `app/Mail/Traits/SanitizeMightGoodUsageHistory.php`

Transforms MightGoodUsageHistory model data.

**Method**:

```php
protected function sanitizeMightGoodUsageHistory(MightGoodUsageHistory $history): array
```

## Combining Multiple Traits

Mailables can use multiple Sanitize Traits:

```php
<?php

namespace App\Mail;

use App\Mail\Traits\SanitizeApplication;
use App\Mail\Traits\SanitizeCompany;
use App\Mail\Traits\SanitizeUser;
use App\Mail\Traits\SanitizeJoboffer;
use App\Mail\Traits\SanitizeMessageRoom;

class JobofferApplied extends Mailable
{
    use SanitizeApplication, SanitizeCompany, SanitizeUser, SanitizeJoboffer, SanitizeMessageRoom;

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

## Custom Sanitize Methods

You can define custom sanitize methods in your Mailable class:

```php
<?php

namespace App\Mail;

class CreateUserDelegatorMail extends Mailable
{
    use SanitizeUser;

    protected function sanitize(): array
    {
        $educationHistories = $this->user->educationHistories()
            ->orderByRaw('graduated_at is null desc')
            ->orderBy('graduated_at', 'desc')
            ->get();

        $jobHistories = $this->user->jobHistories()
            ->orderByRaw('ended_at is null desc')
            ->orderBy('ended_at', 'desc')
            ->orderBy('started_at', 'desc')
            ->get();

        return [
            'user'               => $this->sanitizeUser($this->user),
            'educationHistories' => $this->sanitizeEducationHistories($educationHistories),
            'jobHistories'       => $this->sanitizeJobHistories($jobHistories),
        ];
    }

    private function sanitizeEducationHistories(Collection $educationHistories): array
    {
        return $educationHistories->map(
            fn (UserEducationHistory $eh) => [
                'schoolName'    => $eh->schoolName,
                'facultyName'   => $eh->facultyName,
                'departmentName' => $eh->departmentName,
                'graduatedAt'   => $eh->graduated_at,
            ]
        )->toArray();
    }

    private function sanitizeJobHistories(Collection $jobHistories): array
    {
        return $jobHistories->map(
            fn (UserJobHistory $jh) => [
                'company'          => $jh->company,
                'position'         => $jh->position,
                'startedAt'        => $jh->started_at,
                'endedAt'          => $jh->ended_at,
                'businessCategory' => $jh->businessCategory?->name,
                'jobCategory'      => $jh->jobCategory?->name,
            ]
        )->toArray();
    }
}
```

## Best Practices

### 1. Use Appropriate Sanitize Method

Choose the right method based on who will see the data:

```php
// ✅ Good - User sees their own data
'user' => $this->sanitizeUserForMe($this->application->user)

// ✅ Good - Company sees applicant data (may be anonymized)
'user' => $this->sanitizeUser($this->application->user, $company)

// ❌ Bad - Exposing full name to company when anonymization is needed
'user' => $this->sanitizeUserForMe($this->application->user)  // Wrong context
```

### 2. Keep Sanitize Methods Focused

Each method should return only the data needed for the email:

```php
// ✅ Good - Returns only necessary fields
protected function sanitizeApplication(Application $application): array
{
    return [
        'id'          => $application->id,
        'appliedDate' => $application->created_at,
        'status'      => $application->status,
    ];
}

// ❌ Bad - Returns entire model
protected function sanitizeApplication(Application $application): array
{
    return $application->toArray();  // Too much data
}
```

### 3. Handle Null Values Gracefully

Use null-safe operators:

```php
// ✅ Good
'businessCategory' => $jh->businessCategory?->name,
'jobCategory'      => $jh->jobCategory?->name,

// ❌ Bad - May throw null pointer exception
'businessCategory' => $jh->businessCategory->name,
```

### 4. Transform Dates Appropriately

Don't pass raw Carbon instances to templates:

```php
// ✅ Good - Let template format the date
'appliedDate' => $application->created_at,

// ❌ Bad - Formatting in Mailable
'appliedDate' => $application->created_at->format('Y年m月d日'),
```

Then in template:

```twig
{{ application.appliedDate | date('Y年m月d日') }}
```

### 5. Reuse Existing Traits

Before creating new sanitize methods, check if existing traits can be reused:

```php
// ✅ Good - Reusing existing traits
use SanitizeUser, SanitizeCompany, SanitizeJoboffer;

protected function sanitize(): array
{
    return [
        'user'     => $this->sanitizeUserForMe($this->user),
        'company'  => $this->sanitizeCompany($this->company),
        'joboffer' => $this->sanitizeJoboffer($this->joboffer),
    ];
}

// ❌ Bad - Reinventing the wheel
protected function sanitize(): array
{
    return [
        'user' => [
            'id'   => $this->user->id,
            'name' => $this->user->name,
            // ... duplicating SanitizeUser logic
        ],
    ];
}
```
