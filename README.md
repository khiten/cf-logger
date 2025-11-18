# cf-logger

A lightweight, production-ready PHP access logger with support for Cloudflare, CloudFront, and custom CDN environments. Includes file locking for concurrent access safety, multiple log formats, and error logging.

日本語版は下記をご覧ください。

---

## Features

- **Multi-CDN Support**: Automatic detection of Cloudflare, CloudFront, and X-Forwarded-For headers
- **Multiple Log Formats**: Simple, Apache/NCSA combined, and JSON formats
- **Automatic Log Rotation**: Daily log splitting with configurable directory structures (yeardir, monthdir, flat)
- **File Locking**: Safe concurrent write operations using flock()
- **Error Logging**: Separate error log with request context and exception details
- **Multi-language Support**: Easy localization for error messages (Japanese, English, Spanish, Chinese)
- **PHP 7.x Compatible**: Works with PHP 7.0 and later
- **Zero Configuration Overhead**: Single `config.php` file for all settings

## Installation

1. Clone or download the `cf-logger` directory to your project:
   ```
   your-project/
     cf-logger/
       config.php
       cf-logger.php
       src/
         Logger.php
       languages/
         ja.php
         en.php
         es.php
         zh.php
   ```

2. Ensure the `logs/` directory is writable:
   ```bash
   chmod 755 your-project/cf-logger/logs
   ```

3. (Optional) Create additional language files in `languages/` if needed

## Quick Start

### Basic Usage

Include `cf-logger.php` at the beginning of your PHP template or application:

```php
<?php
require_once __DIR__ . '/cf-logger/cf-logger.php';
// Your application code here
?>
```

That's it! Access logs will be automatically recorded when the page executes.

### Configuration

Edit `cf-logger/config.php` to customize behavior. Each setting is clearly documented with examples:

```php
define('LOG_FORMAT_TYPE', 'simple');          // Output format
define('LOG_DATE_FORMAT', 'Y/m/d H:i:s');     // Date format
define('LOG_STATUS_ENABLED', true);           // Log HTTP status
define('LOG_DIRECTORY', 'logs');              // Log storage directory
define('LOG_FILE_TYPE', 'rotate');            // File rotation mode
define('LOG_DIRECTORY_MODE', 'yeardir');      // Directory structure
define('LOG_LANGUAGE', 'en');                 // Error message language
```

## Configuration Options

### LOG_FORMAT_TYPE
- `'simple'` (default): Compact format with device, browser, method, URI, and status
- `'combined'`: Apache/NCSA combined log format
- `'json'`: JSON object format

**Output example (simple format):**
```
2025/11/18 17:48:04 153.231.52.53 PC Chrome/142.0.0.0 GET /single.php 200
```

### LOG_DATE_FORMAT
PHP `date()` format string, or empty string (`''`) for Apache NCSA default.

**Common examples:**
- `'Y/m/d H:i:s'` (2025/11/18 17:48:04 — commonly used in Japan, China)
- `'d/m/Y H:i:s'` (18/11/2025 17:48:04 — common in UK, Spain, much of Europe and Latin America)
- `'m/d/Y H:i:s'` (11/18/2025 17:48:04 — United States style)
- `'Y-m-d H:i:s'` (2025-11-18 17:48:04 — ISO8601-inspired, international/tech standard)
- `'Y年m月d日 H:i:s'` (2025年11月18日 17:48:04 — Japanese locale)
- `'j M Y, H:i:s'` (18 Nov 2025, 17:48:04 — alternate English style)
- `'d/M/Y:H:i:s O'` (18/Nov/2025:17:48:04 +0900 — Apache NCSA default)

### LOG_STATUS_ENABLED
- `true`: Include HTTP response status code in logs
- `false`: Log status as `'-'`

### LOG_DIRECTORY
- Relative path: `'logs'`, `'../logs'`
- Absolute path: `'/var/www/logs'`

### LOG_FILE_TYPE
- `'rotate'`: Create a new log file each day
- `'single'`: Write all logs to one cumulative file

### LOG_DIRECTORY_MODE
(Only used when `LOG_FILE_TYPE` is `'rotate'`)
- `'yeardir'`: Structure: `logs/2025/11/cf_access_20251118.log`
- `'monthdir'`: Structure: `logs/202511/cf_access_20251118.log`
- `'flat'`: Structure: `logs/cf_access_20251118.log`

