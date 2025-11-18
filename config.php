<?php
/**
 * cdn-logger configuration file
 * Adjust the define() values below to customize your logging behavior.
 * Comments are in English for global users.
 * No need to edit any other files—just modify the settings here!
 */

// Logging output format: "simple" (compact), "combined" (Apache/NCSA style), or "json" (JSON object)
// Example: "simple"
define('LOG_FORMAT_TYPE', 'simple');

// Date and time format for log entries (PHP date() format)
// Leave empty ("") for Apache NCSA default: "d/M/Y:H:i:s O"
// Example: "Y/m/d H:i:s"
define('LOG_DATE_FORMAT', 'Y/m/d H:i:s');

// Whether to include HTTP status code in logs: true (yes), or false (leave as "-")
// Example: true
define('LOG_STATUS_ENABLED', true);

// Directory where log files will be saved
// Example: "logs", "../logs", "/var/www/logs"
define('LOG_DIRECTORY', 'logs');

// Log file rotation: "rotate" (daily split), or "single" (one cumulative file)
// Example: "rotate"
define('LOG_FILE_TYPE', 'rotate');

// Directory structure for rotated logs: "yeardir" (logs/YYYY/MM/), "monthdir" (logs/YYYYMM/), or "flat" (logs/ direct)
// Example: "yeardir"
define('LOG_DIRECTORY_MODE', 'yeardir');

// Language for error messages (see languages/ folder for available options)
// Example: "en", "ja", "zh", "fr", "es"
define('LOG_LANGUAGE', 'en');

// ===== Auto-conversion to array (no need to edit below) =====

return [
	'format_type'      => defined('LOG_FORMAT_TYPE')      ? LOG_FORMAT_TYPE      : 'simple',
	'date_format'      => defined('LOG_DATE_FORMAT')      ? LOG_DATE_FORMAT      : '',
	'status_enabled'   => defined('LOG_STATUS_ENABLED')   ? LOG_STATUS_ENABLED   : true,
	'directory'        => defined('LOG_DIRECTORY')        ? LOG_DIRECTORY        : 'logs',
	'file_type'        => defined('LOG_FILE_TYPE')        ? LOG_FILE_TYPE        : 'rotate',
	'directory_mode'   => defined('LOG_DIRECTORY_MODE')   ? LOG_DIRECTORY_MODE   : 'yeardir',
	'language'         => defined('LOG_LANGUAGE')         ? LOG_LANGUAGE         : 'en'
];
