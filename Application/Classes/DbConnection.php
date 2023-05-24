<?php

/**
 * DbConnection handler
 */
class DbConnection
{
    /**
     * Database connection object
     *
     * @var null|object
     */
    protected static $oConn = null;

    /**
     * Static function for creating a db connecntion
     *
     * @return false|mysqli|null
     * @throws Exception
     */
    protected static function getDbConnection()
    {
        if (self::$oConn === null) {
            mysqli_report(MYSQLI_REPORT_STRICT);
            self::$oConn = mysqli_connect(
                Conf::getParam("dbhost"),
                Conf::getParam("dbuser"),
                Conf::getParam("dbpass"),
                Conf::getParam("db")
            );
            if (self::$oConn->connect_error) {
                throw new Exception("Connection failed: " . mysqli_connect_error());
            }
        }
        return self::$oConn;
    }

    /**
     * Static function for executing sql queries
     *
     * @param string $sQuery
     * @return bool|mysqli_result
     * @throws Exception
     */
    public static function executeMysqlQuery($sQuery)
    {
        $oResult = mysqli_query(self::getDbConnection(), $sQuery);
        $sError = mysqli_error(self::getDbConnection());
        if (!empty($sError)) {
            throw new Exception("MYSQL-Error: " . $sError . " in Query: " . $sQuery);
        }
        //echo "<br>",$query,"<br>";
        return $oResult;
    }
}