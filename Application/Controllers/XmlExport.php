<?php

class XmlExport extends BaseController
{
    //view file name
    protected $view = 'xmlExport';

    // base url of shop where reviews are found
    protected $shopUrl = 'https://reavet.de/';

    //title of page
    protected $title = 'XML-Export';

    //header for xml file
    protected $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?><feed><publisher><name>REAVET</name></publisher><reviews>';

    //footer for xml file
    protected $xmlFooter = '</reviews></feed>';

    //basename for generated xml file
    protected $fileNameBase = 'XML-Export';

    /**
     * exports all reviews in db as xml file for Google merchant center
     * @return void
     * @throws Exception
     */
    public function exportAll()
    {
        $oFcExport = new FcExport();
        $oFcExport->createTableIfNotExists();
        $this->fileNameBase .= '_ALL';

        $this->export();
    }

    /**
     * exports only reviews that weren't previously exported
     * @return void
     * @throws Exception
     */
    public function exportNew()
    {
        $oFcExport = new FcExport();
        $oFcExport->createTableIfNotExists();
        $this->fileNameBase .= '_NEW';

        $this->export(true);
    }

    /**
     * main export method
     * loops through active reviews and exports them
     * disregards reviews for which no article can be found
     * if $new is set to true only exports new reviews
     * @param $new
     * @return void
     * @throws Exception
     */
    protected function export($new = false)
    {
        $tmp = tmpfile();
        $oArticle = new Article();
        $oReview = new Review();
        fwrite($tmp, $this->xmlHeader);

        foreach ($oReview->rowGenerator("nAktiv = '1'") as $aReviewRow) {
            $aReviewArticle = $oArticle->load($aReviewRow['kArtikel'], true);

            if ($aReviewArticle !== false) {
                $this->exportRow($tmp, $aReviewArticle, $aReviewRow, $new);
            }
        }

        fwrite($tmp, $this->xmlFooter);
        $this->downloadXml($tmp);
    }

    /**
     * exports a single review
     * @param $fileHandle
     * @param $aReviewArticle
     * @param $aReviewRow
     * @param $blOnlyNew
     * @return void
     * @throws Exception
     */
    protected function exportRow($fileHandle,$aReviewArticle, $aReviewRow, $blOnlyNew = false)
    {
        $oArticle = new Article();
        $aArticles = [];

        if (intval($aReviewArticle['nIstVater']) !== 0) {
            foreach ($oArticle->rowGenerator("kVaterArtikel = '{$aReviewArticle['kArtikel']}'") as $aArticleRow){
                if ($blOnlyNew === false || $this->isReviewNew($aArticleRow, $aReviewRow) === true)
                $aArticles[] = $aArticleRow;
            }
        } else {
            if ($blOnlyNew === false || $this->isReviewNew($aReviewArticle, $aReviewRow) === true) {
                $aArticles[] = $aReviewArticle;
            }
        }

        if (!empty($aArticles)) {
            $this->exportToXML($fileHandle, $aReviewRow, $aArticles, $this->shopUrl.$aReviewArticle['cSeo']);
            $this->exportToDb($aReviewRow, $aArticles);
        }
    }

    /**
     * uses article and review to check if the review is new
     * @param $aArticle
     * @param $aReview
     * @return bool
     * @throws Exception
     */
    protected function isReviewNew($aArticle, $aReview)
    {
        $oFcExport = new FcExport();
        return $oFcExport->loadExport($aArticle['kArtikel'], $aArticle['kVaterArtikel'], $aReview['kBewertung']) === false;
    }

    /**
     * builds xml string for a single review and writes it to the given file
     * @param $fileHandle
     * @param $aReview
     * @param $aArticles
     * @param $sReviewUrl
     * @return void
     */
    protected function exportToXML($fileHandle, $aReview, $aArticles, $sReviewUrl)
    {
        $aReview = $this->xmlEncode($aReview);
        $sXmlString = "<review><review_id>{$aReview['kBewertung']}</review_id><reviewer><name>{$aReview['cName']}</name></reviewer><review_timestamp>{$aReview['dDatum']}</review_timestamp><title>{$aReview['cTitel']}</title><content>{$aReview['cText']}</content><review_url type='group'>{$sReviewUrl}</review_url><ratings><overall min='1' max='5'>{$aReview['nSterne']}</overall></ratings><products>";

        foreach ($aArticles as $aArticle) {
            $aArticle = $this->xmlEncode($aArticle);
            $productString = "<product><product_ids><gtins><gtin>{$aArticle['cBarcode']}</gtin></gtins><skus><sku>{$aArticle['cArtNr']}</sku></skus></product_ids><product_name>{$aArticle['cName']}</product_name><product_url>".$this->shopUrl.$aArticle['cSeo']."</product_url></product>";
            $sXmlString .= $productString;
        }
        $sXmlString .= "</products></review>";

        fwrite($fileHandle, $sXmlString);
    }

    /**
     * checks whether a review is already in the database and inserts it if not
     * @param $aReview
     * @param $aArticles
     * @return void
     * @throws Exception
     */
    protected function exportToDb($aReview, $aArticles)
    {
        $oFcExport = new FcExport();

        foreach ($aArticles as $aArticle) {
            if ($this->isReviewNew($aArticle,$aReview) === true) {
                $oFcExport->setkArtikel($aArticle['kArtikel']);
                $oFcExport->setkVaterArtikel($aArticle['kVaterArtikel']);
                $oFcExport->setkBewertung($aReview['kBewertung']);
                $oFcExport->setdExportZeit(date('Y-m-d', time()));

                $oFcExport->saveExport();
            }
        }
    }

    /**
     * lets user download the exported xml file and deletes temp file
     * @param $fileHandle
     * @return void
     */
    protected function downloadXml($fileHandle)
    {
        $sPath = stream_get_meta_data($fileHandle)['uri'];

        header('Content-Description: File Transfer');
        header('Content-Type: text/xml');
        header("Content-Disposition: attachment; filename={$this->fileNameBase}_".date('YmdHis').".xml");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($sPath));

        ob_clean();
        flush();
        readfile($sPath);
        fclose($fileHandle);
    }

    /**
     * encodes all entries in a given array to be xml compatible and returns the converted array
     * @param $aArray
     * @return mixed
     */
    protected function xmlEncode($aArray)
    {
        foreach ($aArray as $key => $value) {
            $aArray[$key] = htmlentities($value, ENT_XML1);
        }
        return $aArray;
    }
}
