<?php

class FcExport extends BaseModel
{
    /**
     * Determines name of the db table for this model
     *
     * @var string
     */
    protected $sTablename = 'tbewertung_fcexport';

    /**
     * Creates db table if it doesnt exist
     *
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
     * Loads review via articleId, parentArticleId, reviewId
     *
     * @param string $sArticleId
     * @param string $sParentArticleId
     * @param string $sReviewId
     * @return false|void
     * @throws Exception
     */
    public function loadExport($sArticleId, $sParentArticleId, $sReviewId)
    {
        $sQuery = "SELECT * FROM " . $this->getTableName() . " WHERE kArtikel='$sArticleId' AND kVaterArtikel='$sParentArticleId' AND kBewertung='$sReviewId';";
        $oResult = DbConnection::executeMySQLQuery($sQuery);
        if (mysqli_num_rows($oResult) == 0) {
            return false;
        }
        $aDataArray = mysqli_fetch_assoc($oResult);

        foreach ($aDataArray as $key => $value) {
            $setString = "set" . $key;
            $this->$setString($value);
        }
    }

    /**
     * Inserts this object as entry into the db
     *
     * @return void
     * @throws Exception
     */
    public function saveExport()
    {
        $this->insert();
    }
}