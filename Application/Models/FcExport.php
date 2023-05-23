<?php

class FcExport extends BaseModel
{
    protected $tablename = 'tbewertung_fcexport';


    public function createTableIfNotExists()
    {
        if (mysqli_num_rows(DbConnection::executeMysqlQuery("SHOW TABLES LIKE '{$this->getTableName()}'")) === 0) {
            DbConnection::executeMysqlQuery("CREATE TABLE {$this->getTableName()} (`kArtikel` int(10) NOT NULL,`kVaterArtikel` int(10) NOT NULL, `kBewertung` int(10) NOT NULL, `dExportDatum` date, PRIMARY KEY (`kArtikel`,`kVaterArtikel`,`kBewertung`))");
        }
    }

    public function load($sArticleId, $sParentArticleId, $reviewId)
    {
        $query = "SELECT * FROM " . $this->getTableName() . " WHERE kArtikel='$sArticleId' AND kVaterArtikel='$sParentArticleId' AND kBewertung='$reviewId';";
        $result = DbConnection::executeMySQLQuery($query);
        if (mysqli_num_rows($result) == 0) {
            return false;
        }
        $dataArray = mysqli_fetch_assoc($result);

        foreach ($dataArray as $key => $value) {
            $setString = "set" . $key;
            $this->$setString($value);
        }
    }

    public function save()
    {
        $this->insert();
    }
}