<?php
/**
 * Terra Duo Events Handler
 *
 * Provides an API to add callbacks to, and trigger callbacks of events.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Events {

    protected static $Callbacks = array();

    public static function getCallbacks($EventName) {
        if (isset(self::$Callbacks[$EventName])) {
            return self::$Callbacks[$EventName];
        } else {
            return array();
        }
    }

    public static function addCallback($EventName, $StringOrArray) {
        if (is_string($StringOrArray) or (isset($StringOrArray[0]) and isset ($StringOrArray[1]) and is_object($StringOrArray[0]) and is_string($StringOrArray[1]))) {
            if (isset (self::$Callbacks[$EventName])) {
                self::$Callbacks[$EventName][] = $StringOrArray;
            } else {
                self::$Callbacks[$EventName] = array($StringOrArray);
            }
        } else {
            throw new Exception('Callback provided must be a string containing a function name or an array containing the object and a method string.');
        }
    }

    public static function trigger($event, &$args) {
        if (isset(self::$Callbacks[$event])) {
            foreach (self::$Callbacks[$event] as $callback) {
                if (is_string($callback)) {
                    $callback(&$args);
                } else {
                    $callback[0]->$callback[1](&$args);
                }
            }
        }
    }

    public static function resetEvent($event) {
        self::$Callbacks[$event] = array();
    }

    public static function QuickEvent($callback, &$args) {
        if (is_string($callback)) {
            return $callback(&$args);
        } else {
            return $callback[0]->$callback[1](&$args);
        }
    }

    public static function CallBack($callback, $args) {
        return call_user_func_array($callback, $args);
    }
}