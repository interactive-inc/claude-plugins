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
# review-skills プラグインのインストール
/plugin install review-skills@interactive-claude-plugins
```

## 利用可能なプラグイン

### review-skills

コードレビューと品質評価を支援するプラグインです。複数の専門エージェントとスキルを組み合わせて、多角的なコード分析を実行できます。

#### 含まれるスキル

**reviewing-ts-code**

- 実装コードを 6 つの専門エージェント（SRP、可読性、KISS、規約、TypeScript、ゴミファイル検出）で多角的にレビュー
- トレードオフを考慮した優先度付き改善計画を策定

**claude-skills-reviewer**

- Claude Code スキル自体をベストプラクティスに照らしてレビュー
- スキルの品質、構造、発見可能性、効率性を 8 つの観点から評価
- A-F 評価とスコアを算出

#### 含まれるエージェント

以下の 6 つの専門レビューエージェントが利用可能です：

- **review-srp-reviewer**: Single Responsibility Principle（単一責任の原則）評価
- **review-human-code-reviewer**: Code for Humans（可読性）評価
- **review-kiss-reviewer**: KISS 原則（シンプルさ）評価
- **review-coc-reviewer**: Convention over Configuration（規約）評価
- **review-typescript-comprehensive**: TypeScript 型安全性総合評価
- **review-garbage-detector**: 不要ファイル・ゴミファイル検出

これらのエージェントは、スキル実行時に自動的に呼び出されます。
