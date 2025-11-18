# MCP Review

MCP (Model Context Protocol) サーバー設定をベストプラクティスに照らして検証・レビューするスキルです。

## 概要

このスキルは、`.mcp.json` ファイル内の MCP サーバー設定を検証し、セキュリティ、スコープ管理、環境変数の使用、トランスポートタイプ、一般的な落とし穴などのベストプラクティスに準拠しているかを評価します。設定の改善のための実行可能な推奨事項を提供します。

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

- **7つの検証観点**: ファイル構造、セキュリティ、スコープ管理、トランスポートタイプ、サーバー設定、環境変数、一般的な落とし穴
- **セキュリティ重視**: ハードコードされた秘密情報、適切な環境変数使用、安全なヘッダー設定の検証
- **ベストプラクティス検証**: 適切なスコープ選択、正しいトランスポートタイプ、OAuth 設定の確認
- **実行可能な推奨事項**: 各問題に対する具体的な修正方法と例
- **包括的なエラー検出**: 構文エラー、設定ミス、セキュリティリスクを特定
- **新規設定作成支援**: 一般的なユースケース向けの設定作成をガイド

## 使用タイミング

このスキルは以下のような場合に使用します：

- 既存の `.mcp.json` 設定をレビュー
- 新規 MCP サーバー設定を作成
- MCP 接続または認証の問題をトラブルシューティング
- セキュリティベストプラクティスに従っているか確認
- 環境変数の使用を検証
- トランスポートタイプの適切性を確認

## 検証観点

このスキルは、MCP 設定を 7 つの観点から評価します：

### 1. ファイル構造

- 有効な JSON 構造
- 適切な mcpServers オブジェクト
- 正しいスキーマ準拠

### 2. セキュリティ

- ハードコードされた秘密情報の検出
- 適切な環境変数の使用
- 安全なヘッダー設定
- API キーやトークンの保護

### 3. スコープ管理

- 適切なスコープ選択（local/project/user）
- スコープごとの設定ファイルの配置
- スコープの使い分け

### 4. トランスポートタイプ

- 正しいトランスポートの選択（stdio/http/sse）
- ユースケースに応じた適切なトランスポート
- トランスポート固有の設定

### 5. サーバー設定

- command/args の妥当性
- URL フォーマット
- ヘッダー設定
- タイムアウト設定

### 6. 環境変数

- 適切な ${VAR} 構文
- フォールバック値 ${VAR:-default} の使用
- 環境変数の命名規則

### 7. 一般的な落とし穴

- Windows の `cmd /c` ラッパー
- 実行可能ファイルのパス
- OAuth セットアップ
- ポート衝突

## ファイル構成

```
skills/claude/mcp-review/
├── README.md              # このファイル
├── SKILL.md              # メインスキル定義
└── references/
    ├── best-practices.md          # MCP 設定のベストプラクティス
    ├── security-checklist.md      # セキュリティチェックリスト
    ├── transport-types.md         # トランスポートタイプの選択ガイド
    └── common-issues.md           # 一般的な問題と解決方法
```

## 使い方

### 基本的な使用方法

1. **既存設定のレビュー**
   ```
   「mcp-review スキルを使って MCP 設定をレビューしてください」
   ```

2. **特定の設定ファイルをレビュー**
   ```
   「.mcp.json のセキュリティをチェックしてください」
   ```

3. **新規設定作成のガイド**
   ```
   「GitHub MCP サーバーの設定を作成したい」
   ```

### 検証プロセス

詳細な検証プロセスは [SKILL.md](./SKILL.md) を参照してください。

#### ステップ1: 設定ファイルの特定

プロジェクト内の MCP 設定ファイルを検索：

```bash
# プロジェクトスコープ
.claude/.mcp.json

# ユーザースコープ
~/.claude/.mcp.json

# ローカルスコープ
.claude/.mcp.local.json
```

#### ステップ2: 構造検証

JSON 構造と mcpServers オブジェクトの妥当性を確認。

#### ステップ3: セキュリティ分析

ハードコードされた秘密情報、不安全な設定を検出。

#### ステップ4: ベストプラクティス評価

7 つの観点から設定を評価。

#### ステップ5: 推奨事項の提供

具体的な改善提案と修正例を提示。

## 設定例

### stdio トランスポート（ローカルツール）

```json
{
  "mcpServers": {
    "filesystem": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-filesystem", "/path/to/allowed/files"],
      "scope": "project"
    }
  }
}
```

### HTTP トランスポート（リモート API）

```json
{
  "mcpServers": {
    "github": {
      "url": "https://github-mcp.example.com",
      "headers": {
        "Authorization": "Bearer ${GITHUB_TOKEN}"
      },
      "scope": "user"
    }
  }
}
```

### SSE トランスポート（ストリーミング）

