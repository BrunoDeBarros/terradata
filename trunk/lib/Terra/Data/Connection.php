<?php

class Terra_Data_Connection {

    private static $connection;

    private function __construct() {
        # Forbid instanciating.
    }

    public static function getConnection() {
        return self::$connection;
    }

    public static function setConnection($connection) {
        self::$connection = $connection;
    }
}