### LOG_LANGUAGE
- Supported: `'en'`, `'ja'`, `'es'`, `'zh'` (based on available language files in `languages/`)
- Used for error messages and exceptions

## Log Output Examples

### Simple Format
```
2025/11/18 17:48:04 153.231.52.53 PC Chrome/142.0.0.0 GET /index.php 200
2025/11/18 17:48:05 192.168.1.100 Mobile Safari/14.1 POST /api/submit 201
2025/11/18 17:48:06 10.0.0.1 PC Firefox/102.0 GET /static/image.jpg 304
2025/11/18 17:48:07 172.16.0.50 PC Bot:Googlebot GET /sitemap.xml 200
```

### Combined Format
```
153.231.52.53 - - [18/Nov/2025:17:48:04 +0900] "GET /index.php HTTP/1.1" 200 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36"
```

### JSON Format
```json
{"timestamp":"2025/11/18 17:48:04","ip":"153.231.52.53","status":"200","method":"GET","uri":"/index.php","protocol":"HTTP/1.1","referer":"-","user_agent":"Mozilla/5.0..."}
```

### Error Log (logs/error.log)
```
[2025-11-18 17:48:04] DIR_CREATE_FAIL: Unable to open log file: /var/www/logs/2025/11/cf_access_20251118.log | {"error_code":0,"error_file":"/var/www/cf-logger/src/Logger.php","error_line":142,"request_uri":"/index.php","request_ip":"153.231.52.53"}
```

## CDN Support

### Automatic Detection
The logger automatically detects and prioritizes the following CDN headers:

1. **Cloudflare**: `HTTP_CF_CONNECTING_IP`
2. **AWS CloudFront**: `HTTP_CLOUDFRONT_VIEWER_ADDRESS`
3. **Generic proxies**: `HTTP_X_FORWARDED_FOR`
4. **Direct connection**: `REMOTE_ADDR`

No configuration needed—the logger will use whichever header is available in your environment.

## Browser & Bot Detection

The logger automatically identifies:
- **Device type**: PC or Mobile
- **Browser**: Chrome, Firefox, Safari, Edge, Other
- **Known bots**: Googlebot, Bingbot, DuckDuckBot, Baiduspider, YandexBot, etc.
- **Unknown bots**: Generic bot/crawler keywords

Example outputs:
- `PC Chrome/142.0.0.0`
- `Mobile Safari/14.1`
- `PC Bot:Googlebot`
- `Mobile Bot:unknown`

## Concurrent Access Safety

The logger uses file locking (`flock()`) to ensure data integrity during high-traffic scenarios:
- Multiple simultaneous requests are handled safely
- Log entries are never corrupted or duplicated
- Suitable for sites with hundreds of concurrent users

## Directory Structure

```
cf-logger/
├── config.php              # Configuration file (edit this!)
├── cf-logger.php           # Main entry point for PHP scripts
├── src/
│   └── Logger.php          # Core logging class
├── languages/
│   ├── ja.php              # Japanese error messages
│   ├── en.php              # English error messages
│   ├── es.php              # Spanish error messages
│   └── zh.php              # Chinese error messages
└── logs/
    ├── error.log           # Error log (single file, cumulative)
    └── 2025/11/            # Access logs (date-based rotation)
        └── cf_access_20251118.log
```

## Usage Examples

### WordPress Integration (Future Plugin)
```php
// In wp-content/plugins/cf-logger/cf-logger-wp.php
add_action('shutdown', function() {
    require_once __DIR__ . '/cf-logger.php';
});
```

### Custom CMS Integration
```php
<?php
// In your template or header
require_once __DIR__ . '/cf-logger/cf-logger.php';
?>
```

### Composer Package (Future)
```bash
composer require khiten/cf-logger
```

## Troubleshooting

### Logs not being written
- Check that `logs/` directory exists and is writable: `chmod 755 logs/`
- Verify `LOG_DIRECTORY` path in `config.php` is correct
- Check error log: `logs/error.log` for detailed messages

### Wrong IP logged
- Ensure your CDN headers are correctly configured
- For Cloudflare: verify "Web Traffic" is enabled
- For CloudFront: verify origin headers are passed

