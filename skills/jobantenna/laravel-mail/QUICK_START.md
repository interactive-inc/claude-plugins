# Laravel Mail クイックスタートガイド

このガイドでは、Laravel メール機能の実装手順をステップバイステップで説明します。

## 目次

1. [Creating a New Mailable](#creating-a-new-mailable)
2. [Creating a New Notification](#creating-a-new-notification)
3. [Writing Mail Templates](#writing-mail-templates)
4. [Writing Tests for Mails](#writing-tests-for-mails)

---

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
- `php artisan list`でMailableクラスが認識されているか確認(mailコマンド一覧に表示される)
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
    use SanitizeApplication, SanitizeUser, SanitizeCompany, SanitizeJoboffer, SanitizeMessageRoom;

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

**問題**: `SanitizeJoboffer`と`SanitizeMessageRoom`をインポートしているが、`sanitize()`メソッドで使用していません。不要なインポートは混乱を招きます。
</details>

**検証**:
- 使用するすべてのSanitize Traitsが`use`文で宣言されているか確認
- `sanitize()`メソッドで実際に使用されているか確認
- `references/sanitize-traits-reference.md`で各Traitの使用方法を確認

**エラー時**:
- **Traitが見つからない**: `app/Mail/Traits/`ディレクトリにTraitファイルが存在するか確認
- **メソッドが見つからない**: Trait内で定義されているメソッド名を確認
- **型エラー**: Sanitizeメソッドに渡す引数の型が正しいか確認

### Step 3: Implement the sanitize() Method

The `sanitize()` method transforms raw Eloquent models into safe, template-ready arrays using Sanitize Traits.

**検証**:
- `sanitize()`メソッドが`protected`修飾子で宣言されているか確認
- すべての返り値キーがテンプレートで使用する変数名と一致しているか確認
- `php artisan tinker`で`(new YourMailableName(...))->build()`を実行し、エラーがないか確認

**エラー時**:
- **未定義メソッドエラー**: Sanitize Traitが正しくインポートされているか確認
- **型エラー**: 渡しているモデルの型がSanitizeメソッドの期待する型と一致しているか確認
- **変数未定義エラー**: テンプレートで使用している変数名が`sanitize()`の返り値キーと一致しているか確認

---

## Creating a New Notification

### Step 1: Define the Notification Class

Create a new Notification class in `server/app/Notifications/`:

**Basic Structure:**
```php
<?php

namespace App\Notifications;

use App\Mail\YourMailableName;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class YourNotificationName extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Application $application
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): YourMailableName
    {
        return new YourMailableName($this->application);
    }
}
```

**検証**:
- クラスファイルが`server/app/Notifications/YourNotificationName.php`に作成されているか確認
- `via()`メソッドが適切な通知チャネル(通常は`['mail']`)を返すか確認
- `toMail()`メソッドがMailableインスタンスを返すか確認

**エラー時**:
- **名前空間エラー**: `composer dump-autoload`を実行
- **型エラー**: `toMail()`の返り値の型がMailableクラスであることを確認
- **チャネルエラー**: `via()`メソッドで返している配列の値が有効な通知チャネルであるか確認

### Step 2: Add Condition Checking (if needed)

If the notification should only be sent under certain conditions, add a `shouldSend()` method:

**良い例と悪い例**:

<details>
<summary>✅ 良い例: shouldSend()で明示的に条件をチェック</summary>

```php
class ApplicationSubmittedNotification extends Notification
{
    public function shouldSend($notifiable, string $channel): bool
    {
        // Send only if user has email notifications enabled
        return $notifiable->email_notifications_enabled;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }
}
```

**理由**: `shouldSend()`メソッドで送信条件を明示的にチェックし、条件を満たさない場合は通知を送信しません。
</details>

<details>
<summary>❌ 悪い例: via()で条件をチェック</summary>

```php
class ApplicationSubmittedNotification extends Notification
{
    public function via($notifiable): array
    {
        // Don't do this - use shouldSend() instead
        if (!$notifiable->email_notifications_enabled) {
            return [];
        }
        return ['mail'];
    }
}
```

**問題**: `via()`メソッドで条件チェックを行うと、通知システムのログやキューに空の配列が記録され、意図が不明確になります。`shouldSend()`を使用すべきです。
</details>

**検証**:
- `shouldSend()`メソッドが条件に応じて`true`または`false`を返すか確認
- テストで両方のケース(送信する/しない)が検証されているか確認

**エラー時**:
- **ロジックエラー**: `shouldSend()`の条件式が期待通りに動作するか、`php artisan tinker`でテスト
- **型エラー**: `$channel`パラメータが文字列型であることを確認

---

## Writing Mail Templates

### Step 1: Create HTML Template

Create a Twig template in `server/resources/views/virtual_resources/emails/`:

**Directory Structure:**
- `to_consumer/` - Emails to job seekers
- `to_corporation/` - Emails to companies

**Basic HTML Template:**
```twig
{% extends 'virtual_resources::emails.layouts.basic' %}

{% block title %}Your Email Title{% endblock %}

{% block content %}
<p>{{ user.last_name }} {{ user.first_name }} 様</p>

<p>
    以下の求人に応募が完了しました。
</p>

<table class="data-table">
    <tr>
        <th>企業名</th>
        <td>{{ company.name }}</td>
    </tr>
    <tr>
        <th>職種</th>
        <td>{{ application.joboffer_title }}</td>
    </tr>
</table>

<p>
    引き続きよろしくお願いいたします。
</p>
{% endblock %}
```

**検証**:
- テンプレートファイルが正しいディレクトリに配置されているか確認
- `{% extends %}`でレイアウトを継承しているか確認
- すべての変数が`sanitize()`メソッドで定義されているか確認
- Twigの構文エラーがないか確認: メールを送信してエラーが出ないか確認

**エラー時**:
- **テンプレート読み込みエラー**: ファイルパスと`view()`メソッドの引数が一致しているか確認
- **変数未定義エラー**: Mailable の`sanitize()`メソッドで該当する変数を定義しているか確認
- **レイアウトエラー**: `{% extends %}`で指定しているレイアウトファイルが存在するか確認

### Step 2: Create Plain Text Template

Create a corresponding plain text template with `_plain` suffix:

**Basic Plain Text Template:**
```twig
{{ user.last_name }} {{ user.first_name }} 様

以下の求人に応募が完了しました。

企業名: {{ company.name }}
職種: {{ application.joboffer_title }}

引き続きよろしくお願いいたします。

---
{{ config('siteNames.users') }}
{{ config('app.url') }}
```

**検証**:
- プレーンテキストテンプレートがHTMLテンプレートと同じディレクトリに`_plain`サフィックス付きで配置されているか確認
- HTMLタグを含まないプレーンテキストのみで記述されているか確認
- HTML版と同じ情報が含まれているか確認

**エラー時**:
- **テンプレート読み込みエラー**: Mailableクラスの`text()`メソッドで正しいパスを指定しているか確認
- **変数未定義エラー**: HTML版と同じ変数を使用しているか確認

### Step 3: Test Email Rendering

Use Laravel's mail preview feature to test email rendering:

**Preview Command:**
```bash
php artisan tinker
> Mail::to('test@example.com')->send(new App\Mail\YourMailableName($application));
```

**検証**:
- メールが正常に送信されるか確認
- HTML版とプレーンテキスト版の両方が正しくレンダリングされるか確認
- すべての変数が期待通りに表示されるか確認
- サブジェクトが正しく設定されているか確認

**エラー時**:
- **送信エラー**: `.env`ファイルのメール設定が正しいか確認(`MAIL_MAILER`, `MAIL_HOST`など)
- **レンダリングエラー**: テンプレート内の変数がすべて定義されているか確認
- **キューエラー**: `ShouldQueue`を実装している場合、`queue:work`が起動しているか確認

---

## Writing Tests for Mails

### Step 1: Create Mailable Test File

Create a test file in `server/tests/Unit/Mail/`:

**Basic Test Structure:**
```php
<?php

namespace Tests\Unit\Mail;

use App\Mail\YourMailableName;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourMailableNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_builds_successfully(): void
    {
        // Arrange
        $application = Application::factory()->create();

        // Act
        $mailable = new YourMailableName($application);

        // Assert
        $mailable->assertSeeInHtml($application->user->last_name);
        $mailable->assertSeeInHtml($application->joboffer->company->name);
        $mailable->assertSeeInText($application->joboffer_title);
    }

    public function test_mailable_has_correct_subject(): void
    {
        // Arrange
        $application = Application::factory()->create();

        // Act
        $mailable = new YourMailableName($application);

        // Assert
        $this->assertStringContainsString(
            '応募完了のお知らせ',
            $mailable->build()->subject
        );
    }
}
```

**検証**:
- テストファイルが`server/tests/Unit/Mail/`に作成されているか確認
- `php artisan test --filter=YourMailableNameTest`でテストが実行されるか確認
- すべてのテストがパスするか確認

**エラー時**:
- **ファクトリーエラー**: 必要なモデルのファクトリーが定義されているか確認
- **アサーションエラー**: 期待する文字列がテンプレート内に実際に存在するか確認
- **データベースエラー**: `RefreshDatabase` trait を使用しているか確認

### Step 2: Create Notification Test File

Create a test file in `server/tests/Unit/Notifications/`:

**Basic Test Structure:**
```php
<?php

namespace Tests\Unit\Notifications;

use App\Mail\YourMailableName;
use App\Models\Application;
use App\Models\User;
use App\Notifications\YourNotificationName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourNotificationNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_mail_channel(): void
    {
        // Arrange
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $notification = new YourNotificationName($application);

        // Act
        $channels = $notification->via($user);

        // Assert
        $this->assertContains('mail', $channels);
    }

    public function test_notification_returns_correct_mailable(): void
    {
        // Arrange
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $notification = new YourNotificationName($application);

        // Act
        $mailable = $notification->toMail($user);

        // Assert
        $this->assertInstanceOf(YourMailableName::class, $mailable);
    }

    public function test_notification_respects_should_send_condition(): void
    {
        // Arrange
        $user = User::factory()->create(['email_notifications_enabled' => false]);
        $application = Application::factory()->create();
        $notification = new YourNotificationName($application);

        // Act
        $shouldSend = $notification->shouldSend($user, 'mail');

        // Assert
        $this->assertFalse($shouldSend);
    }
}
```

**検証**:
- テストファイルが`server/tests/Unit/Notifications/`に作成されているか確認
- すべてのテストケース(チャネル、Mailable、送信条件)がカバーされているか確認
- `php artisan test --filter=YourNotificationNameTest`でテストが実行されるか確認

**エラー時**:
- **型エラー**: `toMail()`が正しいMailableインスタンスを返しているか確認
- **条件エラー**: `shouldSend()`の条件ロジックが正しいか確認
- **ファクトリーエラー**: 必要なモデルのファクトリーが定義されているか確認

### Step 3: Integration Test (Optional)

For comprehensive testing, create an integration test in `server/tests/Feature/Mail/`:

**Integration Test Example:**
```php
<?php

namespace Tests\Feature\Mail;

use App\Models\Application;
use App\Models\User;
use App\Notifications\YourNotificationName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class YourNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_notification_when_application_submitted(): void
    {
        // Arrange
        Notification::fake();
        $user = User::factory()->create(['email_notifications_enabled' => true]);
        $application = Application::factory()->create(['user_id' => $user->id]);

        // Act
        $user->notify(new YourNotificationName($application));

        // Assert
        Notification::assertSentTo(
            $user,
            YourNotificationName::class,
            function ($notification, $channels) use ($application) {
                return $notification->application->id === $application->id
                    && in_array('mail', $channels);
            }
        );
    }
}
```

**検証**:
- 統合テストが実際の通知送信フローをシミュレートしているか確認
- `Notification::fake()`を使用して実際のメール送信を防いでいるか確認
- テストがパスするか確認

**エラー時**:
- **アサーションエラー**: 通知が実際に送信されているか、条件が正しいか確認
- **データ整合性エラー**: ファクトリーで作成したデータが期待通りの関連を持っているか確認

---

このクイックスタートガイドに従うことで、JobAntenna プロジェクトのメール機能を効率的に実装できます。詳細なリファレンスや追加情報については、メインの SKILL.md ファイルおよび references ディレクトリを参照してください。
