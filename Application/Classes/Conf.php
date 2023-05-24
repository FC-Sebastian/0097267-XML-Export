<?php

/**
 * Config handler
 */
class Conf
{
    /**
     * Array of Config params
     *
     * @var null|array
     */
    private static $aConfarray = null;

    /**
     * Static function for accessing config params
     *
     * @param string $sKey
     * @return false|mixed
     */
    public static function getParam($sKey)
    {
        if (self::$aConfarray === null) {
            if (!file_exists(__DIR__ . "/../../config.php")) {
                exit("Keine config.php gefunden");
            }
            include __DIR__ . "/../../config.php";
            self::$aConfarray = $aConfigarray;
        }
        if (isset(self::$aConfarray[$sKey])) {
            return self::$aConfarray[$sKey];
        }
        return false;
    }
}