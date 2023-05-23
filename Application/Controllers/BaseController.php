<?php

class BaseController
{
    //determins whether controller starts session or not
    protected $startsSession = false;

    //the name of the controllers view file
    protected $view = false;

    //error message
    protected $error = false;

    /**
     * sets error message for error box
     * @param $error
     * @return void
     */
    public function setErrorMessage($error)
    {
        $this->error = $error;
    }

    /**
     * gets error message for error box
     * @return bool|mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * starts session if necessary
     */
    public function __construct()
    {
        if ($this->startsSession === true) {
            session_start();
        }
    }

    /**
     * returns title of controller's view
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * appends given string to base URL found in config and returns result
     * @param $sitename
     * @return string
     */
    public function getUrl($sitename = "")
    {
        return conf::getParam("url") . $sitename;
    }

    /**
     * loads views and renders page
     * @return void
     * @throws Exception
     */
    public function render()
    {
        if ($this->view === false) {
            throw new Exception("NO VIEW FOUND");
        }

        $viewPath = __DIR__ . "/../../Views/" . $this->view . ".php";
        if (!file_exists($viewPath)) {
            throw new Exception("VIEW FILE NOT FOUND");
        }

        $controller = $this;

        ob_start();
        try {
            $url = $controller->getUrl("css/bootstrap.css");
            $title = $controller->getTitle();
            include $viewPath;
        } catch (Throwable $exc) {
            $controller->setErrorMessage($exc->getMessage());
        }
        $output = ob_get_contents();
        ob_end_clean();

        include __DIR__ . "/../../Views/header.php";
        echo $output;
        include __DIR__ . "/../../Views/footer.php";
    }
}