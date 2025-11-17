---
name: laravel-mail-creator
description: Create Laravel Mailable classes, Notification classes, Twig email templates, and comprehensive tests following JobAntenna project's established patterns. Use this skill when implementing new email functionality, creating email notifications, or writing tests for email features in the JobAntenna Laravel project.
---

# Laravel Mail Creator

## Table of Contents

1. [Overview](#overview)
2. [When to Use This Skill](#when-to-use-this-skill)
3. [Architecture Overview](#architecture-overview)
4. [Creating a New Mailable](#creating-a-new-mailable)
   - [Step 1: Define the Mailable Class](#step-1-define-the-mailable-class)
   - [Step 2: Choose Appropriate Sanitize Traits](#step-2-choose-appropriate-sanitize-traits)
   - [Step 3: Implement Auto-Detection Pattern](#step-3-implement-auto-detection-pattern-optional)
5. [Creating a New Notification](#creating-a-new-notification)
   - [Step 1: Define the Notification Class](#step-1-define-the-notification-class)
   - [Step 2: Wire Up Event Listener](#step-2-wire-up-event-listener)
6. [Writing Mail Templates](#writing-mail-templates)
   - [Step 1: Create HTML Template](#step-1-create-html-template)
   - [Step 2: Create Text Template](#step-2-create-text-template)
   - [Common Patterns and Special Handling](#common-patterns-and-special-handling)
   - [Step 3: Create VirtualResource Override Files](#step-3-create-virtualresource-override-files)
7. [Writing Tests for Mails](#writing-tests-for-mails)
   - [Step 1: Create Test Class](#step-1-create-test-class)
   - [Step 2: Create Expected Output Files](#step-2-create-expected-output-files)
   - [Step 3: Test Sanitize Method](#step-3-test-sanitize-method-optional)
8. [Default Configuration](#default-configuration)
9. [Resources](#resources)

## Overview

This skill provides comprehensive guidance for implementing email functionality in the JobAntenna Laravel 9.x project, following established architectural patterns. It covers creating Mailable classes with Sanitize Traits, Notification classes with channel selection logic, Twig templates for both HTML and plain text versions, and writing thorough tests using the project's custom MailFake infrastructure.

## When to Use This Skill

Use this skill when:
- Implementing new email notifications (user registration, application submitted, password reset, etc.)
- Creating Mailable classes for sending emails
- Writing Notification classes for multi-channel delivery
- Designing email templates (HTML and plain text versions)
- Writing tests for email functionality
- Understanding the project's email architecture patterns

## Architecture Overview

The JobAntenna email system uses a two-layer architecture:

```
Event → Listener → Notification → Mailable → Template
```

**Notification Layer**: Determines send conditions and channel selection (should the email be sent?)
**Mailable Layer**: Prepares data and builds template (what content to send?)

### Key Components

1. **Mailable Classes** (`app/Mail/`) - Define email content and templates
2. **Notification Classes** (`app/Notifications/`) - Handle send conditions and channel routing
3. **Sanitize Traits** (`app/Mail/Traits/`) - Reusable data transformation logic
4. **Templates** (`resources/views/emails/`) - Twig templates for HTML and text versions
5. **Tests** (`tests/Feature/Mail/`) - Comprehensive test coverage with custom MailFake

## Creating a New Mailable

### Step 1: Define the Mailable Class

Create a new Mailable class in `server/app/Mail/`:

**Basic Structure:**
```php
<?php

namespace App\Mail;

use App\Mail\Traits\SanitizeUser;
use App\Mail\Traits\SanitizeCompany;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class YourMailableName extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use SanitizeUser, SanitizeCompany;  // Include relevant Sanitize Traits

    public function __construct(
        private Application $application
    ) {
        parent::__construct();
    }

    public function build(): static
    {
        $siteName = config('siteNames.users');

        return $this->view('virtual_resources::emails.to_consumer.your_template')
            ->text('virtual_resources::emails.to_consumer.your_template_plain')
            ->subject('Your Email Subject【' . $siteName . '】');
    }

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'user'        => $this->sanitizeUserForMe($this->application->user),
            'company'     => $this->sanitizeCompany($this->application->joboffer->company),
        ];
    }
}
```

**検証**:
- クラスファイルが`server/app/Mail/YourMailableName.php`に作成されているか確認
- `php artisan list`でMailableクラスが認識されているか確認（mailコマンド一覧に表示される）
- 構文エラーがないか確認: `php artisan tinker`で`new App\Mail\YourMailableName(...)`を実行

**エラー時**:
- **名前空間エラー**: `composer dump-autoload`を実行してオートロードを再生成
- **クラスが見つからない**: ファイル名とクラス名が一致しているか確認
- **親クラスエラー**: `use Illuminate\Mail\Mailable`のインポート文を確認

### Step 2: Choose Appropriate Sanitize Traits

Available Sanitize Traits (in `app/Mail/Traits/`):
- `SanitizeApplication` - Application data
- `SanitizeUser` - User profile (with `sanitizeUser()` for anonymous and `sanitizeUserForMe()` for authenticated)
- `SanitizeCompany` - Company information
- `SanitizeJoboffer` - Job offer details
- `SanitizeMessageRoom` - Message room data
- `SanitizeTempApplication` - Temporary application data
- `SanitizeAddress` - Address information
- `SanitizeTerm` - Term definitions (salary types, etc.)

Refer to `references/sanitize-traits-reference.md` for detailed usage.

**良い例と悪い例**:

<details>
<summary>✅ 良い例: 必要なTraitsのみをインポート</summary>

```php
class ApplicationSubmittedMail extends Mailable
{
    use SanitizeApplication, SanitizeUser, SanitizeCompany;

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'user'        => $this->sanitizeUserForMe($this->application->user),
            'company'     => $this->sanitizeCompany($this->application->joboffer->company),
        ];
    }
}
```

**理由**: 実際に使用するSanitize Traitsのみをインポートし、`sanitize()`メソッドで明確に使用しています。
</details>

<details>
<summary>❌ 悪い例: 未使用のTraitsをインポート</summary>

```php
class ApplicationSubmittedMail extends Mailable
{
    // すべてのTraitsをインポート（未使用のものも含む）
    use SanitizeApplication, SanitizeUser, SanitizeCompany,
        SanitizeJoboffer, SanitizeMessageRoom, SanitizeTempApplication;

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'user'        => $this->sanitizeUserForMe($this->application->user),
        ];
    }
}
```

**問題点**: 未使用のTraitsをインポートすることで、コードの意図が不明確になり、保守性が低下します。
</details>

<details>
<summary>❌ 悪い例: Traitsを使わず手動でデータ整形</summary>

```php
class ApplicationSubmittedMail extends Mailable
{
    protected function sanitize(): array
    {
        return [
            'user' => [
                'name' => $this->application->user->first_name . ' ' . $this->application->user->last_name,
                'email' => $this->application->user->email,
            ],
        ];
    }
}
```

**問題点**: Sanitize Traitsを使用せずに手動でデータ整形すると、共通ロジックの再利用ができず、コードの重複が発生します。
</details>

### Step 3: Implement Auto-Detection Pattern (Optional)

For multi-recipient types (consumer/partner/administrator), implement auto-detection:

```php
public function build(): static
{
    $user = $this->user;

    // duty = 'users' | 'partners' | 'administrators'
    $base = 'emails.to_' . $user->duty;

    // guard_name = 'users' | 'partners' | 'administrators'
    $site = config('siteNames.' . $user->guard_name);

    return $this->view("virtual_resources::{$base}.verify")
        ->text("virtual_resources::{$base}.verify_plain")
        ->subject("メールアドレスの確認【{$site}】");
}
```

## Creating a New Notification

### Step 1: Define the Notification Class

Create a new Notification class in `server/app/Notifications/`:

```php
<?php

namespace App\Notifications;

use App\Mail\YourMailableName;
use App\Models\Application;
use App\Models\UserBase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class YourNotificationName extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Application $application
    ) {
    }

    /**
     * Determine notification delivery channels
     */
    public function via(UserBase $notifiable): array
    {
        if (!($notifiable instanceof User)) {
            return [];
        }

        // Check send conditions
        $exclude = [];

        if (is_null($notifiable->email_verified_at)) {
            $exclude[] = 'mail';
        } elseif ($notifiable->isDummyEmail) {
            $exclude[] = 'mail';
        } elseif (!$notifiable->setting->email['application']) {
            $exclude[] = 'mail';  // User disabled this notification type
        }

        return array_diff(['mail'], $exclude);
    }

    /**
     * Build mail content
     */
    public function toMail(User $notifiable): YourMailableName
    {
        return (new YourMailableName($this->application))
            ->to($notifiable->email);
    }
}
```

**検証**:
- Notificationクラスが`server/app/Notifications/YourNotificationName.php`に作成されているか確認
- `via()`メソッドが正しい送信条件チェックを実装しているか確認
- `toMail()`メソッドが適切なMailableインスタンスを返すか確認

**エラー時**:
- **送信条件が正しく動作しない**: `via()`メソッドのロジックを確認、`dd($exclude)`でデバッグ
- **メールが送信されない**: ユーザーの`email_verified_at`、`isDummyEmail`、通知設定を確認
- **型エラー**: `UserBase`と`User`の型チェックを確認

**良い例と悪い例**:

<details>
<summary>✅ 良い例: 段階的な条件チェック</summary>

```php
public function via(UserBase $notifiable): array
{
    if (!($notifiable instanceof User)) {
        return [];
    }

    $exclude = [];

    if (is_null($notifiable->email_verified_at)) {
        $exclude[] = 'mail';
    } elseif ($notifiable->isDummyEmail) {
        $exclude[] = 'mail';
    } elseif (!$notifiable->setting->email['application']) {
        $exclude[] = 'mail';
    }

    return array_diff(['mail'], $exclude);
}
```

**理由**: 各条件を個別にチェックし、どの条件で除外されたかが明確です。デバッグも容易です。
</details>

<details>
<summary>❌ 悪い例: 複雑な条件を一つにまとめる</summary>

```php
public function via(UserBase $notifiable): array
{
    if ($notifiable instanceof User &&
        !is_null($notifiable->email_verified_at) &&
        !$notifiable->isDummyEmail &&
        $notifiable->setting->email['application']) {
        return ['mail'];
    }
    return [];
}
```

**問題点**: 複雑な条件が一つにまとめられており、どの条件で除外されたかが不明確です。デバッグが困難です。
</details>

<details>
<summary>❌ 悪い例: 型チェックを省略</summary>

```php
public function via($notifiable): array
{
    // UserBaseからUserへの型チェックなし
    return is_null($notifiable->email_verified_at) ? [] : ['mail'];
}
```

**問題点**: 型チェックを省略すると、予期しない型が渡された際にエラーが発生します。すべての必要な条件をチェックしていません。
</details>

### Step 2: Wire Up Event Listener

Register the notification in `app/Listeners/`:

```php
<?php

namespace App\Listeners;

use App\Events\YourEvent;
use App\Notifications\YourNotificationName;

class YourListener
{
    public function handle(YourEvent $event): void
    {
        try {
            $event->model->user->notify(
                new YourNotificationName($event->model)
            );
        } catch (\Throwable $throwable) {
            report($throwable);
        }
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    YourEvent::class => [
        YourListener::class,
    ],
];
```

## Writing Mail Templates

### Step 1: Create HTML Template

Create in `resources/views/emails/to_{consumer|partner|administrator}/`:

**HTML Version** (`your_template.twig`):
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
  <br>{{ link_to("#{sites.users.url}mypage/?utm_source=newsletter&utm_medium=email&utm_campaign=your_campaign") }}
{% endblock %}
```

**検証**:
- テンプレートファイルが`server/resources/views/emails/to_{recipient}/your_template.twig`に作成されているか確認
- Twigの構文エラーがないか確認: メール送信テストを実行して正常にレンダリングされるか検証
- レイアウトの継承が正しいか確認: `extends`文が適切なレイアウトを参照しているか
- 変数が正しく渡されているか確認: `{{ user.name }}`などの変数がMailableの`sanitize()`メソッドで定義されているか

**エラー時**:
- **レンダリングエラー**: Twigのキャッシュをクリア `php artisan view:clear`
- **変数が未定義**: Mailableクラスの`sanitize()`メソッドで該当変数を返しているか確認
- **レイアウトが見つからない**: `extends`パスが正しいか確認（`virtual_resources::`プレフィックスを確認）
- **フィルターエラー**: `date`などのフィルターの使用方法が正しいか確認

### Step 2: Create Text Template

**Text Version** (`your_template_plain.twig`):
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

  {{ sites.users.url }}mypage/?utm_source=newsletter&utm_medium=email&utm_campaign=your_campaign
{% endautoescape %}{% endblock %}
```

**検証**:
- プレーンテキストファイルが`server/resources/views/emails/to_{recipient}/your_template_plain.twig`に作成されているか確認
- HTML版と内容が一致しているか確認: 同じ情報が含まれているが、HTMLタグがないこと
- `{% autoescape false %}`が適切に使用されているか確認: `content`ブロック内で使用されているか
- 改行と整形が適切か確認: プレーンテキストとして読みやすい形式になっているか

**エラー時**:
- **HTMLタグが残っている**: `<br>`などのHTMLタグを改行に置き換える
- **エスケープ問題**: `{% autoescape false %}`を`content`ブロック内に配置しているか確認
- **レイアウト参照エラー**: `_plain`サフィックスのレイアウトを使用しているか確認
- **URL表示問題**: プレーンテキスト版では`link_to()`ではなく直接URLを表示

### Common Patterns and Special Handling

メールテンプレートでよく使用される特殊なパターンと処理方法：

#### 1. 日付のフォーマット

**基本的な日付表示**:
```twig
{# 日本語形式の日付 #}
{{ application.appliedDate | date('Y年m月d日') }}

{# 日時を含む場合 #}
{{ application.appliedAt | date('Y年m月d日 H:i') }}

{# スラッシュ区切り（第2引数falseでタイムゾーン変換なし） #}
{{ user.birthday | date('Y/m/d', false) }}

{# 年月のみ #}
{{ educationHistory.graduatedAt | date('Y/m', false) }}
```

#### 2. リンクの扱い

**HTML版**: `link_to()` 関数を使用してクリック可能なリンクを生成
```twig
{# 基本的なリンク #}
{{ link_to(joboffer.url) }}

{# UTMパラメータ付きリンク #}
{{ link_to("#{sites.users.url}mypage/message/#{messageRoom.id}/?utm_source=newsletter&utm_medium=email&utm_campaign=apply") }}
```

**Plain版**: URLを直接出力（`link_to()`は使わない）
```twig
{# プレーンテキストではURLを直接表示 #}
{{ sites.users.url }}mypage/message/{{ messageRoom.id }}/?utm_source=newsletter&utm_medium=email&utm_campaign=apply
```

#### 3. テキストの改行処理

**改行をHTMLの`<br>`タグに変換**:
```twig
{# 複数行のテキストをHTMLで表示 #}
{{ joboffer.work|nl2br }}
{{ joboffer.request|nl2br }}
```

**Plain版では改行変換不要**:
```twig
{# プレーンテキストではそのまま出力 #}
{{ joboffer.work }}
```

#### 4. 配列データの結合

**区切り文字を指定して結合**:
```twig
{# カンマ区切り #}
{{ joboffer.areas | join('、') }}

{# スペース区切り #}
{{ [address.region, address.locality, address.street, address.building] | join() }}
```

#### 5. 数値のフォーマット

**カンマ区切りの数値表示**:
```twig
{# util.twigで使用される例 #}
{{ salary.lowest | number_format }}円
```

#### 6. Utilヘルパーの使用

テンプレートの先頭でutilマクロをインポート:
```twig
{% import 'emails/util.twig' as util %}
```

**給与レンジの表示**:
```twig
{{ util.salaryKindAndRange(joboffer.salary) }}
{# 出力例: "時給 2,280 〜 3,000円" #}
```

#### 7. 条件付き表示

**値が存在する場合のみ表示**:
```twig
{# 三項演算子 #}
{{ jobHistory.endedAt is empty ? '' : jobHistory.endedAt | date('Y/m', false) }}

{# if文 #}
{% if not loop.last %}
  <br>—————————————
{% endif %}
```

#### 8. ループ処理

**配列データの繰り返し表示**:
```twig
{% for newPostJoboffer in newPostJoboffersFromFollowCompanies %}
  <br>——————————————
  <br>{{ newPostJoboffer.title }}
  <br>{{ util.salaryKindAndRange(newPostJoboffer.salary) }}
  <br>{{ link_to("#{sites.users.url}at/#{newPostJoboffer.companyId}/offer/#{newPostJoboffer.id}/") }}
{% endfor %}
```

### Available Layout Templates

- `consumer_layout.twig` / `consumer_layout_plain.twig` - For job seekers
- `partner_layout.twig` / `partner_layout_plain.twig` - For companies
- `administrator_layout.twig` / `administrator_layout_plain.twig` - For admins

Refer to `references/template-reference.md` for detailed template patterns.

### Step 3: Create VirtualResource Override Files

このプロジェクトでは、メールテンプレートをVirtualResourceテーブルに登録して管理します。親テンプレート（`resources/views/emails/`）を継承するオーバーライドファイルを作成します。

**配置先**: `database/seeders/data/virtual_resources/views/emails/to_{recipient}/`

**HTML版オーバーライド** (`your_template.twig`):
```twig
{% extends 'emails/to_consumer/your_template.twig' %}

{% block title %}{{ parent() }}{% endblock %}

{% block content %}
  {{ parent() }}
{% endblock %}
```

**テキスト版オーバーライド** (`your_template_plain.twig`):
```twig
{% extends 'emails/to_consumer/your_template_plain.twig' %}

{% block title %}{{ parent() }}{% endblock %}

{% block content %}{% autoescape false %}
  {{ parent() }}
{% endautoescape %}{% endblock %}
```

**目的**:
- VirtualResourceテーブルに格納されるテンプレート
- 通常は親テンプレート（`resources/views/emails/`）をそのまま継承
- 必要に応じてブロックをオーバーライドしてカスタマイズ可能

**VirtualResourceへの登録**:
- オーバーライドファイル作成後、既存のシーダーを使用してVirtualResourceテーブルに登録します
- マイグレーションの作成は不要です

**検証**:
- オーバーライドファイルが`database/seeders/data/virtual_resources/views/emails/to_{recipient}/`に作成されているか確認
- シーダー実行後、`virtual_resources`テーブルにレコードが登録されているか確認: `SELECT * FROM virtual_resources WHERE path LIKE '%your_template%'`

## Writing Tests for Mails

### Step 1: Create Test Class

Create in `tests/Feature/Mail/`:

```php
<?php

namespace Tests\Feature\Mail;

use App\Mail\YourMailableName;
use App\Models\Application;
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
            ->setAttribute('created_at', Carbon::now()->subDays(7)) // 7日前
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

    // Additional seed methods...
}
```

**検証**:
- テストクラスが`server/tests/Feature/Mail/YourMailableNameTest.php`に作成されているか確認
- テストが実行可能か確認: `phpunit --filter=YourMailableNameTest`で正常に実行されるか
- `beforeTest()`で必要なシーダーが実行されているか確認
- `Mail::fake()`を使用してメール送信をモック化しているか確認
- 期待値ファイルのパスが正しいか確認

**エラー時**:
- **シーダーエラー**: `VirtualResourcesTableSeeder`と`TermsTableSeeder`が実行されているか確認
- **アサーション失敗**: 期待値ファイルの内容と実際の出力を比較して差分を確認
- **MailFakeエラー**: `Tests\Facades\Mail`をインポートしているか確認（`Illuminate\Support\Facades\Mail`ではない）
- **パスエラー**: `base_path()`を使用して正しいテストデータディレクトリを参照しているか確認

**良い例と悪い例**:

<details>
<summary>✅ 良い例: 相対日付を使用したテストデータ</summary>

```php
private function seedApplication(): Application
{
    return (new Application())
        ->setAttribute('created_at', Carbon::now()->subDays(7)) // 7日前
        ->setRelation('user', $this->seedUser())
        ->setRelation('joboffer', $this->seedJoboffer());
}
```

**理由**: 相対日付を使用することで、テストが常に一貫した結果を返し、時間が経過しても期待値ファイルを更新する必要がありません。
</details>

<details>
<summary>❌ 悪い例: 固定日付を使用したテストデータ</summary>

```php
private function seedApplication(): Application
{
    return (new Application())
        ->setAttribute('created_at', new Carbon('2024-01-01 12:00:00'))
        ->setRelation('user', $this->seedUser())
        ->setRelation('joboffer', $this->seedJoboffer());
}
```

**問題点**: 固定日付を使用すると、期待値ファイルも固定日付に依存し、年が変わるたびに更新が必要になります。
</details>

<details>
<summary>❌ 悪い例: 実際のFacadeを使用</summary>

```php
use Illuminate\Support\Facades\Mail; // ❌ 間違ったFacade

public function html(): void
{
    Mail::fake(); // プロジェクト独自のMailFakeではない

    Mail::to('test@example.com')
        ->send(new YourMailableName($this->seedApplication()));

    // カスタムアサーションが使えない
}
```

**問題点**: Laravelの標準`Mail` Facadeを使用すると、プロジェクト独自の`assertEqualHtml()`などのカスタムアサーションが使用できません。
</details>

### Step 2: Create Expected Output Files

Create expected HTML and text files in `tests/data/email_template_test/to_{consumer|partner|administrator}/`:

- `your_template.html` - Expected HTML output
- `your_template_plain.txt` - Expected text output

**検証**:
- 期待値ファイルが`server/tests/data/email_template_test/to_{recipient}/`に作成されているか確認
- HTMLファイルの内容が実際のメール出力と一致するか確認: テストを一度実行して実際の出力を確認
- プレーンテキストファイルの内容が適切か確認: HTMLタグが含まれていないこと
- ファイル名がテンプレート名と一致しているか確認

**エラー時**:
- **テスト失敗**: 実際のメール出力をログに出力して期待値ファイルを更新
- **ファイルが見つからない**: パスとファイル名が正しいか確認（`to_consumer`、`to_partner`、`to_administrator`のいずれか）
- **差分がある**: `spaceless()`関数の動作を理解し、空白の扱いを確認
- **文字コード問題**: ファイルがUTF-8で保存されているか確認

### Step 3: Test Sanitize Method (Optional)

```php
/**
 * @test
 */
public function sanitizeReturnsCorrectDataStructure(): void
{
    $mail = new YourMailableName($application);

    $reflection = new \ReflectionClass($mail);
    $sanitizeMethod = $reflection->getMethod('sanitize');
    $sanitizeMethod->setAccessible(true);

    $result = $sanitizeMethod->invoke($mail);

    self::assertArrayHasKey('application', $result);
    self::assertArrayHasKey('user', $result);
    self::assertArrayHasKey('company', $result);
}
```

**検証**:
- `sanitize()`メソッドのテストが正常に実行されるか確認
- リフレクションAPIを使用してプライベートメソッドにアクセスできているか確認
- 返り値の配列構造が期待通りか確認: 必要なキーがすべて含まれているか
- Sanitize Traitsが正しく適用されているか確認

**エラー時**:
- **メソッドが見つからない**: `sanitize()`メソッドがMailableクラスに定義されているか確認
- **アクセスエラー**: `setAccessible(true)`が呼ばれているか確認
- **キーが存在しない**: Sanitize Traitsのメソッドが正しく呼ばれているか確認
- **型エラー**: `sanitize()`の返り値が配列であることを確認

### Available Custom Assertions

The project's custom `MailFake` provides:
- `assertEqualHtml($html, $adjuster)` - Compare HTML body
- `assertEqualText($text, $adjuster)` - Compare text body
- `assertEqualSubject($subject)` - Compare subject line

Refer to `references/mail-test-patterns.md` for comprehensive testing patterns.

## Default Configuration

このスキルで作成されるメール機能は、以下のデフォルト設定に従います。

### キューイング動作

**デフォルト**: すべてのメール送信はキューで非同期実行されます。

- Mailableクラスは`ShouldQueue`インターフェースを実装
- Notificationクラスも`ShouldQueue`インターフェースを実装
- `Queueable`と`SerializesModels`トレイトを使用

**変更方法**: 同期送信が必要な場合は`ShouldQueue`インターフェースを削除してください。

### メール送信元アドレス

**デフォルト**: `config/mail.php`で設定されたグローバル送信元を使用

```php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Example'),
],
```

**変更方法**: 特定のMailableで異なる送信元を使用する場合は`build()`メソッドで`->from()`を追加してください。

### テンプレートパス規約

**デフォルト**: `resources/views/emails/to_{recipient}/`ディレクトリ構造

- `to_consumer/` - 求職者向けメール
- `to_partner/` - 企業向けメール
- `to_administrator/` - 管理者向けメール

各ディレクトリ内に`{template_name}.twig`（HTML）と`{template_name}_plain.twig`（テキスト）を配置します。

### レイアウトテンプレート

**デフォルト**: `virtual_resources`パッケージ内のレイアウトを継承

- `virtual_resources::emails.layouts.consumer_layout` - 求職者向けHTML
- `virtual_resources::emails.layouts.consumer_layout_plain` - 求職者向けテキスト
- `virtual_resources::emails.layouts.partner_layout` - 企業向けHTML
- `virtual_resources::emails.layouts.partner_layout_plain` - 企業向けテキスト
- `virtual_resources::emails.layouts.administrator_layout` - 管理者向けHTML
- `virtual_resources::emails.layouts.administrator_layout_plain` - 管理者向けテキスト

### サブジェクトの命名規則

**デフォルト**: サイト名を末尾に追加

```php
->subject('メールタイトル【' . config('siteNames.users') . '】')
```

### テストデータの配置

**デフォルト**: `tests/data/email_template_test/to_{recipient}/`

期待値ファイルは以下の命名規則に従います:
- `{template_name}.html` - HTML版の期待値
- `{template_name}_plain.txt` - テキスト版の期待値

### Sanitize Traitsのインポート

**デフォルト**: 必要なSanitize Traitsのみをインポート

一般的な組み合わせ:
- 応募関連: `SanitizeApplication`, `SanitizeUser`, `SanitizeCompany`, `SanitizeJoboffer`
- メッセージ関連: `SanitizeUser`, `SanitizeMessageRoom`
- ユーザー登録関連: `SanitizeUser`

## Resources

### references/

Detailed implementation and testing guides:

- `mail-implementation-patterns.md` - Comprehensive Mailable and Notification patterns
- `mail-test-patterns.md` - Testing strategies, MailFake usage, and assertions
- `sanitize-traits-reference.md` - All available Sanitize Traits and their usage

Load these references as needed for detailed information on specific patterns.

### assets/

Ready-to-use templates:

- `templates/mailable-template.php` - Mailable class boilerplate
- `templates/notification-template.php` - Notification class boilerplate
- `templates/mail-test-template.php` - Test class boilerplate
- `templates/twig/` - Email template examples (HTML and text)

Copy and customize these templates for new email implementations.

### scripts/

Automation utilities:

- `generate_mail_scaffold.py` - Generate complete mail implementation (Mailable + Notification + Templates + Tests)

Execute scripts to accelerate development workflow.
