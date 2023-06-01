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
        exit;
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
        exit;
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
        fwrite($tmp, $this->getXmlHeader());

        foreach ($oReview->rowGenerator("nAktiv = '1'") as $aReviewRow) {
            $aReviewArticle = $oArticle->load($aReviewRow['kArtikel'], true);

            if ($aReviewArticle !== false) {
                $this->exportRow($tmp, $aReviewArticle, $aReviewRow, $blNew);
            }
        }

        fwrite($tmp, $this->getXmlFooter());
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
            foreach ($oArticle->rowGenerator("kVaterArtikel = '".$aReviewArticle['kArtikel']."'") as $aArticleRow) {
                if ($blOnlyNew === false || $this->isReviewNew($aArticleRow, $aReviewRow) === true) {
                    $aArticles[] = $aArticleRow;
                }
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
        $sXmlString  = "        <review>".PHP_EOL;
        $sXmlString .= "            <review_id>".$aReview['kBewertung']."</review_id>".PHP_EOL;
        $sXmlString .= "            <reviewer>".PHP_EOL;
        $sXmlString .= "                <name>".$aReview['cName']."</name>".PHP_EOL;
        $sXmlString .= "            </reviewer>".PHP_EOL;
        $sXmlString .= "            <review_timestamp>".$formattedTime."</review_timestamp>".PHP_EOL;
        $sXmlString .= "            <title>".$aReview['cTitel']."</title>".PHP_EOL;
        $sXmlString .= "            <content>".$aReview['cText']."</content>".PHP_EOL;
        $sXmlString .= "            <review_url type='group'>".$sReviewUrl."</review_url>".PHP_EOL;
        $sXmlString .= "            <ratings>".PHP_EOL;
        $sXmlString .= "                <overall min='1' max='5'>".$aReview['nSterne']."</overall>".PHP_EOL;
        $sXmlString .= "            </ratings>".PHP_EOL;
        $sXmlString .= "            <products>".PHP_EOL;
        $sXmlString .= $this->getProductsString($aArticles);
        $sXmlString .= "            </products>".PHP_EOL;
        $sXmlString .= "        </review>".PHP_EOL;

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
            $sProductString  = "                <product>".PHP_EOL;
            $sProductString .= "                    <product_ids>".PHP_EOL;
            $sProductString .= "                        <gtins>".PHP_EOL;
            $sProductString .= "                            <gtin>".$aArticle['cBarcode']."</gtin>".PHP_EOL;
            $sProductString .= "                        </gtins>".PHP_EOL;
            $sProductString .= "                        <skus>".PHP_EOL;
            $sProductString .= "                            <sku>".$aArticle['cArtNr']."</sku>".PHP_EOL;
            $sProductString .= "                        </skus>".PHP_EOL;
            $sProductString .= "                    </product_ids>".PHP_EOL;
            $sProductString .= "                    <product_name>".$aArticle['cName']."</product_name>".PHP_EOL;
            $sProductString .= "                    <product_url>".$this->sShopUrl.$aArticle['cSeo']."</product_url>".PHP_EOL;
            $sProductString .= "                </product>".PHP_EOL;
            $sReturn .= $sProductString;
        }
        return $sReturn;
    }

    /**
     * Returns the xml header
     *
     * @return string
     */
    protected function getXmlHeader()
    {
        $sHeader  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $sHeader .= '<feed>'.PHP_EOL;
        $sHeader .= '    <version>2.3</version>'.PHP_EOL;
        $sHeader .= '    <publisher>'.PHP_EOL;
        $sHeader .= '        <name>REAVET</name>'.PHP_EOL;
        $sHeader .= '    </publisher>'.PHP_EOL;
        $sHeader .= '    <reviews>'.PHP_EOL;
        return $sHeader;
    }

    /**
     * Returns the xml footer
     *
     * @return string
     */
    protected function getXmlFooter()
    {
        $sFooter  = '    </reviews>'.PHP_EOL;
        $sFooter .= '</feed>'.PHP_EOL;
        return $sFooter;
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
        header("Content-Disposition: attachment; filename=".$this->sFileNameBase."_".date('YmdHis').".xml");
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