### File permission errors
- Make sure the web server user has write access: `chown -R www-data:www-data logs/`

## Performance Notes

- File locking adds minimal overhead (typically < 1ms per request)
- For extremely high-traffic sites (10k+ req/sec), consider log aggregation services
- Log rotation files are automatically managed—no manual cleanup needed

## Security Considerations

- The logger respects HTTP header priorities (Cloudflare > CloudFront > X-Forwarded-For)
- Always validate trusted proxies if behind multiple proxies
- Protect `logs/` directory from public access (outside web root is recommended)

## Localization

To add a new language, create a file in `languages/` (e.g., `languages/fr.php`):

```php
<?php
return [
    'DIR_NOT_WRITABLE' => 'Impossible d\'écrire dans le répertoire des journaux: %s',
    'DIR_CREATE_FAIL'  => 'Impossible de créer le répertoire des journaux: %s'
];
?>
```

Then set `LOG_LANGUAGE` in `config.php`:
```php
define('LOG_LANGUAGE', 'fr');
```

## Requirements

- PHP 7.0 or later
- Write permissions on `logs/` directory
- Linux/Unix or Windows with file locking support

## Documentation

- **Setup Guide (Rental Servers)**: [https://khiten.github.io/cf-logger/SETUP-rental-ja.html](https://khiten.github.io/cf-logger/SETUP-rental-ja.html)
- **Setup Guide (Standard)**: [https://khiten.github.io/cf-logger/SETUP-ja.html](https://khiten.github.io/cf-logger/SETUP-ja.html)
- **GitHub Repository**: [https://github.com/khiten/cf-logger](https://github.com/khiten/cf-logger)

## License

[Specify your license here - MIT, GPL, etc.]

## Support

For issues, feature requests, or contributions, please visit the [GitHub repository](https://github.com/khiten/cf-logger).

---

# 日本語版ドキュメント

## 機能

- **複数CDN対応**: Cloudflare、CloudFront、汎用X-Forwarded-Forヘッダーを自動判定
- **複数ログ形式対応**: シンプル形式、Apache/NCSA combined形式、JSON形式
- **自動ログローテーション**: 日単位でログファイルを分割。ディレクトリ構成を選択可能（yeardir/monthdir/flat）
- **ファイルロック対応**: 複数プロセス同時実行時も安全にログ記録
- **エラーログ機能**: 独立したエラーログにリクエストコンテキスト付きで記録
- **多言語対応**: エラーメッセージの言語切り替え可能（日本語、英語、スペイン語、中国語）
- **PHP 7.x互換**: PHP 7.0以降で動作
- **ゼロ設定**: 設定ファイル1つで全機能をコントロール可能

## インストール

1. `cf-logger` ディレクトリをプロジェクトにダウンロード・配置します：

```
your-project/
  cf-logger/
    config.php
    cf-logger.php
    src/
      Logger.php
    languages/
      ja.php
      en.php
      es.php
      zh.php
```

2. `logs/` ディレクトリが書き込み可能になっていることを確認します：

```bash
chmod 755 your-project/cf-logger/logs
```

3. （オプション）追加言語が必要な場合、`languages/` に言語ファイルを作成します

## 基本的な使い方

### 最小限の導入

PHPファイルやテンプレートの先頭で `cf-logger.php` をインクルードするだけです：

```php
<?php
require_once __DIR__ . '/cf-logger/cf-logger.php';
// ここからアプリケーションコード
?>
```

完了です！ページにアクセスすると自動的にログが記録されます。

### 設定変更

`cf-logger/config.php` を編集して動作をカスタマイズできます。各設定項目にはコメントと例が記載されています：

```php
define('LOG_FORMAT_TYPE', 'simple');          // ログ形式
define('LOG_DATE_FORMAT', 'Y/m/d H:i:s');     // 日時フォーマット
define('LOG_STATUS_ENABLED', true);           // ステータスコード記録
define('LOG_DIRECTORY', 'logs');              // ログ保存先
define('LOG_FILE_TYPE', 'rotate');            // ファイルローテーション
define('LOG_DIRECTORY_MODE', 'yeardir');      // ディレクトリ構成
define('LOG_LANGUAGE', 'ja');                 // 言語設定
```

## 設定項目の説明

### LOG_FORMAT_TYPE
- `'simple'`（デフォルト）: コンパクト形式。デバイス、ブラウザ、メソッド、URI、ステータス
- `'combined'`: Apache/NCSA combined形式
- `'json'`: JSON形式

**出力例（simple形式）:**
```
2025/11/18 17:48:04 153.231.52.53 PC Chrome/142.0.0.0 GET /single.php 200
```

### LOG_DATE_FORMAT
PHP `date()` 形式文字列、または空文字列 `''` で Apache NCSA 標準形式を使用。

**よく使われる例:**
- `'Y/m/d H:i:s'` (2025/11/18 17:48:04 — 日本、中国でよく使用)
- `'d/m/Y H:i:s'` (18/11/2025 17:48:04 — イギリス、スペイン、ヨーロッパ・ラテンアメリカで一般的)
- `'m/d/Y H:i:s'` (11/18/2025 17:48:04 — アメリカ式)
- `'Y-m-d H:i:s'` (2025-11-18 17:48:04 — ISO8601準拠、国際/技術標準)
- `'Y年m月d日 H:i:s'` (2025年11月18日 17:48:04 — 日本語ロケール)
- `'j M Y, H:i:s'` (18 Nov 2025, 17:48:04 — 英語別形式)
- `'d/M/Y:H:i:s O'` (18/Nov/2025:17:48:04 +0900 — Apache NCSA デフォルト)

### LOG_STATUS_ENABLED
- `true`: HTTPレスポンスステータスコードをログに含める
- `false`: ステータスを `'-'` で記録

### LOG_DIRECTORY
- 相対パス: `'logs'`、`'../logs'`
- 絶対パス: `'/var/www/logs'`

### LOG_FILE_TYPE
- `'rotate'`: 1日ごとに新しいログファイルを作成
- `'single'`: 全ログを1ファイルに累積

### LOG_DIRECTORY_MODE
（`LOG_FILE_TYPE` が `'rotate'` の場合のみ有効）
- `'yeardir'`: 構成例 `logs/2025/11/cf_access_20251118.log`
- `'monthdir'`: 構成例 `logs/202511/cf_access_20251118.log`
- `'flat'`: 構成例 `logs/cf_access_20251118.log`

### LOG_LANGUAGE
- 対応言語: `'ja'`、`'en'`、`'es'`、`'zh'` (`languages/` 内の言語ファイルに基づく)
- エラーメッセージの表示言語

## ログ出力例

### シンプル形式
```
2025/11/18 17:48:04 153.231.52.53 PC Chrome/142.0.0.0 GET /index.php 200
2025/11/18 17:48:05 192.168.1.100 Mobile Safari/14.1 POST /api/submit 201
2025/11/18 17:48:06 10.0.0.1 PC Firefox/102.0 GET /static/image.jpg 304
2025/11/18 17:48:07 172.16.0.50 PC Bot:Googlebot GET /sitemap.xml 200
```

### Combined形式
```
153.231.52.53 - - [18/Nov/2025:17:48:04 +0900] "GET /index.php HTTP/1.1" 200 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36"
```

### JSON形式
```json
{"timestamp":"2025/11/18 17:48:04","ip":"153.231.52.53","status":"200","method":"GET","uri":"/index.php","protocol":"HTTP/1.1","referer":"-","user_agent":"Mozilla/5.0..."}
```

### エラーログ（logs/error.log）
```
[2025-11-18 17:48:04] DIR_CREATE_FAIL: ログファイルを開くことができません: /var/www/logs/2025/11/cf_access_20251118.log | {"error_code":0,"error_file":"/var/www/cf-logger/src/Logger.php","error_line":142,"request_uri":"/index.php","request_ip":"153.231.52.53"}
```

## CDN対応

### 自動判定
ロガーは以下のCDNヘッダーを自動的に判定します（優先度順）：

1. **Cloudflare**: `HTTP_CF_CONNECTING_IP`
2. **AWS CloudFront**: `HTTP_CLOUDFRONT_VIEWER_ADDRESS`
3. **汎用プロキシ**: `HTTP_X_FORWARDED_FOR`
4. **直接接続**: `REMOTE_ADDR`

設定不要です。環境に応じて自動的に適切なヘッダーが使用されます。

## ブラウザ・Bot自動判定

以下の項目を自動判定します：
- **デバイス**: PC / Mobile
- **ブラウザ**: Chrome、Firefox、Safari、Edge、その他
- **既知Bot**: Googlebot、Bingbot、DuckDuckBot、Baiduspider、YandexBot等
- **未知Bot**: その他のbot/クローラーキーワード

出力例：
- `PC Chrome/142.0.0.0`
- `Mobile Safari/14.1`
- `PC Bot:Googlebot`
- `Mobile Bot:unknown`

## 大量同時アクセス対応

ファイルロック（`flock()`）により高トラフィック時もデータ整合性を保証：
- 複数の同時リクエストでも安全
- ログ行の破損・重複なし
- 数百の同時ユーザーに対応

## ディレクトリ構成

```
cf-logger/
├── config.php              # 設定ファイル（編集対象）
├── cf-logger.php           # PHPスクリプト用エントリーポイント
├── src/
│   └── Logger.php          # ロギング本体クラス
├── languages/
│   ├── ja.php              # 日本語エラーメッセージ
│   ├── en.php              # 英語エラーメッセージ
│   ├── es.php              # スペイン語エラーメッセージ
│   └── zh.php              # 中国語エラーメッセージ
└── logs/
    ├── error.log           # エラーログ（単一ファイル、累積）
    └── 2025/11/            # アクセスログ（日単位でローテーション）
        └── cf_access_20251118.log
```

## トラブルシューティング

### ログが記録されない
- `logs/` ディレクトリが存在し、書き込み可能か確認：`chmod 755 logs/`
- `config.php` の `LOG_DIRECTORY` パスが正しいか確認
- エラーログ確認：`logs/error.log` に詳細が記録されています

### 記録されるIPが間違っている
- CDNのヘッダー設定を確認
- Cloudflare使用時：「Web Traffic」が有効になっているか確認
- CloudFront使用時：オリジンヘッダーが正しく渡されているか確認

### ファイル権限エラー
- Webサーバーユーザーが書き込み権限を持っているか確認：`chown -R www-data:www-data logs/`

## パフォーマンス

- ファイルロック時のオーバーヘッドは最小限（通常 < 1ms/リクエスト）
- 超高負荷サイト（10k+リクエスト/秒）の場合はログ集約サービスの導入を検討
- ログファイルのローテーションは自動管理。手動クリーンアップ不要

## セキュリティ

- HTTPヘッダーの優先度を適切に設定（Cloudflare > CloudFront > X-Forwarded-For）
- 複数プロキシ背後の場合は信頼できるプロキシを検証
- `logs/` ディレクトリはWeb公開フォルダの外に配置することを推奨

## 多言語対応

新しい言語を追加するには `languages/` に言語ファイルを作成します（例：`languages/fr.php`）：

```php
<?php
return [
    'DIR_NOT_WRITABLE' => 'Impossible d\'écrire dans le répertoire des journaux: %s',
    'DIR_CREATE_FAIL'  => 'Impossible de créer le répertoire des journaux: %s'
];
?>
```

`config.php` で言語を指定：
```php
define('LOG_LANGUAGE', 'fr');
```

## 必要要件

- PHP 7.0以降
- `logs/` ディレクトリへの書き込み権限
- Linux/Unix または Windows（ファイルロック対応）

## ドキュメント

- **セットアップガイド（レンタルサーバー用）**: [https://khiten.github.io/cf-logger/SETUP-rental-ja.html](https://khiten.github.io/cf-logger/SETUP-rental-ja.html)
- **セットアップガイド（標準版）**: [https://khiten.github.io/cf-logger/SETUP-ja.html](https://khiten.github.io/cf-logger/SETUP-ja.html)
- **GitHubリポジトリ**: [https://github.com/khiten/cf-logger](https://github.com/khiten/cf-logger)

## ライセンス

[ライセンスを指定してください - MIT、GPLなど]

## サポート

問題報告、機能リクエスト、PRは [GitHubリポジトリ](https://github.com/khiten/cf-logger) へお願いします。

---

**Version**: 1.0.0  
**Last Updated**: 2025-11-18
