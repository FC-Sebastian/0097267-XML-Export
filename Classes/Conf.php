<?php

class Conf
{
    private static $confarray = null;

    public static function getParam($key)
    {
        if (self::$confarray === null) {
            if (!file_exists(__DIR__ . "/../config.php")) {
                exit("Keine config.php gefunden");
            }
            include __DIR__ . "/../config.php";
            self::$confarray = $configarray;
        }
        if (isset(self::$confarray[$key])) {
            return self::$confarray[$key];
        }
        return false;
    }
}