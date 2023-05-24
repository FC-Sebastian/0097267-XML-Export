<?php

class XmlExport extends BaseController
{
    /**
     * View file name
     *
     * @var string
     */
    protected $sView = 'xmlExport';

    /**
     * Base url of shop where reviews are found
     *
     * @var string
     */
    protected $sShopUrl = 'https://reavet.de/';

    /**
     * Title of page
     *
     * @var string
     */
    protected $sTitle = 'XML-Export';

    /**
     * Header for xml file
     *
     * @var string
     */
    protected $sXmlHeader = '<?xml version="1.0" encoding="UTF-8"?><feed><version>2.3</version><publisher><name>REAVET</name></publisher><reviews>';

    /**
     * Footer for xml file
     *
     * @var string
     */
    protected $sXmlFooter = '</reviews></feed>';

    /**
     * Basename for generated xml file
     *
     * @var string
     */
    protected $sFileNameBase = 'GoogleReviews';

    /**
     * Exports all reviews in db as xml file for Google merchant center
     *
     * @return void
     * @throws Exception
     */
    public function exportAll()
    {
        $oFcExport = new FcExport();
        $oFcExport->createTableIfNotExists();
        $this->sFileNameBase .= '_ALL';

        $this->export();
    }

    /**
     * Exports only reviews that weren't previously exported
     *
     * @return void
     * @throws Exception
     */
    public function exportNew()
    {
        $oFcExport = new FcExport();
        $oFcExport->createTableIfNotExists();
        $this->sFileNameBase .= '_NEW';

        $this->export(true);
    }

    /**
     * Main export method
     * loops through active reviews and exports them
     * disregards reviews for which no article can be found
     * if $new is set to true only exports new reviews
     *
     * @param bool $blNew
     * @return void
     * @throws Exception
     */
    protected function export($blNew = false)
    {
        $tmp = tmpfile();
        $oArticle = new Article();
        $oReview = new Review();
        fwrite($tmp, $this->sXmlHeader);

        foreach ($oReview->rowGenerator("nAktiv = '1'") as $aReviewRow) {
            $aReviewArticle = $oArticle->load($aReviewRow['kArtikel'], true);

            if ($aReviewArticle !== false) {
                $this->exportRow($tmp, $aReviewArticle, $aReviewRow, $blNew);
            }
        }

        fwrite($tmp, $this->sXmlFooter);
        $this->downloadXml($tmp);
    }

    /**
     * Exports a single review
     *
     * @param object $oFileHandle
     * @param array $aReviewArticle
     * @param array $aReviewRow
     * @param bool $blOnlyNew
     * @return void
     * @throws Exception
     */
    protected function exportRow($oFileHandle, $aReviewArticle, $aReviewRow, $blOnlyNew = false)
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
            $this->exportToXML($oFileHandle, $aReviewRow, $aArticles, $this->sShopUrl.$aReviewArticle['cSeo']);
            $this->exportToDb($aReviewRow, $aArticles);
        }
    }

    /**
     * Uses article and review to check if the review is new
     *
     * @param array $aArticle
     * @param array $aReview
     * @return bool
     * @throws Exception
     */
    protected function isReviewNew($aArticle, $aReview)
    {
        $oFcExport = new FcExport();
        return $oFcExport->loadExport($aArticle['kArtikel'], $aArticle['kVaterArtikel'], $aReview['kBewertung']) === false;
    }

    /**
     * Builds xml string for a single review and writes it to the given file
     *
     * @param object $oFileHandle
     * @param array $aReview
     * @param array $aArticles
     * @param string $sReviewUrl
     * @return void
     */
    protected function exportToXML($oFileHandle, $aReview, $aArticles, $sReviewUrl)
    {
        $aReview = $this->xmlEncode($aReview);
        $formattedTime = date('c', strtotime($aReview['dDatum']));
        $sXmlString  = "<review>";
        $sXmlString .= "    <review_id>{$aReview['kBewertung']}</review_id>";
        $sXmlString .= "    <reviewer>";
        $sXmlString .= "        <name>{$aReview['cName']}</name>";
        $sXmlString .= "    </reviewer>";
        $sXmlString .= "    <review_timestamp>{$formattedTime}</review_timestamp>";
        $sXmlString .= "    <title>{$aReview['cTitel']}</title>";
        $sXmlString .= "    <content>{$aReview['cText']}</content>";
        $sXmlString .= "    <review_url type='group'>{$sReviewUrl}</review_url>";
        $sXmlString .= "    <ratings>";
        $sXmlString .= "        <overall min='1' max='5'>{$aReview['nSterne']}</overall>";
        $sXmlString .= "    </ratings>";
        $sXmlString .= "    <products>";
        $sXmlString .= $this->getProductsString($aArticles);
        $sXmlString .= "    </products>";
        $sXmlString .= "</review>";

        fwrite($oFileHandle, $sXmlString);
    }

    /**
     * Builds xml string from given articles and returns it
     *
     * @param array $aArticles
     * @return string
     */
    protected function getProductsString($aArticles)
    {
        $sReturn = '';
        foreach ($aArticles as $aArticle) {
            $aArticle = $this->xmlEncode($aArticle);
            $sProductString  = "        <product>";
            $sProductString .= "            <product_ids>";
            $sProductString .= "                <gtins>";
            $sProductString .= "                    <gtin>{$aArticle['cBarcode']}</gtin>";
            $sProductString .= "                </gtins>";
            $sProductString .= "                <skus>";
            $sProductString .= "                    <sku>{$aArticle['cArtNr']}</sku>";
            $sProductString .= "                </skus>";
            $sProductString .= "            </product_ids>";
            $sProductString .= "            <product_name>{$aArticle['cName']}</product_name>";
            $sProductString .= "            <product_url>".$this->sShopUrl.$aArticle['cSeo']."</product_url>";
            $sProductString .= "        </product>";
            $sReturn .= $sProductString;
        }
        return $sReturn;
    }

    /**
     * Checks whether a review is already in the database and inserts it if not
     *
     * @param array $aReview
     * @param array $aArticles
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
                $oFcExport->setdExportZeit(date('Y-m-d H:i:s', time()));

                $oFcExport->saveExport();
            }
        }
    }

    /**
     * Lets user download the exported xml file and deletes temp file
     *
     * @param object $oFileHandle
     * @return void
     */
    protected function downloadXml($oFileHandle)
    {
        $sPath = stream_get_meta_data($oFileHandle)['uri'];

        header('Content-Description: File Transfer');
        header('Content-Type: text/xml');
        header("Content-Disposition: attachment; filename={$this->sFileNameBase}_".date('YmdHis').".xml");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($sPath));

        ob_clean();
        flush();
        readfile($sPath);
        fclose($oFileHandle);
    }

    /**
     * Encodes all entries in a given array to be xml compatible and returns the converted array
     *
     * @param array $aArray
     * @return array
     */
    protected function xmlEncode($aArray)
    {
        foreach ($aArray as $key => $value) {
            $aArray[$key] = htmlentities($value, ENT_XML1);
        }
        return $aArray;
    }
}
