<?php

/**
 * Terra Autoload
 *
 * Registers an autoloader for Terra Libraries.
 * Does not interfere with other registered autoloaders.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
function Terra_Autoload($class) {
    # Don't interfere with other autoloaders.
    if (0 !== strpos($class, 'Terra_')) {
        return false;
    }
    $path = dirname(__FILE__).'/'.str_replace('_', '/', substr($class, 6, strlen($class) - 6)).'.php';

    if (!file_exists($path)) {
        die($path);
        return false;
    }

    require_once $path;
}

spl_autoload_register('Terra_Autoload');

if (!defined('TERRA_APPDATA_PATH')) {
    if (file_exists(dirname(__FILE__).'../')) {
        define('TERRA_APPDATA_PATH', dirname(__FILE__).'/../Terra_AppData/');
    } else {
        throw new Terra_Exception("A valid Terra_AppData folder was not found!");
    }
}