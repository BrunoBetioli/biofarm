<?php

namespace app\Model;

use libs\Model;

class Test extends Model
{
    protected $database = 'biodados';

    public $table = 'ger_unidades';

    public function unidades($fields = array())
    {
        $default = array(
            'join' => null,
            'where' => ' WHERE 1 = 1',
            'offset' => false,
            'count' => 0,
            'orderBy' =>  'u.desuni'
        );
        $options = $fields + $default;

        $query = "
            SELECT DISTINCT u.*
            FROM {$this->table} u
            {$options['join']}
            {$options['where']}
            ORDER BY {$options['orderBy']}";
        if ($options['offset'] !== false || $options['count'] > 0) {
            $query .= ' LIMIT ';
            if ($options['offset'] !== false) {
                $query .= $options['offset'].', ';
            }
            if ($options['count'] > 0) {
                $query .= $options['count'];
            }
        }

		$unidades = $this->query($query);

        return $unidades;
    }
}