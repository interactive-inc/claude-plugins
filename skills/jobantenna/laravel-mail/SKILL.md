---
name: laravel-mail
description: Create Laravel Mailable classes, Notification classes, Twig email templates, and comprehensive tests following JobAntenna project's established patterns. Use this skill when implementing new email functionality, creating email notifications, or writing tests for email features in the JobAntenna Laravel project. 「Laravel メール作成」「Mailable 実装」「Notification 作成」「メールテンプレート」「メールレビュー」「メール実装チェック」などの依頼時に使用
---

# Laravel Mail Creator

## Table of Contents

1. [Overview](#overview)
2. [When to Use This Skill](#when-to-use-this-skill)
3. [Architecture Overview](#architecture-overview)
4. [Quick Start](#quick-start)
5. [Default Configuration](#default-configuration)
6. [Mail Review](#mail-review)
7. [Resources](#resources)

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
- Reviewing existing mail implementations for best practice compliance
- Getting improvement suggestions with specific code examples

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

### Two-Layer System Benefits

**Why separate Notification and Mailable?**

- **Notification**: Business logic for "when" and "who" receives emails
- **Mailable**: Technical details of "what" content to send

This separation allows:
- Testing Notification conditions independently
- Reusing Mailables across different Notifications
- Clear responsibility boundaries (SRP)

### Sanitize Traits Pattern

Sanitize Traits transform raw Eloquent models into safe, template-ready arrays:

```php
// Instead of passing raw models to templates (risky):
$this->application // Contains internal IDs, timestamps, etc.

// Use Sanitize Traits to create clean data structures:
$this->sanitizeApplication($this->application)
// Returns: ['id' => 123, 'status' => '選考中', 'submitted_at' => '2024年1月15日']
```

**Benefits:**
- **Security**: Prevents accidental exposure of sensitive data
- **Consistency**: Standardized data format across all emails
- **Testability**: Easy to verify template data structure
- **Maintainability**: Centralized transformation logic

Available Sanitize Traits:
- `SanitizeApplication` - Application data
- `SanitizeUser` - User profile data (with `sanitizeUser()` for anonymous and `sanitizeUserForMe()` for authenticated users)
- `SanitizeCompany` - Company information
- `SanitizeJoboffer` - Job offer details
- `SanitizeMessageRoom` - Message room data
- `SanitizeTempApplication` - Temporary application data
- `SanitizeAddress` - Address information
- `SanitizeTerm` - Term definitions (salary types, etc.)

Refer to `references/sanitize-traits-reference.md` for detailed usage of each trait.

### MailFake Testing Infrastructure

JobAntenna uses a custom `MailFake` class that extends Laravel's default MailFake to provide:

- **Expected Output Comparison**: Compare rendered emails against golden files
- **Relative Date Handling**: Support for `{{ today }}`, `{{ tomorrow }}`, etc. in expected outputs
- **Automatic Plain Text Verification**: Ensures both HTML and text versions are tested

For detailed testing patterns, see `references/mail-test-patterns.md`.

## Quick Start

For step-by-step implementation guides, see **[QUICK_START.md](./QUICK_START.md)** which covers:

1. **Creating a New Mailable**
   - Step 1: Define the Mailable Class
   - Step 2: Choose Appropriate Sanitize Traits
   - Step 3: Implement the sanitize() Method

2. **Creating a New Notification**
   - Step 1: Define the Notification Class
   - Step 2: Add Condition Checking (if needed)

3. **Writing Mail Templates**
   - Step 1: Create HTML Template
   - Step 2: Create Plain Text Template
   - Step 3: Test Email Rendering

4. **Writing Tests for Mails**
   - Step 1: Create Mailable Test File
   - Step 2: Create Notification Test File
   - Step 3: Integration Test (Optional)

Each section includes:
- ✅ Good examples and ❌ Bad examples with explanations
- Verification steps to confirm correct implementation
- Error handling instructions for common issues

## Default Configuration

For detailed default settings and conventions, see **[DEFAULT_CONFIGURATION.md](./DEFAULT_CONFIGURATION.md)** which covers:

1. **キューイング動作** - すべてのメール送信はデフォルトでキューで非同期実行
2. **メール送信元アドレス** - `config/mail.php` で設定されたグローバル送信元を使用
3. **テンプレートパス規約** - `resources/views/emails/to_{recipient}/` ディレクトリ構造
4. **レイアウトテンプレート** - `virtual_resources` パッケージ内のレイアウトを継承
5. **サブジェクトの命名規則** - サイト名を末尾に追加 `【{サイト名}】`
6. **テストデータの配置** - `tests/data/email_template_test/to_{recipient}/`
7. **Sanitize Traits のインポート** - 必要な Traits のみをインポート

これらのデフォルト設定に従うことで、JobAntenna プロジェクト全体で一貫性のあるメール実装が可能になります。

## Mail Review

このスキルには、Laravel メール実装を実証済みパターンとベストプラクティスに基づいて評価する専門レビューエージェントが含まれています。

### レビュー機能を使用するタイミング

レビューエージェントは以下の場合に使用します:
- 新しいメール機能を実装した後、ベストプラクティスに従っているか確認したい
- 既存のメール実装をリファクタリングして保守性を向上させたい
- 本番デプロイ前に潜在的な問題を特定したい
- チームメンバーのオンボーディング時にメールの品質を標準化したい
- コード例付きの具体的な改善提案が必要

### レビュー対象の10の評価観点

レビューエージェントは以下の**10の主要な観点**に基づいてメール実装を評価します:

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

エージェントは以下を含む包括的なレビューレポートを提供します:

- **全体的な品質評価** - 優秀 / 良好 / 要改善 / 不適切
- **評価サマリーテーブル** - 10の観点それぞれの評価（✅ 優 / ⚠️ 要改善 / ❌ 不適切）
- **優先度付き改善提案** - 高 / 中 / 低の優先度で以下を提示:
  - 現在の実装コード
  - 特定された問題点
  - 推奨される実装コード
  - 期待される効果
- **次のアクション** - メール実装を改善するための明確なステップ

### レビューのリクエスト方法

メールのレビューを依頼する際は、ファイルパスと必要に応じて特定の懸念事項を指定します:

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

エージェントはファイルを読み込み、10の観点に基づいて評価し、コード例付きの詳細な改善提案を提供します。

レビューロジックとパターンの詳細は `agents/laravel-mail-reviewer.md` を参照してください。

## Resources

### Quick Start Guide

- **[QUICK_START.md](./QUICK_START.md)** - ステップバイステップ実装ガイド
  - Creating a New Mailable（3ステップ）
  - Creating a New Notification（2ステップ）
  - Writing Mail Templates（3ステップ）
  - Writing Tests for Mails（3ステップ）
  - 良い例・悪い例の比較
  - 検証ステップとエラーハンドリング

### Default Configuration

- **[DEFAULT_CONFIGURATION.md](./DEFAULT_CONFIGURATION.md)** - デフォルト設定とプロジェクト規約
  - キューイング動作
  - メール送信元アドレス
  - テンプレートパス規約
  - レイアウトテンプレート
  - サブジェクトの命名規則
  - テストデータの配置
  - Sanitize Traits のインポート

### Detailed References

詳細な実装とテストガイド（`references/` ディレクトリ）:

- `mail-implementation-patterns.md` - 包括的な Mailable と Notification パターン
- `mail-test-patterns.md` - テスト戦略、MailFake 使用法、アサーション
- `sanitize-traits-reference.md` - すべての利用可能な Sanitize Traits とその使用法

必要に応じて特定のパターンに関する詳細情報をこれらのリファレンスから読み込んでください。

### Ready-to-Use Templates

すぐに使えるテンプレート（`assets/` ディレクトリ）:

- `templates/mailable-template.php` - Mailable クラスのボイラープレート
- `templates/notification-template.php` - Notification クラスのボイラープレート
- `templates/mail-test-template.php` - テストクラスのボイラープレート
- `templates/twig/` - メールテンプレート例（HTML とテキスト）

新しいメール実装のためにこれらのテンプレートをコピーしてカスタマイズしてください。

### Automation Utilities

開発ワークフローを加速するユーティリティ（`scripts/` ディレクトリ）:

- `generate_mail_scaffold.py` - 完全なメール実装を生成（Mailable + Notification + Templates + Tests）

スクリプトを実行して開発ワークフローを加速してください。

### Review Agent

- `agents/laravel-mail-reviewer.md` - Laravel メール実装をレビューする専門エージェント

このエージェントは、Mailable クラス、Notification クラス、Twig テンプレート、テストの実装を 10 の主要な観点に基づいて評価します。

---

このスキルを使用することで、JobAntenna プロジェクトで一貫性があり、テストされ、保守可能なメール機能を効率的に実装できます。
