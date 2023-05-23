<?php

class Article extends BaseModel
{
    protected $tablename = 'tartikel';
    protected $primary = 'kArtikel';

    public function getData()
    {
        return $this->data;
    }
}