<?php
/**
 * Terra Table Driver for MySQL
 *
 * Handles Generation and execution of SQL queries in MySQL.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 10.05
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Table_Driver_MySQL {

    public $Connection;

    function __construct($host, $user, $pass, $db) {
        $this->Connection = mysql_connect($host, $user, $pass);
        if (!is_resource($this->Connection)) {
            throw new Terra_Table_DriverException('Could not connect to the database. MySQL error: '.mysql_error());
        }
        mysql_select_db($b, $this->Connection);
    }

    function query($sql) {
        $result = mysql_query($sql, $this->Connection);
        if (!$result) {
            throw new Terra_Table_DriverException("An error occured with MySQL: \n".mysql_error()." \n Trying to execute the following query: \n $sql");
        }
        if (is_bool($result)) {
            return $result;
        } else {
            return $this->getRowsInResult($result);
        }
    }

    public function getRowsInResult($result) {
        $rows = array();
        while($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

}
