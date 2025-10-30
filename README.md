# Claude Code プラグインマーケットプレース

Interactive Inc.が提供する Claude Code 用のプラグイン集です。

## インストール方法

### 前提条件

- Claude Code がインストールされていること

### マーケットプレースの追加

`/plugins` コマンドからマーケットプレースを追加:

```bash
/plugin marketplace add interactive-inc/claude-plugins
```

### プラグインのインストール

マーケットプレース追加後、以下のコマンドでプラグインをインストールできます：

```bash
# reviewing-code プラグインのインストール
/plugin install reviewing-code@interactive-claude-plugins

# reviewing-skills プラグインのインストール
/plugin install reviewing-skills@interactive-claude-plugins
```

## 提供プラグイン

### 1. reviewing-code

**説明**: 実装コードを 6 つの専門エージェントで多角的にレビューし、トレードオフを考慮した優先度付き改善計画を策定します。

**主な機能**:

- 複数の設計原則に基づく包括的なコードレビュー
- 優先度付きの改善提案
- 段階的な実装計画の策定
- トレードオフ分析

**含まれるエージェント**:

- `review-srp-reviewer` - Single Responsibility Principle（単一責任の原則）の評価
- `review-human-code-reviewer` - Code for Humans 原則（可読性）の評価
- `review-kiss-reviewer` - KISS 原則（シンプルさ）の評価
- `review-coc-reviewer` - Convention over Configuration（規約優先）の評価
- `review-typescript-comprehensive` - TypeScript 型安全性の総合評価
- `review-garbage-detector` - 不要ファイル・ゴミファイルの検出

**使用場面**:

- 新機能実装後の品質確認
- リファクタリング前の現状分析
- 定期的なコード品質監査
- 技術的負債の特定と優先順位付け

### 2. reviewing-skills

**説明**: Claude Code スキルをベストプラクティスに照らして包括的にレビューし、具体的な改善提案を提供します。

**主な機能**:

- スキルファイルの構造と内容の評価
- ベストプラクティスへの準拠チェック
- A-F の総合評価スコアの算出
- 具体的な改善例の提示

**評価観点**:

- 命名規則（Naming Convention）
- Description の品質と発見可能性
- Progressive Disclosure の実装状況
- コンテンツの簡潔性と明確性
- ワークフローと検証機構
- テンプレートと例の提供

**使用場面**:

- 自作スキルの品質チェック
- スキルのベストプラクティス準拠確認
- スキルの改善提案取得
