<?php

/**
 * DbConnection handler
 */
class DbConnection
{
    protected static $conn = null;

    /**
     * static function for creating a db connecntion
     * @return false|mysqli|null
     * @throws Exception
     */
    protected static function getDbConnection()
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_STRICT);
            self::$conn = mysqli_connect(
                Conf::getParam("dbhost"),
                Conf::getParam("dbuser"),
                Conf::getParam("dbpass"),
                Conf::getParam("db")
            );
            if (self::$conn->connect_error) {
                throw new Exception("Connection failed: " . mysqli_connect_error());
            }
        }
        return self::$conn;
    }

    /**
     * static function for executing sql queries
     * @param $query
     * @return bool|mysqli_result
     * @throws Exception
     */
    public static function executeMysqlQuery($query)
    {
        $result = mysqli_query(self::getDbConnection(), $query);
        $error = mysqli_error(self::getDbConnection());
        if (!empty($error)) {
            throw new Exception("MYSQL-Error: " . $error . " in Query: " . $query);
        }
        //echo "<br>",$query,"<br>";
        return $result;
    }
}