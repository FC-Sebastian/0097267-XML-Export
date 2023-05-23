<?php

class FcExport extends BaseModel
{
    // determines name of the db table for this model
    protected $tablename = 'tbewertung_fcexport';

    /**
     * creates db table if it doesnt exist
     * @return void
     * @throws Exception
     */
    public function createTableIfNotExists()
    {
        if (mysqli_num_rows(DbConnection::executeMysqlQuery("SHOW TABLES LIKE '{$this->getTableName()}'")) === 0) {
            DbConnection::executeMysqlQuery("CREATE TABLE {$this->getTableName()} (`kArtikel` int(10) NOT NULL,`kVaterArtikel` int(10) NOT NULL, `kBewertung` int(10) NOT NULL, `dExportZeit` datetime, PRIMARY KEY (`kArtikel`,`kVaterArtikel`,`kBewertung`))");
        }
    }

    /**
     * loads review via articleId, parentArticleId, reviewId
     * @param $sArticleId
     * @param $sParentArticleId
     * @param $reviewId
     * @return false|void
     * @throws Exception
     */
    public function loadExport($sArticleId, $sParentArticleId, $reviewId)
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

    /**
     * inserts this object as entry into the db
     * @return void
     * @throws Exception
     */
    public function saveExport()
    {
        $this->insert();
    }
}