<?php

/**
 * Terra Data Connection
 *
 * Provides a container for a MySQL Connection, available anywhere.
 *
 * <code>
 * # Setting a connection
 * $connection = mysql_connect($host, $user, $pass);
 * mysql_select_db($connection);
 * Terra_Data_Connection::setConnection($connection);
 *
 * #And whenever you need to access that connection, just use:
 * Terra_Data_Connection::getConnection();
 * </code>
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2
 * @package Terra
 * @subpackage Data
 * @copyright Copyright (c) 2008-2011 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Data_Connection {

    protected static $connection;

    protected function __construct() {
        # Forbid instanciating.
    }

    protected function __clone() {
        # Forbid cloning.
    }
    
    public static function getConnection() {
        return self::$connection;
    }

    public static function setConnection($connection) {
        self::$connection = $connection;
    }

}