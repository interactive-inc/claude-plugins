# Marketplace Validator

`.claude-plugin/marketplace.json` の構造を検証し、参照されているすべてのファイルパスの存在を確認するスキルです。

## 概要

このスキルは、`.claude-plugin/marketplace.json` ファイルの構造と内容を検証します。特に、すべての参照ファイルパス（スキル、エージェント、リソース）が実際にリポジトリ内に存在するかを確認することに重点を置いています。

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

- **構造検証**: marketplace.json の JSON 構造と必須フィールドを検証
- **パス存在確認**: すべての参照ファイルパスが実際に存在するか確認
- **プラグイン定義チェック**: 各プラグインの name、description、skills/agents の正確性を検証
- **スキル/エージェント検証**: スキルおよびエージェントの SKILL.md、agents/*.md の存在確認
- **自動修正スクリプト**: Python スクリプトによる自動検証と詳細なエラーレポート
- **包括的なエラーレポート**: 発見された問題の詳細な説明と修正方法

## 使用タイミング

このスキルは以下のような場合に使用します：

- `.claude-plugin/marketplace.json` を新規作成または更新した後
- 新しいプラグイン、スキル、エージェントをマーケットプレースに追加する前
- マーケットプレース設定を公開する前のレビュー
- プラグインのインストール問題をトラブルシューティング
- マーケットプレース内のすべてのパスが実際のファイルと一致するか確認

## 検証内容

### 1. ファイル構造

- JSON 構造の妥当性
- 必須フィールドの存在（name, version, plugins）
- 適切なデータ型の使用

### 2. プラグイン定義

- 各プラグインの `name` フィールド
- 各プラグインの `description` フィールド
- スキルとエージェントの配列

### 3. スキルパスの検証

- `skills` 配列内の各スキルパス
- SKILL.md ファイルの存在確認
- frontmatter（name, description）の検証

### 4. エージェントパスの検証

- `agents` 配列内の各エージェントパス
- エージェント定義ファイルの存在確認
- frontmatter（name, description）の検証

### 5. 相対パスの正確性

- すべてのパスがリポジトリルートからの相対パスであること
- パスが実際のファイル/ディレクトリ構造と一致すること

## ファイル構成

```
skills/claude/marketplace-review/
├── README.md              # このファイル
├── SKILL.md              # メインスキル定義
├── scripts/
│   └── validate_marketplace.py    # 自動検証スクリプト
├── references/
│   └── schema-reference.md        # marketplace.json スキーマリファレンス
└── assets/
    └── example-marketplace.json   # マーケットプレース設定の例
```

## 使い方

### 基本的な使用方法

1. **マーケットプレース設定の検証**
   ```
   「marketplace-review スキルを使ってマーケットプレース設定を検証してください」
   ```

2. **自動検証スクリプトの実行**
   ```
   「.claude-plugin/marketplace.json を検証してください」
   ```

3. **プラグイン追加前のチェック**
   ```
   「新しいプラグインをマーケットプレースに追加する前に検証したい」
   ```

### 検証プロセス

詳細な検証プロセスは [SKILL.md](./SKILL.md) を参照してください。

#### ステップ1: マーケットプレースファイルの特定

リポジトリルートの `.claude-plugin/marketplace.json` ファイルを検索します。

#### ステップ2: 検証スクリプトの実行

Python スクリプトを実行して構造とパスを検証：

```bash
python3 scripts/validate_marketplace.py .claude-plugin/marketplace.json
```

#### ステップ3: 結果の確認

検証結果とエラーレポートを確認します：

- ✅ すべてのチェックが成功
- ❌ 発見された問題の詳細
- 📝 推奨される修正方法

#### ステップ4: 問題の修正

エラーレポートに基づいて問題を修正します。

#### ステップ5: 再検証

修正後、再度検証を実行して問題が解決されたことを確認します。

## 検証スクリプト

### スクリプトの機能

`scripts/validate_marketplace.py` は以下を自動的に実行します：

- JSON 構造の妥当性検証
- 必須フィールドの存在確認
- すべての参照パスの存在確認
- 詳細なエラーレポートの生成
- 発見された問題の数のカウント

### スクリプトの使用方法

```bash
# 基本的な使用
python3 scripts/validate_marketplace.py .claude-plugin/marketplace.json

# 詳細な出力
python3 scripts/validate_marketplace.py .claude-plugin/marketplace.json --verbose
```

### 出力例

```
✅ JSON structure is valid
✅ Required fields present: name, version, plugins
✅ Plugin 'claude' definition is valid

Validating plugin 'claude':
  ✅ Skill 'skill-review' path exists: skills/claude/skill-review/SKILL.md
  ✅ Agent 'review-agent' path exists: skills/claude/skill-review/agents/review-agent.md
  ❌ Skill 'missing-skill' path NOT found: skills/claude/missing-skill/SKILL.md

Summary:
  Total checks: 15
  Passed: 14
  Failed: 1

❌ Validation failed. Please fix the issues above.
```

## よくある問題と解決方法

詳細なトラブルシューティングガイドは [SKILL.md](./SKILL.md) を参照してください。

### 問題1: パスが見つからない

**症状**: `Skill path NOT found: skills/...`

**原因**: marketplace.json に記載されたパスが実際のファイルと一致しない

**解決方法**:
1. 実際のファイルパスを確認
2. marketplace.json のパスを修正
3. または、不足しているファイルを作成

### 問題2: JSON 構造エラー

**症状**: `JSON parsing error`

**原因**: JSON 形式が不正（カンマ不足、引用符の不一致など）

**解決方法**:
1. JSON バリデータで構文を確認
2. エディタの JSON フォーマッタを使用
3. 構文エラーを修正

### 問題3: 必須フィールドの欠落

**症状**: `Missing required field: ...`

**原因**: name、description などの必須フィールドがない

**解決方法**:
1. スキーマリファレンスで必須フィールドを確認
2. 不足しているフィールドを追加
3. 適切な値を設定

## スキーマリファレンス

marketplace.json の完全なスキーマは [references/schema-reference.md](./references/schema-reference.md) を参照してください：

### 基本構造

```json
{
  "name": "marketplace-name",
  "version": "1.0.0",
  "plugins": [
    {
      "name": "plugin-name",
      "description": "プラグインの説明",
      "skills": ["skills/path/to/skill"],
      "agents": ["skills/path/to/agent.md"]
    }
  ]
}
```

### 必須フィールド

- `name`: マーケットプレース名（文字列）
- `version`: バージョン番号（セマンティックバージョニング）
- `plugins`: プラグイン配列

### プラグインの必須フィールド

- `name`: プラグイン名（文字列）
- `description`: プラグインの説明（文字列）
- `skills`: スキルディレクトリパスの配列（オプション）
- `agents`: エージェントファイルパスの配列（オプション）

## デフォルト動作

ユーザーが対象を指定しない場合：

1. リポジトリルートの `.claude-plugin/marketplace.json` を検索
2. ファイルが存在する場合は自動的に検証を開始
3. ファイルが存在しない場合は、ユーザーに場所を確認

## 注意事項

1. **リポジトリルート**: スクリプトはリポジトリルートから実行してください
2. **相対パス**: すべてのパスはリポジトリルートからの相対パスで記述します
3. **Python 3**: 検証スクリプトは Python 3.6 以上が必要です
4. **ファイル作成**: 検証のみを行い、ファイルの自動作成はしません
5. **バックアップ推奨**: 大規模な変更前にバックアップを取ることを推奨します

## 関連リンク

- [SKILL.md](./SKILL.md) - 詳細な検証プロセスとトラブルシューティング
- [scripts/validate_marketplace.py](./scripts/validate_marketplace.py) - 自動検証スクリプト
- [references/schema-reference.md](./references/schema-reference.md) - marketplace.json スキーマリファレンス
- [assets/example-marketplace.json](./assets/example-marketplace.json) - マーケットプレース設定の例

---

このスキルにより、マーケットプレース設定の整合性と正確性を自動的に検証し、プラグインのスムーズなインストールと使用を保証します。
