# デフォルト設定

このスキルで作成されるメール機能は、以下のデフォルト設定に従います。

## 目次

1. [キューイング動作](#キューイング動作)
2. [メール送信元アドレス](#メール送信元アドレス)
3. [テンプレートパス規約](#テンプレートパス規約)
4. [レイアウトテンプレート](#レイアウトテンプレート)
5. [サブジェクトの命名規則](#サブジェクトの命名規則)
6. [テストデータの配置](#テストデータの配置)
7. [Sanitize Traits のインポート](#sanitize-traits-のインポート)

---

## キューイング動作

**デフォルト**: すべてのメール送信はキューで非同期実行されます。

- Mailable クラスは `ShouldQueue` インターフェースを実装
- Notification クラスも `ShouldQueue` インターフェースを実装
- `Queueable` と `SerializesModels` トレイトを使用

**変更方法**: 同期送信が必要な場合は `ShouldQueue` インターフェースを削除してください。

**実装例:**

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class YourMailableName extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    // ...
}
```

---

## メール送信元アドレス

**デフォルト**: `config/mail.php` で設定されたグローバル送信元を使用

```php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    'name' => env('MAIL_FROM_NAME', 'Example'),
],
```

**変更方法**: 特定の Mailable で異なる送信元を使用する場合は `build()` メソッドで `->from()` を追加してください。

**実装例:**

```php
public function build(): static
{
    return $this->view('virtual_resources::emails.to_consumer.your_template')
        ->from('noreply@jobantenna.com', 'JobAntenna Support')
        ->subject('Your Email Subject');
}
```

---

## テンプレートパス規約

**デフォルト**: `resources/views/emails/to_{recipient}/` ディレクトリ構造

- `to_consumer/` - 求職者向けメール
- `to_partner/` - 企業向けメール
- `to_administrator/` - 管理者向けメール

各ディレクトリ内に `{template_name}.twig` (HTML) と `{template_name}_plain.twig` (テキスト) を配置します。

**ディレクトリ構造例:**

```
resources/views/emails/
├── to_consumer/
│   ├── application_submitted.twig
│   ├── application_submitted_plain.twig
│   ├── password_reset.twig
│   └── password_reset_plain.twig
├── to_partner/
│   ├── application_received.twig
│   └── application_received_plain.twig
└── to_administrator/
    ├── daily_report.twig
    └── daily_report_plain.twig
```

**命名規則:**
- テンプレート名はスネークケース (`application_submitted`)
- HTML 版: `{template_name}.twig`
- テキスト版: `{template_name}_plain.twig`

---

## レイアウトテンプレート

**デフォルト**: `virtual_resources` パッケージ内のレイアウトを継承

**利用可能なレイアウト:**

- `virtual_resources::emails.layouts.consumer_layout` - 求職者向け HTML
- `virtual_resources::emails.layouts.consumer_layout_plain` - 求職者向けテキスト
- `virtual_resources::emails.layouts.partner_layout` - 企業向け HTML
- `virtual_resources::emails.layouts.partner_layout_plain` - 企業向けテキスト
- `virtual_resources::emails.layouts.administrator_layout` - 管理者向け HTML
- `virtual_resources::emails.layouts.administrator_layout_plain` - 管理者向けテキスト

**使用例:**

```twig
{# HTML テンプレート #}
{% extends 'virtual_resources::emails.layouts.consumer_layout' %}

{% block title %}応募完了のお知らせ{% endblock %}

{% block content %}
<p>{{ user.last_name }} {{ user.first_name }} 様</p>

<p>以下の求人に応募が完了しました。</p>
{% endblock %}
```

```twig
{# プレーンテキストテンプレート #}
{% extends 'virtual_resources::emails.layouts.consumer_layout_plain' %}

{% block content %}
{{ user.last_name }} {{ user.first_name }} 様

以下の求人に応募が完了しました。
{% endblock %}
```

---

## サブジェクトの命名規則

**デフォルト**: サイト名を末尾に追加

```php
->subject('メールタイトル【' . config('siteNames.users') . '】')
```

**実装例:**

```php
public function build(): static
{
    $siteName = config('siteNames.users');

    return $this->view('virtual_resources::emails.to_consumer.application_submitted')
        ->subject('応募完了のお知らせ【' . $siteName . '】');
}
```

**サブジェクトのパターン:**

| メール種類 | サブジェクト例 |
|----------|-------------|
| 応募完了 | `応募完了のお知らせ【JobAntenna】` |
| パスワードリセット | `パスワード再設定のご案内【JobAntenna】` |
| メッセージ受信 | `新着メッセージのお知らせ【JobAntenna】` |

**注意事項:**
- サイト名は必ず `config('siteNames.users')` から取得する
- サブジェクトは簡潔で分かりやすい日本語を使用する
- 半角【】でサイト名を囲む

---

## テストデータの配置

**デフォルト**: `tests/data/email_template_test/to_{recipient}/`

期待値ファイルは以下の命名規則に従います:
- `{template_name}.html` - HTML 版の期待値
- `{template_name}_plain.txt` - テキスト版の期待値

**ディレクトリ構造例:**

```
tests/data/email_template_test/
├── to_consumer/
│   ├── application_submitted.html
│   ├── application_submitted_plain.txt
│   ├── password_reset.html
│   └── password_reset_plain.txt
├── to_partner/
│   ├── application_received.html
│   └── application_received_plain.txt
└── to_administrator/
    ├── daily_report.html
    └── daily_report_plain.txt
```

**期待値ファイルの作成方法:**

1. メールを実際に送信する
2. 受信したメールの HTML ソースをコピーして `.html` ファイルに保存
3. プレーンテキスト版も同様に `.txt` ファイルに保存
4. 相対日付や動的データを `{{ ... }}` プレースホルダーに置換

**期待値ファイル例:**

```html
<!-- tests/data/email_template_test/to_consumer/application_submitted.html -->
<!DOCTYPE html>
<html>
<head>
    <title>応募完了のお知らせ</title>
</head>
<body>
    <p>山田 太郎 様</p>
    <p>以下の求人に応募が完了しました。</p>
    <table>
        <tr>
            <th>企業名</th>
            <td>株式会社サンプル</td>
        </tr>
        <tr>
            <th>応募日</th>
            <td>{{ today }}</td>
        </tr>
    </table>
</body>
</html>
```

---

## Sanitize Traits のインポート

**デフォルト**: 必要な Sanitize Traits のみをインポート

一般的な組み合わせ:
- **応募関連**: `SanitizeApplication`, `SanitizeUser`, `SanitizeCompany`, `SanitizeJoboffer`
- **メッセージ関連**: `SanitizeUser`, `SanitizeMessageRoom`
- **ユーザー登録関連**: `SanitizeUser`

**実装例:**

```php
<?php

namespace App\Mail;

use App\Mail\Traits\SanitizeApplication;
use App\Mail\Traits\SanitizeUser;
use App\Mail\Traits\SanitizeCompany;
use App\Mail\Traits\SanitizeJoboffer;

class ApplicationSubmittedMail extends Mailable
{
    use SanitizeApplication, SanitizeUser, SanitizeCompany, SanitizeJoboffer;

    public function __construct(
        private Application $application
    ) {
        parent::__construct();
    }

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'user'        => $this->sanitizeUserForMe($this->application->user),
            'company'     => $this->sanitizeCompany($this->application->joboffer->company),
            'joboffer'    => $this->sanitizeJoboffer($this->application->joboffer),
        ];
    }
}
```

**利用可能な Sanitize Traits:**

| Trait | 主なメソッド | 用途 |
|-------|------------|-----|
| `SanitizeApplication` | `sanitizeApplication()` | 応募データの変換 |
| `SanitizeUser` | `sanitizeUser()`, `sanitizeUserForMe()` | ユーザープロフィールの変換 |
| `SanitizeCompany` | `sanitizeCompany()` | 企業情報の変換 |
| `SanitizeJoboffer` | `sanitizeJoboffer()` | 求人情報の変換 |
| `SanitizeMessageRoom` | `sanitizeMessageRoom()` | メッセージルームの変換 |
| `SanitizeTempApplication` | `sanitizeTempApplication()` | 一時応募データの変換 |
| `SanitizeAddress` | `sanitizeAddress()` | 住所情報の変換 |
| `SanitizeTerm` | `sanitizeSalaryType()` 等 | 用語定義の変換 |

詳細な使用方法は `references/sanitize-traits-reference.md` を参照してください。

**選択のガイドライン:**

1. テンプレートで使用する変数に対応する Trait を選択
2. 未使用の Trait はインポートしない
3. `sanitize()` メソッドで実際に使用することを確認
4. メソッド名の命名規則に従う (`sanitize{ModelName}`)

---

## その他の設定

### UTM パラメータ

メール内のリンクには自動的に UTM パラメータが付与されます:

```php
$url = route('application.show', $application->id) . '?' . http_build_query([
    'utm_source' => 'email',
    'utm_medium' => 'notification',
    'utm_campaign' => 'application_submitted',
]);
```

### タイムゾーン

すべての日時データは Asia/Tokyo タイムゾーンで処理されます。

### 文字エンコーディング

メールは UTF-8 エンコーディングで送信されます。

---

このデフォルト設定に従うことで、JobAntenna プロジェクト全体で一貫性のあるメール実装が可能になります。プロジェクト固有の要件がある場合は、これらの設定を適宜カスタマイズしてください。
