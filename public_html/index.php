<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PATH TO CMS
const CMS_PATH = 'bc-cms';
const CONFIG_JSON_PATH = __DIR__ . '/../' . CMS_PATH . '/storage/bc.json';
const CONFIG_PHP_PATH = __DIR__ . '/../' . CMS_PATH . '/storage/bc.php';

const WHITELISTED_CONFIG_KEYS = [
    'BC_ACTIVE_THEME',
    'BC_ACTIVE_STYLE',
    'BC_INIT_THEME'
];

if (!empty($_SERVER['REQUEST_URI'])) {
    if (!file_exists(__DIR__ . '/../' . CMS_PATH . '/.env')) {
        copy(__DIR__ . '/../' . CMS_PATH . '/.env.example', __DIR__ . '/../' . CMS_PATH . '/.env');
    }
}
if (!version_compare(phpversion(), '8.2', '>')) {
    die("Current PHP version: " . phpversion() . "<br>You must upgrade PHP version 8.2 or later");
}
// Load configuration
if (file_exists(CONFIG_JSON_PATH)) {
    // Try to load JSON config first
    try {
        $configContent = file_get_contents(CONFIG_JSON_PATH);
        if ($configContent === false) {
            throw new \RuntimeException('Failed to read config file');
        }
        
        $config = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);
        
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                if(!in_array($key, WHITELISTED_CONFIG_KEYS)) continue;
                // Only define if not already defined and key is a valid constant name
                if (!defined($key) && is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                    if (is_scalar($value) || is_null($value)) {
                        define($key, $value);
                    } else {
                        // For non-scalar values, store as JSON string
                        define($key . '_JSON', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // Log error but don't stop execution
        error_log('Failed to load JSON config: ' . $e->getMessage());
        
        // Fallback to PHP config if available
        if (file_exists(CONFIG_PHP_PATH)) {
            require CONFIG_PHP_PATH;
        }
    }
} elseif (file_exists(CONFIG_PHP_PATH)) {
    // Fallback to PHP config if JSON doesn't exist
    require CONFIG_PHP_PATH;
}
if (file_exists(__DIR__ . '/../' . CMS_PATH . '/storage/pro.php')) {
    require __DIR__ . '/../' . CMS_PATH . '/storage/pro.php';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../' . CMS_PATH . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../' . CMS_PATH . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../' . CMS_PATH . '/bootstrap/app.php';

// set the public path to this directory
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());