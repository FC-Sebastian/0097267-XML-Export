<?php

abstract class JSONController extends BaseController
{
    /**
     * echoes the json encoded return-value of getJsonData to be fetched by Ajax
     * @return void
     */
    public function render()
    {
        echo json_encode($this->getJsonData());
        exit();
    }

    abstract protected function getJsonData();
}