<?php

class Article extends BaseModel
{
    /**
     * Determines name of the db table for this model
     *
     * @var string
     */
    protected $sTablename = 'tartikel';

    /**
     * Primary index of the db table
     *
     * @var string
     */
    protected $sPrimary = 'kArtikel';
}