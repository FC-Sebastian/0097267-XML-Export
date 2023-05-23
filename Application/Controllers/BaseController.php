<?php

class BaseController
{
    protected $startsSession = false;
    protected $view = false;
    protected $error = false;
    protected $onloadFuntction = "";

    public function getOnload()
    {
        return $this->onloadFuntction;
    }

    public function setErrorMessage($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function __construct()
    {
        if ($this->startsSession === true) {
            session_start();
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getUrl($sitename = "")
    {
        return conf::getParam("url") . $sitename;
    }

    public function getRequestParameter($key, $default = false)
    {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        return $default;
    }

    public function getPictureUrl($filename)
    {
        if (!empty($filename)) {
            return conf::getParam("url") . "pics/" . $filename;
        }
        return "https://secure.gravatar.com/avatar/cb665e6a65789619c27932fc7b51f8dc?default=mm&size=200&rating=G";
    }

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

    protected function redirect($url)
    {
        header("location: " . $url);
        exit();
    }
}