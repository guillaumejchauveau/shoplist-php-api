<?php

/*
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/*
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */
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
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);
/*
 * File path to the webroot directory.
 *
 * To derive your webroot from your webserver change this to:
 *
 * `define('WWW_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DS) . DS);`
 */
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
