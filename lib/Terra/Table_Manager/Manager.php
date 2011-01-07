<?php
/**
 * Terra Duo Table Manager
 *
 * Provides a factory for Tables and a function to discover tables.
 *
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.2.21
 * @package TD
 * @subpackage Table
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TD_Table_Manager {
    /**
     * Instantiate a TD_Table.
     *
     * If TD_Table_<b>$class</b> exists, a new instance of that class is returned.
     * Otherwise, a new instance of <b>$class</b> is returned.
     *
     * @example Table/Manager.php How to use the Table Manager
     * @param string $class
     * @return TD_Table_Interface
     */
    public static function Factory($class) {
        if (class_exists("TD_Table_{$class}")) {
            $class = "TD_Table_{$class}";
            return new $class;
        } elseif(class_exists($class)) {
            return new $class;
        } else {
            throw new TD_Table_Exception("A class named TD_Table_{$class} does not exist. Nor does a class named $class.");
            return false;
        }
    }

    /**
     * Discover a table. This will look at an existing table and create a
     * table definition array for it. It will try to be as complete as possible.
     * In MySQL, for example, it will add validation rules based on the maximum
     * length of a field, and based on its type (it will use inArray if it's ENUM, etc.)
     * @param string $TableName
     * @return array
     */
    public static function discoverTable($table) {
        # @todo build a function that returns an array containing all fields and stuff.
    }
}
