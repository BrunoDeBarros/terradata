<?php

/**
 * Terra Autoload
 *
 * Registers an autoloader for Terra Libraries.
 * Does not interfere with other registered autoloaders.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2
 * @package Terra
 * @throws Terra_Exception if no valid Terra Application Data folder can be found.
 * @copyright Copyright (c) 2008-2011 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Load the class with the name $class.
 * 
 * This function is to be called by PHP when autoloading a class, but can be called manually.
 * 
 * @param string $class
 * @return false if the class with the name $class does not exist.
 */
function Terra_Autoload($class) {
    # Don't interfere with other autoloaders.
    if (0 !== strpos($class, 'Terra_')) {
        return false;
    }
    $path = dirname(__FILE__).'/'.str_replace('_', '/', substr($class, 6, strlen($class) - 6)).'.php';

    if (!file_exists($path)) {
        return false;
    }

    require_once $path;
}

spl_autoload_register('Terra_Autoload');

if (!defined('TERRA_APPDATA_PATH')) {
    if (file_exists(dirname(__FILE__).'../')) {
        /**
         * The path to the Terra Application Data folder.
         */
        define('TERRA_APPDATA_PATH', dirname(__FILE__).'/../Terra_AppData/');
    } else {
        throw new Terra_Exception("A valid Terra_AppData folder was not found!");
    }
}