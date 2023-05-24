<?php

class BaseController
{
    /**
     * Name of view File
     *
     * @var bool
     */
    protected $sView = false;

    /**
     * Error message
     *
     * @var bool
     */
    protected $sError = false;

    /**
     * Sets error message for error box
     *
     * @param string $sError
     * @return void
     */
    public function setErrorMessage($sError)
    {
        $this->sError = $sError;
    }

    /**
     * Gets error message for error box
     *
     * @return bool|string
     */
    public function getError()
    {
        return $this->sError;
    }

    /**
     * Returns title of controller's view
     *
     * @return bool|string
     */
    public function getTitle()
    {
        return $this->sTitle;
    }

    /**
     * Appends given string to base URL found in config and returns result
     *
     * @param string $sSitename
     * @return string
     */
    public function getUrl($sSitename = "")
    {
        return conf::getParam("url") . $sSitename;
    }

    /**
     * Loads views and renders page
     *
     * @return void
     * @throws Exception
     */
    public function render()
    {
        if ($this->sView === false) {
            throw new Exception("NO VIEW FOUND");
        }

        $sViewPath = __DIR__ . "/../../Views/" . $this->sView . ".php";
        if (!file_exists($sViewPath)) {
            throw new Exception("VIEW FILE NOT FOUND");
        }

        $oController = $this;

        ob_start();
        try {
            $sUrl = $oController->getUrl("css/bootstrap.css");
            $sTitle = $oController->getTitle();
            include $sViewPath;
        } catch (Throwable $exc) {
            $oController->setErrorMessage($exc->getMessage());
        }
        $sOutput = ob_get_contents();
        ob_end_clean();

        include __DIR__ . "/../../Views/header.php";
        echo $sOutput;
        include __DIR__ . "/../../Views/footer.php";
    }
}