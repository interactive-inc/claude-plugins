# Hooks Review

Claude Code フックの設定をレビュー・構成し、ワークフロー自動化を支援するスキルです。

## 概要

このスキルは、Claude Code のフック（特定のイベントに応答して実行されるシェルコマンド）の包括的なレビューと設定を可能にします。バリデーション、フォーマット、セキュリティチェック、カスタム統合などのワークフロー自動化機能を提供します。

## インストール

### 前提条件

Claude Code CLI がインストールされている必要があります。

### マーケットプレースの追加

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

```bash
/plugin install claude@interactive-claude-plugins
```

## 主な機能

- **包括的な設定レビュー**: セキュリティ、パフォーマンス、正確性の観点から既存のフック設定を分析
- **セキュリティ脆弱性検出**: コマンドインジェクション、クォートされていない変数、秘密情報の露出を特定
- **パフォーマンス最適化**: タイムアウト不足、非効率なパターンを発見
- **ベストプラクティス検証**: 正しい終了コード、適切な JSON フォーマットを確認
- **フック作成支援**: 一般的なシナリオ向けの新規フック設定をガイド
- **優先度付き推奨事項**: Critical / High / Medium / Low の 4 段階で問題を分類

## 使用タイミング

このスキルは以下のような場合に使用します：

- フック設定を新規作成した後の品質チェック
- 既存フックのセキュリティレビュー
- フック設定のトラブルシューティング
- ベストプラクティスに従っているか確認したい
- ワークフロー自動化のためのフック設定を作成したい
- フックのパフォーマンスを改善したい

## サポートされるフックイベント

- **PreToolUse**: ツール実行前（Write/Edit/Bash など）
- **PostToolUse**: ツール実行後
- **UserPromptSubmit**: ユーザープロンプト送信時
- **Stop**: セッション停止時
- **SubagentStop**: サブエージェント停止時
- **SessionStart**: セッション開始時
- **SessionEnd**: セッション終了時
- **Notification**: 通知発生時
- **PreCompact**: コンテキスト圧縮前

## ファイル構成

```
skills/claude/hooks-review/
├── README.md                          # このファイル
├── SKILL.md                          # メインスキル定義
├── assets/
│   └── example-hooks.json            # フック設定の例
└── references/
    ├── review-checklist.md           # レビューチェックリスト
    ├── review-report-template.md     # レビューレポートテンプレート
    ├── security-guide.md             # セキュリティガイド
    └── common-patterns.md            # 一般的なフックパターン
```

## 使い方

### 基本的な使用方法

1. **既存フック設定のレビュー**
   ```
   「hooks-review スキルを使ってフック設定をレビューしてください」
   ```

2. **特定のフック設定ファイルをレビュー**
   ```
   「.claude/settings.json のフック設定をレビューしてください」
   ```

3. **新規フック作成のガイド**
   ```
   「コミット前のセキュリティスキャン用のフックを作成したい」
   ```

### レビュープロセス

詳細なレビュープロセスは [SKILL.md](./SKILL.md) を参照してください。

1. フック設定ファイルの読み込みと検証
2. セキュリティ、パフォーマンス、正確性の基準に対する分析
3. 問題の特定と重要度による分類
4. 具体的な改善提案の作成
5. Before/After 形式での修正例の提示

## レビュー観点

### セキュリティ

- コマンドインジェクションの脆弱性
- クォートされていない変数の使用
- ハードコードされた秘密情報
- 適切な環境変数の使用
- ユーザー入力のサニタイゼーション

### パフォーマンス

- タイムアウト設定の有無
- 効率的なコマンドパターン
- 不必要な処理の削減
- リソース使用の最適化

### 正確性

- 適切な終了コードの使用
- 正しい JSON 構造
- エラーハンドリングの実装
- ログメッセージの適切性

### ベストプラクティス

- 適切なフックイベントの選択
- ツールフィルタの使用
- プロジェクト固有 vs グローバル設定
- ドキュメント化

## レビューレポート

レビュー結果は以下の形式で出力されます：

1. **サマリー**: 総合評価と主要な発見事項
2. **重要度別の問題**: Critical / High / Medium / Low で分類
3. **具体的な推奨事項**: 各問題の詳細と修正方法
4. **修正例**: Before/After 形式のコード例
5. **次のアクション**: 優先順位付きのアクションアイテム

詳細なレポート形式は [references/review-report-template.md](./references/review-report-template.md) を参照してください。

## 一般的なフックパターン

### コード整形（PreToolUse）

ファイル書き込み前に自動でコードをフォーマット：

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "event": "PreToolUse",
        "tool": "Write",
        "command": "prettier --write {{file_path}}",
        "timeout": 5000
      }
    ]
  }
}
```

### セキュリティスキャン（PreToolUse）

Git コミット前にセキュリティチェック：

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "event": "PreToolUse",
        "tool": "Bash",
        "filter": "git commit",
        "command": "trufflehog git file://. --fail",
        "timeout": 30000
      }
    ]
  }
}
```

### テスト実行検証（PostToolUse）

コード変更後にテストを実行：

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "event": "PostToolUse",
        "tool": "Edit",
        "command": "npm test",
        "timeout": 60000
      }
    ]
  }
}
```

その他の一般的なパターンは [references/common-patterns.md](./references/common-patterns.md) を参照してください。

## セキュリティガイド

フック設定のセキュリティベストプラクティスは [references/security-guide.md](./references/security-guide.md) を参照してください：

- 変数のクォーティング
- 環境変数の安全な使用
- コマンドインジェクションの防止
- 秘密情報の管理
- タイムアウトの設定

## 注意事項

1. **設定ファイルの場所**: レビュー対象のフック設定ファイルのパスを明確に指定してください
2. **JSON 形式**: 設定ファイルは有効な JSON 形式である必要があります
3. **セキュリティ優先**: セキュリティに関する問題は最優先で対処します
4. **実行可能性**: すべての推奨事項は具体的で実行可能です
5. **テスト推奨**: フック設定変更後は必ずテストしてください

## 関連リンク

- [SKILL.md](./SKILL.md) - 詳細な実行フローとレビュー基準
- [references/review-checklist.md](./references/review-checklist.md) - 詳細なレビューチェックリスト
- [references/review-report-template.md](./references/review-report-template.md) - レビューレポートテンプレート
- [references/security-guide.md](./references/security-guide.md) - セキュリティガイド
- [references/common-patterns.md](./references/common-patterns.md) - 一般的なフックパターン
- [assets/example-hooks.json](./assets/example-hooks.json) - フック設定の例

---

このスキルにより、Claude Code フックの品質とセキュリティを客観的に評価し、効果的なワークフロー自動化を支援します。
