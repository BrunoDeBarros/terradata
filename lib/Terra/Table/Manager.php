<?php
/**
 * Terra Duo Table Manager
 *
 * Provides a factory for Tables and a function to discover tables.
 *
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Table_Manager {
    /**
     * Instantiate a Terra_Table.
     *
     * If Terra_Table_<b>$class</b> exists, a new instance of that class is returned.
     * Otherwise, a new instance of <b>$class</b> is returned.
     *
     * @example Table/Manager.php How to use the Table Manager
     * @param string $class
     * @param string $table
     * @param boolean $discover
     * @return Terra_Table_Interface
     */
    public static function Factory($class, $table = null, $discover = false) {

        if ($class == 'MySQL') {
            $class = 'Collection';
        }

        if (class_exists("Terra_Table_{$class}")) {
            $class = "Terra_Table_{$class}";
            $buffer = new $class($table);
        } elseif(class_exists($class)) {
            $buffer = new $class($table);
            if (!($buffer instanceof Terra_Table_Interface)) {
                throw new Terra_Table_Exception("$class does not implement Terra_Table_Interface.");
                return false;
            }
        } else {
            throw new Terra_Table_Exception("A class named Terra_Table_{$class} does not exist. Nor does a class named $class.");
            return false;
        }

        if ($discover) {
            $buffer->discoverTable();
        }

        return $buffer;

    }
}
