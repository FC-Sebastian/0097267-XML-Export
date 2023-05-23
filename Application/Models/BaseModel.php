<?php

class BaseModel
{
    // determines name of the db table for this model
    protected $tablename = false;

    //primary index of the db table
    protected $primary = false;

    //used for storing db data
    public $data = [];

    /**
     * getter und setter for column values
     * @param $name
     * @param $arguments
     * @return mixed|void
     */
    public function __call($name, $arguments)
    {
        $arguments = implode($arguments);
        if (substr($name, 0, 3) == "get") {
            $name = str_replace("get", "", $name);
            if (isset($this->data[$name])) {
                return $this->data[$name];
            }
            return;
        }
        if (substr($name, 0, 3) == "set") {
            $name = str_replace("set", "", $name);
            $this->data[$name] = $arguments;
        }
    }

    /**
     * returns name of db table
     * @return bool|mixed|void
     */
    public function getTableName()
    {
        if ($this->tablename !== false) {
            return $this->tablename;
        }
    }

    /**
     * loads entry by primary index and assigns result to this object
     * if $getArray is true instead returns result as array
     * @param $id
     * @param $getArray
     * @return array|false|void|null
     * @throws Exception
     */
    public function load($id, $getArray = false)
    {
        $query = "SELECT * FROM " . $this->getTableName() . " WHERE {$this->primary}='$id';";
        $result = DbConnection::executeMySQLQuery($query);
        if (mysqli_num_rows($result) == 0) {
            return false;
        }
        $dataArray = mysqli_fetch_assoc($result);
        if ($getArray === true) {
            return $dataArray;
        }

        foreach ($dataArray as $key => $value) {
            $setString = "set" . $key;
            $this->$setString($value);
        }
    }

    /**
     * determines whether an entry with the same primary index exist
     * if one does its updated otherwise a new entry is inserted
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $fncName = "get{$this->primary}";
        if (isset($this->data["{$this->primary}"])) {
            $query = "SELECT {$this->primary} FROM " . $this->getTableName() . " WHERE {$this->primary}=" . $this->$fncName();
            $result = DbConnection::executeMySQLQuery($query);
            $result = mysqli_fetch_assoc($result);
        }
        if (isset($result["{$this->primary}"]) && $this->$fncName() == $result["{$this->primary}"]) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    /**
     * deletes entry via the given id or via the id of this object
     * @param $id
     * @return void
     * @throws Exception
     */
    public function delete($id = false)
    {
        $fncName = "get{$this->primary}";
        if ($id === false) {
            $id = $this->$fncName();
        }
        $query = "DELETE FROM " . $this->getTableName() . " WHERE {$this->primary}='$id'";
        DbConnection::executeMysqlQuery($query);
    }

    /**
     * inserts this object as an entry into the db
     * @return void
     * @throws Exception
     */
    protected function insert()
    {
        $querybegin = "INSERT INTO " . $this->getTableName() . " (";
        $queryend = ") VALUES ( ";
        foreach ($this->data as $key => $data) {
            $querybegin .= $key . ",";
            $queryend .= "'" . $data . "',";
        }
        $query = substr($querybegin, 0, -1) . substr($queryend, 0, -1) . ")";
        DbConnection::executeMysqlQuery($query);
    }

    /**
     * updates an existing database entry with params from this object
     * @return void
     * @throws Exception
     */
    protected function update()
    {
        $fncName = "get{$this->primary}";
        $querybegin = "UPDATE " . $this->getTableName() . " ";
        $querymid = "SET ";
        $queryend = "WHERE {$this->primary} = " . $this->$fncName();
        foreach ($this->data as $key => $data) {
            $querymid .= "" . $key . "='" . $data . "',";
        }
        $query = $querybegin . substr($querymid, 0, -1) . $queryend;
        DbConnection::executeMySQLQuery($query);
    }

    /**
     * a generator method which loads all entries from the db and yields each row as an array
     * @param $where
     * @return Generator
     * @throws Exception
     */
    public function rowGenerator($where = false)
    {
        $query = "SELECT * FROM {$this->getTableName()}";
        if ($where !== false) {
            $query .= " WHERE {$where}";
        }

        $result = DbConnection::executeMysqlQuery($query);
        while ($row = mysqli_fetch_assoc($result)) {
            yield $row;
        }
    }
}