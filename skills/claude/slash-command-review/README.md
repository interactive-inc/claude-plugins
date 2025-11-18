# Slash Command Review

Claude Code のスラッシュコマンド実装をベストプラクティスに照らしてレビューし、A-F 評価と具体的な改善提案を提供するスキルです。

## 概要

このスキルは、Claude Code のスラッシュコマンドファイル（`.claude/commands/*.md`）を 6 つの観点から評価し、強みと弱点を特定して実行可能な改善推奨事項を提供します。メタデータ品質、引数処理、動的機能、セキュリティ、スコープ、スキル境界などを包括的に分析します。

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

- **6つの評価観点**: メタデータ品質、引数処理、動的機能（Bash/ファイル統合）、セキュリティ、スコープ、スキル境界
- **A-F 評価**: 0-100 点のスコアリングと A-F グレーディング
- **優先度付き推奨事項**: Critical / High / Medium / Low の 4 段階で問題を分類
- **具体的な修正例**: Before/After 形式で改善例を提示
- **セキュリティ重視**: コマンドインジェクション、パストラバーサル、権限昇格のリスクを検出
- **ベストプラクティス検証**: frontmatter、引数パターン、ツール制限、モデル選択を評価

## 使用タイミング

このスキルは以下のような場合に使用します：

- スラッシュコマンドを新規作成した後の品質チェック
- 既存コマンドの改善点を特定したい
- ベストプラクティスに従っているか確認したい
- セキュリティ評価が必要
- コマンドの標準準拠を確認したい
- `.claude/commands/` ディレクトリ全体を監査したい

## 評価観点

このスキルは、スラッシュコマンドを 6 つの観点から評価します：

### 1. メタデータ品質

- **description**: 明確で簡潔なコマンドの目的説明
- **argument-hint**: 引数パターンの提示（例: `<file>`, `[options]`）
- **model**: 適切なモデル選択（haiku/sonnet/opus）
- **allowed-tools**: 必要最小限のツール制限
- **disable-model-invocation**: 自動実行防止の適切な使用

### 2. 引数処理

- **位置引数**: `$1`, `$2` などの適切な使用
- **全引数**: `$ARGUMENTS` の活用
- **バリデーション**: 引数の検証と適切なエラーメッセージ
- **デフォルト値**: 引数が未指定の場合の処理
- **ヘルプメッセージ**: 使用方法の明確な説明

### 3. 動的機能（Bash/ファイル統合）

- **Bash コマンド**: `$()` を使った動的なコンテキスト取得
- **ファイル読み込み**: `{{file:path}}` を使ったファイルコンテンツの埋め込み
- **エラーハンドリング**: コマンド失敗時の適切な処理
- **条件分岐**: 状況に応じた動的な振る舞い

### 4. セキュリティ

- **コマンドインジェクション**: 未サニタイズの引数によるリスク
- **パストラバーサル**: `../` などの危険なパス操作
- **権限昇格**: 不必要な高特権ツールの使用
- **秘密情報**: ハードコードされたトークンやパスワード
- **入力検証**: ユーザー入力の適切なバリデーション

### 5. スコープ

- **プロジェクト固有 vs グローバル**: 適切なスコープの選択
- **チーム共有**: `.claude/commands/` による共有
- **個人専用**: `~/.claude/commands/` による個人設定
- **オーバーライド**: プロジェクトによるグローバル設定の上書き

### 6. スキル境界

- **スラッシュコマンド vs スキル**: 適切な実装方法の選択
- **複雑度の評価**: シンプルなコマンドは適切、複雑な処理はスキルへ
- **保守性**: コマンドが複雑すぎないか
- **再利用性**: 複数のプロジェクトで使える設計か

## ファイル構成

```
skills/claude/slash-command-review/
├── README.md              # このファイル
├── SKILL.md              # メインスキル定義
└── references/
    ├── best-practices.md         # スラッシュコマンドのベストプラクティス
    ├── security-guide.md         # セキュリティガイド
    ├── examples.md               # 良い例・悪い例
    └── scoring-rubric.md         # 評価基準とスコアリング
```

## 使い方

### 基本的な使用方法

1. **単一コマンドのレビュー**
   ```
   「slash-command-review スキルを使って /component コマンドをレビューしてください」
   ```

2. **特定のファイルをレビュー**
   ```
   「.claude/commands/component.md を評価してください」
   ```

3. **すべてのコマンドを監査**
   ```
   「.claude/commands/ 内のすべてのスラッシュコマンドをレビューしてください」
   ```

### レビューワークフロー

詳細なワークフローは [SKILL.md](./SKILL.md) を参照してください。

#### ステップ1: 対象コマンドの特定

レビュー対象のスラッシュコマンドファイルを特定：

- ユーザーが特定のファイルを指定
- ユーザーが特定のディレクトリを指定
- ユーザーが「すべてのコマンド」を指定
- ユーザーがコマンド名を指定（例: `/component`）

#### ステップ2: コマンド構造の解析

frontmatter（YAML）とコマンド本体（Markdown）を抽出・分析。

#### ステップ3: 6つの観点で評価

各観点でチェックリストに基づいて評価し、問題を特定。

#### ステップ4: スコアリングとグレーディング

0-100 点のスコアを算出し、A-F グレードを付与。

#### ステップ5: レポート生成

優先度付き推奨事項と具体的な修正例を含むレポートを生成。

## 評価スコア

### スコアリング基準

- **A（優秀）**: 90-100点 - すべてのベストプラクティスに準拠
- **B（良好）**: 70-89点 - ほとんどのベストプラクティスに準拠、軽微な改善点のみ
- **C（要改善）**: 50-69点 - いくつかの重要な問題あり
- **D（多くの問題）**: 30-49点 - 多数のベストプラクティス違反
- **F（不合格）**: 0-29点 - 致命的な問題あり、全面的な再作成を推奨

