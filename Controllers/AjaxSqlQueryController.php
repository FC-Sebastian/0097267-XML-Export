<?php

abstract class AjaxSqlQueryController extends BaseController
{
    public function render()
    {
        $this->executeSqlQuery();
        exit();
    }

    abstract protected function executeSqlQuery();
}