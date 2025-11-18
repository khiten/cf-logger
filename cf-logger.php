<?php
require_once __DIR__ . '/src/Logger.php';
$config = require __DIR__ . '/config.php';
$lang   = $config['language'];

function get_message($key, $lang = 'en', $args = []) {
	static $cache = [];
	if (!isset($cache[$lang])) {
		$file = __DIR__ . "/languages/{$lang}.php";
		$cache[$lang] = file_exists($file) ? require($file) : [];
	}
	$msg = $cache[$lang][$key] ?? $key;
	return $args ? vsprintf($msg, $args) : $msg;
}

$logger = new Logger($config);

try {
	$logger->write();
} catch (Exception $e) {
	// エラーコンテキスト
	$context = [
		'error_code'    => $e->getCode(),
		'error_file'    => $e->getFile(),
		'error_line'    => $e->getLine(),
		'request_uri'   => $_SERVER['REQUEST_URI'] ?? 'N/A',
		'request_ip'    => $logger->getRealIp()
	];
	
	// エラーログに記載
	$logger->logError($e->getMessage(), $context);
	
	// ユーザー向け表示
	preg_match('/(\w+):\s?(.*)$/', $e->getMessage(), $m);
	$msg_id = $m[1] ?? 'ERROR';
	$detail = $m[2] ?? '';
	echo get_message($msg_id, $lang, [$detail]);
}
