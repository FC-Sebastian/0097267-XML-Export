<?php

class Review extends BaseModel
{
    /**
     * Db table name
     *
     * @var string
     */
    protected $sTablename = 'tbewertung';

    /**
     * Primary index of db table
     *
     * @var string
     */
    protected $sPrimary = 'kBewertung';

    /**
     * Determines which fields to select
     *
     * @var string
     */
    protected $sSelectFields = "kBewertung, kArtikel, cName, cTitel, cText, nSterne, dDatum";
}