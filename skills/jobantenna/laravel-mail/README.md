# Laravel Mail Creator

JobAntenna Laravel プロジェクトにおけるメール機能（Mailable、Notification、Twig テンプレート、テスト）の実装を確立されたパターンに従って支援する包括的なスキルです。

## 概要

このスキルは、JobAntenna プロジェクトで実証済みのメールアーキテクチャパターンに基づき、Mailable クラス、Notification クラス、Twig テンプレート、包括的なテストの作成をサポートします。Sanitize Traits によるデータ変換、MailFake によるテストインフラ、二層アーキテクチャ（Notification + Mailable）を活用した堅牢なメール実装を実現します。

## インストール

### 前提条件

Claude Code CLI がインストールされている必要があります。

### マーケットプレースの追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

```bash
/plugin install jobantenna@interactive-claude-plugins
```

## 主な機能

- **確立されたアーキテクチャパターン**: 二層システム（Notification + Mailable）による明確な責任分離
- **Sanitize Traits**: 8つの再利用可能なデータ変換 Trait による安全なテンプレートデータ
- **カスタム MailFake**: 期待値ファイル比較、相対日付サポート、自動プレーンテキスト検証
- **即利用可能なテンプレート**: Mailable、Notification、Twig テンプレート、テストのボイラープレート
- **包括的なガイド**: クイックスタート、デフォルト設定、詳細リファレンス
- **自動化ツール**: メール実装の完全なスキャフォールドを生成するスクリプト
- **専門レビューエージェント**: 10の観点からメール実装の品質評価

## このスキルを使用するタイミング

以下のような場合に使用してください：

### メール実装
- 新しいメール通知の実装（ユーザー登録、応募完了、パスワードリセットなど）
- Mailable クラスの作成
- Notification クラスによるマルチチャネル配信の実装
- HTML とプレーンテキスト版のメールテンプレート作成
- メール機能の包括的なテスト作成
- プロジェクトのメールアーキテクチャパターンの理解

### メールレビュー
- 既存のメール実装がベストプラクティスに準拠しているかチェック
- コード例付きの具体的な改善提案を受ける
- メール実装の品質と保守性を評価

## ディレクトリ構造

```
skills/jobantenna/laravel-mail/
├── README.md                           # このファイル
├── SKILL.md                            # スキル定義とアーキテクチャ概要
├── QUICK_START.md                      # ステップバイステップ実装ガイド
├── DEFAULT_CONFIGURATION.md            # デフォルト設定とプロジェクト規約
├── assets/
│   └── templates/                      # 即利用可能なテンプレート
│       ├── mailable-template.php       # Mailable クラスボイラープレート
│       ├── notification-template.php   # Notification クラスボイラープレート
│       ├── mail-test-template.php      # テストクラスボイラープレート
│       └── twig/                       # Twig テンプレート例
├── references/                         # 詳細リファレンス
│   ├── mail-implementation-patterns.md # Mailable/Notification パターン
│   ├── mail-test-patterns.md          # テスト戦略とアサーション
│   └── sanitize-traits-reference.md   # Sanitize Traits 完全リファレンス
├── agents/
│   └── laravel-mail-reviewer.md       # メールレビュー専門エージェント
└── scripts/
    └── generate_mail_scaffold.py      # メール実装生成スクリプト
```

## アーキテクチャ概要

JobAntenna のメールシステムは二層アーキテクチャを採用しています：

```
Event → Listener → Notification → Mailable → Template
```

### 二層システムの利点

**Notification Layer**: 送信条件とチャネル選択（メールを送信すべきか？）
**Mailable Layer**: データ準備とテンプレート構築（何を送信するか？）

この分離により：
- Notification の条件を独立してテスト可能
- 異なる Notification 間で Mailable を再利用可能
- 明確な責任境界（単一責任の原則）

### 主要コンポーネント

1. **Mailable クラス** (`app/Mail/`) - メール内容とテンプレート定義
2. **Notification クラス** (`app/Notifications/`) - 送信条件とチャネルルーティング
3. **Sanitize Traits** (`app/Mail/Traits/`) - 再利用可能なデータ変換ロジック
4. **テンプレート** (`resources/views/emails/`) - HTML とテキスト版の Twig テンプレート
5. **テスト** (`tests/Feature/Mail/`) - カスタム MailFake による包括的テスト

### Sanitize Traits パターン

Sanitize Traits は生の Eloquent モデルを安全でテンプレート対応の配列に変換します：

```php
// 生のモデルをテンプレートに渡すのではなく（リスク高）:
$this->application

// Sanitize Traits を使用してクリーンなデータ構造を作成:
$this->sanitizeApplication($this->application)
// 返り値: ['id' => 123, 'status' => '選考中', 'submitted_at' => '2024年1月15日']
```

**利点:**
- **セキュリティ**: 機密データの誤った露出を防止
- **一貫性**: すべてのメールで標準化されたデータ形式
- **テスタビリティ**: テンプレートデータ構造の検証が容易
- **保守性**: 中央集約された変換ロジック

