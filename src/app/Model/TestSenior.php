<?php

namespace app\Model;

use libs\Model;

class TestSenior extends Model
{
    protected $database = 'senior';

    public $table = 'e210est';

    public function test($fields = array())
    {
        $query = "
            SELECT (CASE WHEN coddep = 1 THEN 'BIO001' WHEN coddep = 2 THEN 'BIO02' WHEN coddep = 7 THEN 'BIO07' WHEN coddep = 14 THEN 'BIO14' ELSE coddep END) deposito, codpro, LTRIM(STR(qtdest, 15, 5))
            FROM e210est WHERE coddep IN (1, 2, 7, 14) AND qtdest <> 0";

        $test = $this->query($query);

        return $test;
    }
}