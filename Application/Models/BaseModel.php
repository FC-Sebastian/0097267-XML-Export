<?php

class BaseModel
{
    protected $tablename = false;
    protected $primary = false;
    public $data = [];


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

    public function getTableName()
    {
        if ($this->tablename !== false) {
            return $this->tablename;
        }
    }

    public function getColumnNameArray()
    {
        $query = "SHOW COLUMNS FROM " . $this->getTableName();
        $result = DbConnection::executeMySQLQuery($query);
        if (mysqli_num_rows($result) == 0) {
            return;
        }
        $dataArray = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $returnarray = [];
        foreach ($dataArray as $data) {
            $returnarray[] = $data["Field"];
        }
        return $returnarray;
    }

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

    public function delete($id = false)
    {
        $fncName = "get{$this->primary}";
        if ($id === false) {
            $id = $this->$fncName();
        }
        $query = "DELETE FROM " . $this->getTableName() . " WHERE {$this->primary}='$id'";
        DbConnection::executeMysqlQuery($query);
    }

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