<?php

/*
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/*
 * The full path to the directory which holds "src", WITHOUT a trailing DS.
 */
define('ROOT', dirname(__DIR__));
/*
 * The actual directory name for the application directory. Normally
 * named 'src'.
 */
define('APP_DIR', 'src' . DS . 'ShopList');
/*
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);
/*
 * Path to REST's directory.
 */
define('REST', ROOT . DS . 'src' . DS . 'Rest' . DS);
/*
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);
/**
 * Webroot directory relative to the root directory.
 */
define('WWW', 'webroot');
/*
 * File path to the webroot directory.
 */
define('WWW_ROOT', ROOT . DS . WWW . DS);