**利用可能な Sanitize Traits:**
- `SanitizeApplication` - 応募データ
- `SanitizeUser` - ユーザープロファイルデータ（匿名用 `sanitizeUser()`、認証ユーザー用 `sanitizeUserForMe()`）
- `SanitizeCompany` - 企業情報
- `SanitizeJoboffer` - 求人詳細
- `SanitizeMessageRoom` - メッセージルームデータ
- `SanitizeTempApplication` - 一時応募データ
- `SanitizeAddress` - 住所情報
- `SanitizeTerm` - 用語定義（給与タイプなど）

詳細は [references/sanitize-traits-reference.md](./references/sanitize-traits-reference.md) を参照してください。

## クイックスタート

詳細なステップバイステップガイドは [QUICK_START.md](./QUICK_START.md) を参照してください：

### 1. 新しい Mailable の作成

```bash
# ステップ1: Mailable クラス定義
# ステップ2: 適切な Sanitize Traits を選択
# ステップ3: sanitize() メソッドの実装
```

### 2. 新しい Notification の作成

```bash
# ステップ1: Notification クラス定義
# ステップ2: 条件チェックの追加（必要に応じて）
```

### 3. メールテンプレートの作成

```bash
# ステップ1: HTML テンプレート作成
# ステップ2: プレーンテキストテンプレート作成
# ステップ3: メールレンダリングのテスト
```

### 4. メールのテスト作成

```bash
# ステップ1: Mailable テストファイル作成
# ステップ2: Notification テストファイル作成
# ステップ3: 統合テスト（オプション）
```

各セクションには以下が含まれます：
- ✅ 良い例と ❌ 悪い例の説明付き比較
- 正しい実装を確認する検証ステップ
- 一般的な問題のエラーハンドリング手順

## デフォルト設定

詳細なデフォルト設定と規約は [DEFAULT_CONFIGURATION.md](./DEFAULT_CONFIGURATION.md) を参照してください：

1. **キューイング動作** - すべてのメール送信はデフォルトでキューで非同期実行
2. **メール送信元アドレス** - `config/mail.php` で設定されたグローバル送信元を使用
3. **テンプレートパス規約** - `resources/views/emails/to_{recipient}/` ディレクトリ構造
4. **レイアウトテンプレート** - `virtual_resources` パッケージ内のレイアウトを継承
5. **サブジェクトの命名規則** - サイト名を末尾に追加 `【{サイト名}】`
6. **テストデータの配置** - `tests/data/email_template_test/to_{recipient}/`
7. **Sanitize Traits のインポート** - 必要な Traits のみをインポート

## MailFake テストインフラ

JobAntenna は Laravel のデフォルト MailFake を拡張したカスタム `MailFake` クラスを使用：

- **期待値出力比較**: レンダリングされたメールをゴールデンファイルと比較
- **相対日付処理**: 期待値出力で `{{ today }}`、`{{ tomorrow }}` などをサポート
- **自動プレーンテキスト検証**: HTML とテキスト版の両方が自動的にテスト

詳細なテストパターンは [references/mail-test-patterns.md](./references/mail-test-patterns.md) を参照してください。

## 利用可能なリソース

### テンプレート (assets/)

即利用可能なボイラープレート：
- `templates/mailable-template.php` - Mailable クラス
- `templates/notification-template.php` - Notification クラス
- `templates/mail-test-template.php` - テストクラス
- `templates/twig/` - HTML/テキストテンプレート例

### リファレンス (references/)

詳細な実装ガイド：
- `mail-implementation-patterns.md` - Mailable/Notification の包括的パターン
- `mail-test-patterns.md` - テスト戦略、MailFake 使用法、アサーション
- `sanitize-traits-reference.md` - すべての Sanitize Traits とその使用法

### 自動化スクリプト (scripts/)

開発ワークフローを加速：
- `generate_mail_scaffold.py` - 完全なメール実装を生成（Mailable + Notification + Templates + Tests）

### レビューエージェント (agents/)

- `laravel-mail-reviewer.md` - Laravel メール実装レビュー専門エージェント

このエージェントは Mailable クラス、Notification クラス、Twig テンプレート、テスト実装を 10 の主要な観点に基づいて評価します。

## メールレビュー

このスキルには、Laravel メール実装を実証済みパターンとベストプラクティスに基づいて評価する専門レビューエージェントが含まれています。

### レビュー機能を使用するタイミング

- 新しいメール機能を実装した後、ベストプラクティスに従っているか確認したい
- 既存のメール実装をリファクタリングして保守性を向上させたい
- 本番デプロイ前に潜在的な問題を特定したい
- チームメンバーのオンボーディング時にメールの品質を標準化したい
- コード例付きの具体的な改善提案が必要

### レビュー対象の10の評価観点

