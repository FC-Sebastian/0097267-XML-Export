<?php

class XmlExport extends BaseController
{
    protected $view = 'xmlExport';

    protected $shopUrl = 'https://reavet.de/';

    protected $title = 'XML-Export';

    protected $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?><feed><publisher><name>REAVET</name></publisher><reviews>';

    protected $xmlFooter = '</reviews></feed>';

    public function exportAll()
    {
        $tmp = tmpfile();
        $oArticle = new Article();
        $oReview = new Review();
        fwrite($tmp, $this->xmlHeader);

        foreach ($oReview->rowGenerator("nAktiv = '1'") as $aReviewRow) {
            $aReviewArticle = $oArticle->load($aReviewRow['kArtikel'], true);

            if ($aReviewArticle !== false) {
                $this->exportRow($tmp, $aReviewArticle, $aReviewRow);
            }
        }

        fwrite($tmp, $this->xmlFooter);
        $this->downloadXml($tmp);
    }

    public function exportNew()
    {
        $tmp = tmpfile();
        $oArticle = new Article();
        $oReview = new Review();
        fwrite($tmp, $this->xmlHeader);

        foreach ($oReview->rowGenerator("nAktiv = '1'") as $aReviewRow) {
            $aReviewArticle = $oArticle->load($aReviewRow['kArtikel'], true);

            if ($aReviewArticle !== false) {
                $this->exportRow($tmp, $aReviewArticle, $aReviewRow, true);
            }
        }

        fwrite($tmp, $this->xmlFooter);
        $this->downloadXml($tmp);    }

    protected function exportRow($fileHandle,$aReviewArticle, $aReviewRow, $blOnlyNew = false)
    {
        $oArticle = new Article();
        $oFcExport = new FcExport();
        $aArticles = [];

        if (intval($aReviewArticle['nIstVater']) !== 0) {
            foreach ($oArticle->rowGenerator("kVaterArtikel = '{$aReviewArticle['kArtikel']}'") as $aArticleRow){
                if ($blOnlyNew === false || $oFcExport->load($aArticleRow['kArtikel'], $aArticleRow['kVaterArtikel'], $aReviewRow['kBewertung']) === false)
                $aArticles[] = $aArticleRow;
            }
        } else {
            if ($blOnlyNew === false || $oFcExport->load($aReviewArticle['kArtikel'], $aReviewArticle['kVaterArtikel'], $aReviewRow['kBewertung']) === false) {
                $aArticles[] = $aReviewArticle;
            }
        }

        if (!empty($aArticles)) {
            $this->exportToXML($fileHandle, $aReviewRow, $aArticles, $this->shopUrl.$aReviewArticle['cSeo']);
            $this->exportToDb($aReviewRow, $aArticles);
        }
    }

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

    protected function exportToDb($aReview, $aArticles)
    {
        $oFcExport = new FcExport();
        $oFcExport->createTableIfNotExists();

        foreach ($aArticles as $aArticle) {
            if ($oFcExport->load($aArticle['kArtikel'], $aArticle['kVaterArtikel'], $aReview['kBewertung']) === false) {
                $oFcExport->setkArtikel($aArticle['kArtikel']);
                $oFcExport->setkVaterArtikel($aArticle['kVaterArtikel']);
                $oFcExport->setkBewertung($aReview['kBewertung']);
                $oFcExport->setdExportDatum(date('Y-m-d', time()));

                $oFcExport->save();
            }
        }
    }

    protected function downloadXml($fileHandle)
    {
        $sPath = stream_get_meta_data($fileHandle)['uri'];

        header('Content-Description: File Transfer');
        header('Content-Type: text/xml');
        header("Content-Disposition: attachment; filename=XML-Export.xml");
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

    protected function xmlEncode($aArray)
    {
        foreach ($aArray as $key => $value) {
            $aArray[$key] = htmlentities($value, ENT_XML1);
        }
        return $aArray;
    }
}
