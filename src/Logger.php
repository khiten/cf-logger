<?php
class Logger
{
	public $config;
	
	public function __construct($config = [])
	{
		$defaults = [
			'format_type'    => 'simple',
			'date_format'    => '',
			'status_enabled' => true,
			'directory'      => 'logs',
			'file_type'      => 'rotate',
			'directory_mode' => 'yeardir',
		];
		$this->config = array_merge($defaults, $config);
	}
	
	public function getRealIp()
	{
		// Cloudflare優先
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		
		// CloudFront（AWS）次優先
		if (isset($_SERVER['HTTP_CLOUDFRONT_VIEWER_ADDRESS'])) {
			return $_SERVER['HTTP_CLOUDFRONT_VIEWER_ADDRESS'];
		}
		
		// 汎用X-Forwarded-For（複数IP対応、左端=元のクライアントIP）
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
		}
		
		// フォールバック
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public function getBrowserSummary($ua)
	{
		$device = 'PC';
		if (stripos($ua, 'android') !== false || stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false || stripos($ua, 'mobile') !== false) {
			$device = 'Mobile';
		}
		$bots = [
			'Googlebot'   => 'Googlebot',
			'Bingbot'     => 'Bingbot',
			'Slurp'       => 'Yahoo! Slurp',
			'DuckDuckBot' => 'DuckDuckBot',
			'Baiduspider' => 'Baiduspider',
			'YandexBot'   => 'YandexBot',
			'Sogou'       => 'Sogou',
			'Exabot'      => 'Exabot',
			'facebot'     => 'Facebook',
			'ia_archiver' => 'Alexa'
		];
		foreach ($bots as $keyword => $botname) {
			if (stripos($ua, $keyword) !== false) {
				return $device . ' Bot:' . $botname;
			}
		}
		if (preg_match('/bot|crawl|spider|slurp|baidu|duckduckgo|bing|yandex/i', $ua)) {
			return $device . ' Bot:unknown';
		}
		if (preg_match('/(Firefox)\/([\d\.]+)/i', $ua, $match)) {
			$browser = $match[1] . '/' . $match[2];
		} elseif (preg_match('/(Chrome)\/([\d\.]+)/i', $ua, $match) && stripos($ua, 'Edge/') === false) {
			$browser = $match[1] . '/' . $match[2];
		} elseif (preg_match('/(Edg|Edge)\/([\d\.]+)/i', $ua, $match)) {
			$browser = 'Edge/' . $match[2];
		} elseif (preg_match('/Version\/([\d\.]+).*Safari/', $ua, $match)) {
			$browser = 'Safari/' . $match[1];
		} else {
			$browser = 'Other';
		}
		return $device . ' ' . $browser;
	}
	
	public function ensureDir($dir)
	{
		if (!is_dir($dir)) {
			$parent = dirname($dir);
			if (!is_dir($parent)) $this->ensureDir($parent);
			if (!is_writable($parent)) throw new Exception("DIR_NOT_WRITABLE: $parent");
			if (!mkdir($dir, 0755, true) && !is_dir($dir)) throw new Exception("DIR_CREATE_FAIL: $dir");
		}
		if (!is_writable($dir)) throw new Exception("DIR_NOT_WRITABLE: $dir");
		return $dir;
	}
	
	public function getLogFile()
	{
		$basedir = $this->config['directory'];
		if ($basedir === '') $basedir = getcwd();
		if (strpos($basedir, '/') !== 0) $basedir = __DIR__ . '/../' . $basedir;
		
		if ($this->config['file_type'] === 'rotate') {
			$y = date('Y');
			$m = date('m');
			$ym = date('Ym');
			$datepart = date('Ymd');
			if ($this->config['directory_mode'] === 'yeardir') {
				$dir = "{$basedir}/{$y}/{$m}";
			} elseif ($this->config['directory_mode'] === 'monthdir') {
				$dir = "{$basedir}/{$ym}";
			} else {
				$dir = $basedir;
			}
			$this->ensureDir($dir);
			return "{$dir}/cf_access_{$datepart}.log";
		} else {
			$this->ensureDir($basedir);
			return "{$basedir}/cf_access.log";
		}
	}
	
	public function makeLogLine()
	{
		$format     = $this->config['format_type'];
		$date_fmt   = $this->config['date_format'];
		$time       = $date_fmt ? date($date_fmt) : date('d/M/Y:H:i:s O');
		$ip         = $this->getRealIp();
		$method     = $_SERVER['REQUEST_METHOD'];
		$uri        = $_SERVER['REQUEST_URI'];
		$protocol   = $_SERVER['SERVER_PROTOCOL'];
		$status     = ($this->config['status_enabled'] && function_exists('http_response_code')) ? http_response_code() : '-';
		$size       = '-';
		$referer    = $_SERVER['HTTP_REFERER'] ?? '-';
		$ua         = $_SERVER['HTTP_USER_AGENT'] ?? '-';
		
		if ($format === 'simple') {
			$ua_simple = $this->getBrowserSummary($ua);
			return "{$time} {$ip} {$ua_simple} {$method} {$uri} {$status}";
		}
		elseif ($format === 'combined') {
			return sprintf('%s - - [%s] "%s %s %s" %s %s "%s" "%s"',
				$ip, $time, $method, $uri, $protocol, $status, $size, $referer, $ua);
		}
		elseif ($format === 'json') {
			$log = [
				'timestamp'  => $time,
				'ip'         => $ip,
				'status'     => $status,
				'method'     => $method,
				'uri'        => $uri,
				'protocol'   => $protocol,
				'referer'    => $referer,
				'user_agent' => $ua
			];
			return json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		return '';
	}
	
	public function write($extra = [])
	{
		$logfile = $this->getLogFile();
		$line    = $this->makeLogLine();
		if ($extra && $this->config['format_type'] === 'simple') {
			$line .= ' ' . json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		
		// ファイルロック対応版の書き込み
		$fp = @fopen($logfile, 'a');
		if ($fp) {
			// 排他ロック（他のプロセスがロック取得まで待機）
			if (flock($fp, LOCK_EX)) {
				fwrite($fp, $line . "\n");
				flock($fp, LOCK_UN);  // ロック解放
			}
			fclose($fp);
		} else {
			throw new Exception("DIR_CREATE_FAIL: Unable to open log file: $logfile");
		}
	}
	
	public function logError($message, $context = [])
	{
		$timestamp = date('Y-m-d H:i:s');
		$error_log = __DIR__ . '/../logs/error.log';
		
		// エラーメッセージ構築
		$log_entry = "[{$timestamp}] {$message}";
		if (!empty($context)) {
			$log_entry .= " | " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		
		// ファイルロック対応でエラーログに書き込み
		$fp = @fopen($error_log, 'a');
		if ($fp) {
			if (flock($fp, LOCK_EX)) {
				fwrite($fp, $log_entry . "\n");
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}
}