1. **Mailable クラスの基本構造** - 命名規則、署名定義、戻り値、親コンストラクタ呼び出し
2. **Sanitize Trait の選択と実装** - 適切な Trait の使用、メソッド呼び出し、データ構造
3. **build() メソッドの実装** - テンプレート指定、サブジェクト、sanitize() 連携
4. **Auto-Detection パターン** - 複数受信者タイプへの対応、duty/guard_name による自動判定
5. **Notification クラスの構造** - via() による送信条件チェック、toMail() の実装
6. **Twig テンプレートの実装** - HTML/テキスト版の対応、レイアウト継承、変数使用
7. **テストの実装** - MailFake の使用、期待値ファイル、相対日付、アサーション
8. **Event リスナー連携** - イベントとリスナーの結合、エラーハンドリング
9. **プロジェクト固有の規約** - VirtualResource、命名規則、UTM パラメータ
10. **テスト容易性と保守性** - seed メソッド分離、テストデータ構造、メソッド分割

### レビュー出力

エージェントは以下を含む包括的なレビューレポートを提供：

- **全体的な品質評価** - 優秀 / 良好 / 要改善 / 不適切
- **評価サマリーテーブル** - 10の観点それぞれの評価（✅ 優 / ⚠️ 要改善 / ❌ 不適切）
- **優先度付き改善提案** - 高 / 中 / 低の優先度で以下を提示：
  - 現在の実装コード
  - 特定された問題点
  - 推奨される実装コード
  - 期待される効果
- **次のアクション** - メール実装を改善するための明確なステップ

### レビューのリクエスト方法

**例 1: フルレビュー**
```
app/Mail/ApplicationSubmitted.php をレビューしてください
```

**例 2: 焦点を絞ったレビュー**
```
app/Mail/PasswordReset.php の Sanitize Trait の使い方と
テンプレート実装をレビューしてください
```

**例 3: 複数ファイル**
```
以下のメール実装をレビューしてください:
- app/Mail/ApplicationSubmitted.php
- app/Notifications/ApplicationSubmittedNotification.php
- resources/views/emails/to_consumer/application_submitted.twig
```

## 使用例

### 例1: 応募完了メールの実装

```php
// app/Mail/ApplicationSubmitted.php
class ApplicationSubmitted extends Mailable
{
    use SanitizeApplication, SanitizeJoboffer, SanitizeUser;

    public function __construct(
        private Application $application
    ) {
        parent::__construct();
    }

    protected function sanitize(): array
    {
        return [
            'application' => $this->sanitizeApplication($this->application),
            'joboffer' => $this->sanitizeJoboffer($this->application->joboffer),
            'user' => $this->sanitizeUserForMe($this->application->user),
        ];
    }

    public function build(): self
    {
        return $this->subject('応募を受け付けました')
            ->view('emails.to_consumer.application_submitted')
            ->text('emails.to_consumer.application_submitted_text')
            ->with($this->sanitize());
    }
}
```

### 例2: Notification での条件チェック

```php
// app/Notifications/ApplicationSubmittedNotification.php
class ApplicationSubmittedNotification extends Notification
{
    public function via($notifiable): array
    {
        // ユーザーがメール通知を許可している場合のみ送信
        if (!$notifiable->email_notification_enabled) {
            return [];
        }

        return ['mail'];
    }

    public function toMail($notifiable): ApplicationSubmitted
    {
        return new ApplicationSubmitted($this->application);
    }
}
```

### 例3: MailFake によるテスト

```php
// tests/Feature/Mail/ApplicationSubmittedTest.php
public function test_application_submitted_mail()
{
    Mail::fake();

    $application = Application::factory()->create();

    Mail::to($application->user)->send(
        new ApplicationSubmitted($application)
    );

    Mail::assertSent(ApplicationSubmitted::class, function ($mail) use ($application) {
        return $mail->hasTo($application->user->email);
    });

    // 期待値ファイルと比較
    $this->assertMailMatchesExpected(
        ApplicationSubmitted::class,
        'application_submitted',
        $application->user
    );
}
```

## トラブルシューティング

### メールが送信されない

```bash
# キュー設定を確認
php artisan queue:work

# ログを確認
tail -f storage/logs/laravel.log
```

### テンプレートが見つからない

```bash
# ビューキャッシュをクリア
php artisan view:clear

# パスが正しいか確認
# resources/views/emails/to_consumer/your_template.twig
```

### Sanitize Traits が動作しない

```php
// Trait がインポートされているか確認
use App\Mail\Traits\SanitizeApplication;

// sanitize() メソッドで使用されているか確認
return [
    'application' => $this->sanitizeApplication($this->application),
];
```

## ベストプラクティス

1. **常に Sanitize Traits を使用** - 生のモデルを直接テンプレートに渡さない
2. **HTML とテキスト版の両方を提供** - アクセシビリティのため
3. **期待値ファイルでテスト** - レンダリング結果の回帰を防ぐ
4. **キューイングを使用** - メール送信がアプリケーションをブロックしないように
5. **Notification で条件チェック** - 不要なメール送信を防ぐ

## 関連リソース

- [Laravel 公式ドキュメント - Mail](https://laravel.com/docs/mail)
- [Laravel 公式ドキュメント - Notifications](https://laravel.com/docs/notifications)
- [Twig Documentation](https://twig.symfony.com/doc/)

---

このスキルを使用することで、JobAntenna プロジェクトで一貫性があり、テストされ、保守可能なメール機能を効率的に実装できます。