### 優先度分類

- 🔴 **Critical**: 致命的な問題（セキュリティリスク、コマンドが動作しない）
- 🟠 **High**: 重要な改善（品質や保守性に大きく影響）
- 🟡 **Medium**: 推奨される改善（ベストプラクティスからの逸脱）
- 🟢 **Low**: 最適化（さらなる品質向上）

詳細なスコアリング基準は [references/scoring-rubric.md](./references/scoring-rubric.md) を参照してください。

## ベストプラクティス

詳細なベストプラクティスガイドは [references/best-practices.md](./references/best-practices.md) を参照してください：

### frontmatter の書き方

```yaml
---
description: Create a new React component with TypeScript and tests
argument-hint: <component-name>
model: haiku
allowed-tools: [Write, Read, Glob]
---
```

### 引数処理のパターン

```markdown
# 引数の検証
Check if component name is provided:
- If $1 is empty, show usage: "Usage: /component <component-name>"
- Validate component name format (PascalCase)

# デフォルト値の設定
Set component directory to ${1:-src/components}
```

### 動的機能の活用

```markdown
# Bash コマンドの使用
Current branch: $(git rev-parse --abbrev-ref HEAD)

# ファイルコンテンツの埋め込み
Current package.json:
{{file:.claude/package.json}}
```

### セキュリティのベストプラクティス

```markdown
# 入力検証
Validate that $1 matches pattern: ^[A-Za-z][A-Za-z0-9_]*$
Reject if $1 contains: ../ or absolute paths

# ツール制限
allowed-tools: [Write, Read, Glob]  # Bash は不要なら除外
```

## セキュリティガイド

詳細なセキュリティガイドは [references/security-guide.md](./references/security-guide.md) を参照してください：

### 回避すべきパターン

❌ **コマンドインジェクション**:
```markdown
Run: git commit -m "$1"  # $1 が "; rm -rf /" だったら危険
```

✅ **安全な実装**:
```markdown
Validate $1 contains only alphanumeric and basic punctuation
Or use allowed-tools to restrict to safe tools only
```

❌ **パストラバーサル**:
```markdown
Read file: $1  # $1 が "../../etc/passwd" だったら危険
```

✅ **安全な実装**:
```markdown
Validate $1 is within project directory
Reject if $1 contains "../" or starts with "/"
```

❌ **権限昇格**:
```markdown
allowed-tools: [Bash, Write, Edit, Read]  # Bash は高リスク
```

✅ **安全な実装**:
```markdown
allowed-tools: [Write, Read, Glob]  # 必要最小限のツールのみ
```

## 良い例・悪い例

詳細な例は [references/examples.md](./references/examples.md) を参照してください。

### 良い例: シンプルな React コンポーネント作成コマンド

```markdown
---
description: Create a new React component with TypeScript
argument-hint: <component-name>
model: haiku
allowed-tools: [Write, Read, Glob]
---

Create a new React component named $1.

Validation:
- Check if $1 is provided, if not show usage
- Validate $1 is valid component name (PascalCase)
- Check if component already exists

Create the following files:
1. src/components/$1/$1.tsx - Component implementation
2. src/components/$1/$1.test.tsx - Unit tests
3. src/components/$1/index.ts - Barrel export

Use TypeScript strict mode and follow project conventions.
```

### 悪い例: セキュリティリスクのあるコマンド

```markdown
---
description: Run custom command
---

Execute: bash -c "$ARGUMENTS"
```

**問題点**:
- コマンドインジェクションのリスク
- 入力検証なし
- allowed-tools 制限なし
- argument-hint なし
- モデル指定なし

## レビューレポート

レビュー結果は以下の形式で出力されます：

1. **エグゼクティブサマリー**: 総合評価（A-F グレード、スコア）、主要な発見事項
2. **観点別評価**: 6つの観点ごとの詳細な分析
3. **優先度別の推奨事項**: Critical / High / Medium / Low で分類
4. **具体的な修正例**: Before/After 形式のコード例
5. **次のアクション**: 優先順位付きのアクションアイテム

## スラッシュコマンド vs スキル

スラッシュコマンドとスキルの適切な使い分け：

### スラッシュコマンドが適している場合

- シンプルなプロンプトテンプレート
- プロジェクト固有のコマンド
- 少数の引数のみ
- 動的コンテキストの埋め込み
- 10-50 行程度の内容

### スキルが適している場合

- 複雑な多段階ワークフロー
- 外部リソースの参照が必要
- Progressive Disclosure が必要
- 複数のプロジェクトで再利用
- 100 行以上の詳細な手順

## 注意事項

1. **対象の明確化**: レビュー対象のコマンドファイルパスを明確に指定してください
2. **ファイル形式**: コマンドファイルは Markdown 形式（.md）である必要があります
3. **セキュリティ優先**: セキュリティに関する問題は最優先で対処します
4. **実行可能性**: すべての推奨事項は具体的で実行可能です
5. **テスト推奨**: コマンド変更後は必ず動作をテストしてください

## 関連リンク

- [SKILL.md](./SKILL.md) - 詳細なレビューワークフローと評価基準
- [references/best-practices.md](./references/best-practices.md) - スラッシュコマンドのベストプラクティス
- [references/security-guide.md](./references/security-guide.md) - セキュリティガイド
- [references/examples.md](./references/examples.md) - 良い例・悪い例
- [references/scoring-rubric.md](./references/scoring-rubric.md) - 評価基準とスコアリング

---

このスキルにより、Claude Code スラッシュコマンドの品質とセキュリティを客観的に評価し、継続的な改善を支援します。