```json
{
  "mcpServers": {
    "events": {
      "url": "https://events.example.com/sse",
      "transport": "sse",
      "scope": "project"
    }
  }
}
```

## セキュリティチェックリスト

詳細なセキュリティチェックリストは [references/security-checklist.md](./references/security-checklist.md) を参照してください：

### 必須チェック項目

- [ ] API キーやトークンがハードコードされていない
- [ ] すべての秘密情報が環境変数で管理されている
- [ ] 環境変数が適切な構文（${VAR}）で参照されている
- [ ] ヘッダーに平文の認証情報が含まれていない
- [ ] URL に秘密情報がクエリパラメータとして含まれていない
- [ ] 実行可能ファイルのパスが検証されている
- [ ] コマンドインジェクションのリスクがない

### 推奨チェック項目

- [ ] 環境変数にフォールバック値が設定されている
- [ ] タイムアウト設定が適切
- [ ] ログレベルが適切に設定されている
- [ ] エラーハンドリングが実装されている

## トランスポートタイプの選択

詳細なガイドは [references/transport-types.md](./references/transport-types.md) を参照してください。

### stdio

**用途**: ローカルツール、ファイルシステムアクセス、コマンドライン統合

**利点**: シンプル、セキュア、低レイテンシ

**設定例**:
```json
{
  "command": "node",
  "args": ["server.js"]
}
```

### HTTP

**用途**: リモート API、RESTful サービス、クラウドサービス

**利点**: 広くサポート、ステートレス、スケーラブル

**設定例**:
```json
{
  "url": "https://api.example.com",
  "headers": {"Authorization": "Bearer ${API_KEY}"}
}
```

### SSE (Server-Sent Events)

**用途**: リアルタイムストリーミング、イベント駆動、長時間接続

**利点**: 単方向ストリーミング、自動再接続、効率的

**設定例**:
```json
{
  "url": "https://events.example.com/sse",
  "transport": "sse"
}
```

## よくある問題と解決方法

詳細なトラブルシューティングガイドは [references/common-issues.md](./references/common-issues.md) を参照してください。

### 問題1: 認証エラー

**症状**: "Authentication failed" または "Unauthorized"

**原因**: 環境変数が正しく設定されていない、または構文エラー

**解決方法**:
1. 環境変数が設定されているか確認: `echo $VARIABLE_NAME`
2. 環境変数の構文を確認: `${VAR}` が正しいか
3. フォールバック値を試す: `${VAR:-default_value}`

### 問題2: 接続タイムアウト

**症状**: "Connection timeout" または "Server not responding"

**原因**: サーバーが起動していない、URL が間違っている、ネットワーク問題

**解決方法**:
1. サーバーが実行中か確認
2. URL とポートが正しいか確認
3. ファイアウォール設定を確認
4. タイムアウト値を増やす

### 問題3: Windows での実行エラー

**症状**: "Command not found" on Windows

**原因**: Windows の実行ラッパーが必要

**解決方法**:
```json
{
  "command": "cmd",
  "args": ["/c", "node", "server.js"]
}
```

## ベストプラクティス

詳細なベストプラクティスガイドは [references/best-practices.md](./references/best-practices.md) を参照してください：

### スコープの選択

- **project**: プロジェクト固有の設定（`.claude/.mcp.json`）
- **user**: すべてのプロジェクトで共有（`~/.claude/.mcp.json`）
- **local**: ローカル開発のみ（`.claude/.mcp.local.json`、gitignore に追加）

### 環境変数の管理

- `.env` ファイルで管理
- `.env.example` でテンプレート提供
- 秘密情報は `.gitignore` に追加
- フォールバック値で開発を容易に

### エラーハンドリング

- 適切なタイムアウト設定
- リトライロジックの実装
- 明確なエラーメッセージ
- ログの適切なレベル設定

## 注意事項

1. **設定ファイルの場所**: レビュー対象の MCP 設定ファイルのパスを明確に指定してください
2. **JSON 形式**: 設定ファイルは有効な JSON 形式である必要があります
3. **環境変数**: 使用する環境変数が実際に設定されているか確認してください
4. **セキュリティ優先**: セキュリティに関する問題は最優先で対処します
5. **テスト推奨**: 設定変更後は必ず接続をテストしてください

## 関連リンク

- [SKILL.md](./SKILL.md) - 詳細な検証プロセスと評価基準
- [references/best-practices.md](./references/best-practices.md) - MCP 設定のベストプラクティス
- [references/security-checklist.md](./references/security-checklist.md) - セキュリティチェックリスト
- [references/transport-types.md](./references/transport-types.md) - トランスポートタイプの選択ガイド
- [references/common-issues.md](./references/common-issues.md) - 一般的な問題と解決方法

---

このスキルにより、MCP サーバー設定のセキュリティと品質を客観的に評価し、効果的な Model Context Protocol 統合を支援します。
