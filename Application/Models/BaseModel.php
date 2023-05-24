<?php

class BaseModel
{
    /**
     * Name of the db table for this model
     *
     * @var bool
     */
    protected $sTablename = false;

    /**
     * Primary index of the db table
     *
     * @var bool
     */
    protected $sPrimary = false;

    /**
     * Used for storing db data
     *
     * @var array
     */
    public $aData = [];

    /**
     * Getter und setter for column values
     *
     * @param string $sName
     * @param mixed $arguments
     * @return mixed|void
     */
    public function __call($sName, $arguments)
    {
        $arguments = implode($arguments);
        if (substr($sName, 0, 3) == "get") {
            $sName = str_replace("get", "", $sName);
            if (isset($this->aData[$sName])) {
                return $this->aData[$sName];
            }
            return;
        }
        if (substr($sName, 0, 3) == "set") {
            $sName = str_replace("set", "", $sName);
            $this->aData[$sName] = $arguments;
        }
    }

    /**
     * Returns name of db table
     *
     * @return string|void
     */
    public function getTableName()
    {
        if ($this->sTablename !== false) {
            return $this->sTablename;
        }
    }

    /**
     * Loads entry by primary index and assigns result to this object
     * if $getArray is true instead returns result as array
     *
     * @param string $sId
     * @param bool $blGetArray
     * @return array|false|void
     * @throws Exception
     */
    public function load($sId, $blGetArray = false)
    {
        $sQuery = "SELECT * FROM " . $this->getTableName() . " WHERE {$this->sPrimary}='$sId';";
        $oResult = DbConnection::executeMySQLQuery($sQuery);
        if (mysqli_num_rows($oResult) == 0) {
            return false;
        }
        $aDataArray = mysqli_fetch_assoc($oResult);
        if ($blGetArray === true) {
            return $aDataArray;
        }

        foreach ($aDataArray as $key => $value) {
            $setString = "set" . $key;
            $this->$setString($value);
        }
    }

    /**
     * Determines whether an entry with the same primary index exist
     * if one does its updated otherwise a new entry is inserted
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $sFncName = "get{$this->sPrimary}";
        if (isset($this->aData["{$this->sPrimary}"])) {
            $sQuery = "SELECT {$this->sPrimary} FROM " . $this->getTableName() . " WHERE {$this->sPrimary}=" . $this->$sFncName();
            $oResult = DbConnection::executeMySQLQuery($sQuery);
            $oResult = mysqli_fetch_assoc($oResult);
        }
        if (isset($oResult["{$this->sPrimary}"]) && $this->$sFncName() == $oResult["{$this->sPrimary}"]) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    /**
     * Deletes entry via the given id or via the id of this object
     *
     * @param string $sId
     * @return void
     * @throws Exception
     */
    public function delete($sId = false)
    {
        $sFncName = "get{$this->sPrimary}";
        if ($sId === false) {
            $sId = $this->$sFncName();
        }
        $sQuery = "DELETE FROM " . $this->getTableName() . " WHERE {$this->sPrimary}='$sId'";
        DbConnection::executeMysqlQuery($sQuery);
    }

    /**
     * Inserts this object as an entry into the db
     *
     * @return void
     * @throws Exception
     */
    protected function insert()
    {
        $sQuerybegin = "INSERT INTO " . $this->getTableName() . " (";
        $sQueryend = ") VALUES ( ";
        foreach ($this->aData as $key => $data) {
            $sQuerybegin .= $key . ",";
            $sQueryend .= "'" . $data . "',";
        }
        $sQuery = substr($sQuerybegin, 0, -1) . substr($sQueryend, 0, -1) . ")";
        DbConnection::executeMysqlQuery($sQuery);
    }

    /**
     * Updates an existing database entry with params from this object
     *
     * @return void
     * @throws Exception
     */
    protected function update()
    {
        $sFncName = "get{$this->sPrimary}";
        $sQuerybegin = "UPDATE " . $this->getTableName() . " ";
        $sQuerymid = "SET ";
        $sQueryend = "WHERE {$this->sPrimary} = " . $this->$sFncName();
        foreach ($this->aData as $key => $data) {
            $sQuerymid .= "" . $key . "='" . $data . "',";
        }
        $sQuery = $sQuerybegin . substr($sQuerymid, 0, -1) . $sQueryend;
        DbConnection::executeMySQLQuery($sQuery);
    }

    /**
     * A generator method which loads all entries from the db and yields each row as an array
     *
     * @param string $sWhere
     * @return Generator
     * @throws Exception
     */
    public function rowGenerator($sWhere = false)
    {
        $sQuery = "SELECT * FROM {$this->getTableName()}";
        if ($sWhere !== false) {
            $sQuery .= " WHERE {$sWhere}";
        }

        $oResult = DbConnection::executeMysqlQuery($sQuery);
        while ($aRow = mysqli_fetch_assoc($oResult)) {
            yield $aRow;
        }
    }
}