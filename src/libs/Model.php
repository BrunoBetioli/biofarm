<?php

namespace libs;

use libs\Connection;
use libs\Application;
use libs\Router;
use \PDO;
use \PDOException;

class Model
{

    public $pdo;

    protected $database = 'default';

    protected $table = null;

    private static $instance = null;

    protected $currentController;

    protected $currentAction;

    protected $request = array();

    function __construct ()
    {
        $this->pdo = Connection::getInstance($this->database);
    }

    public function setTableName($table)
    {
        if (!empty($table)) {
            $this->table = $table;
        }
    }

    private function buildInsert($arrayData)
    {

        $sql = null;
        $fields = null;
        $values = null;

        foreach ($arrayData as $key => $value):
            $fields .= $key . ', ';
            $values .= '?, ';
        endforeach;

        $fields = (substr($fields, -2) == ', ') ? trim(substr($fields, 0, (strlen($fields) - 2))) : $fields ;

        $values = (substr($values, -2) == ', ') ? trim(substr($values, 0, (strlen($values) - 2))) : $values ;

        $sql .= "INSERT INTO {$this->table} ({$fields}) VALUES({$values})";

        return trim($sql);
    }

    private function buildUpdate($arrayData, $arrayCondition)
    {

        $sql = null;
        $fields = null;
        $valCondicao = null;

        foreach ($arrayData as $key => $value):
            $fields .= $key . '= ?, ';
        endforeach;

        foreach ($arrayCondition as $key => $value):
            $valCondicao .= $key . (is_array($value) ? '('.str_repeat('?,', count($value) - 1) . '?)' : '? AND ');
        endforeach;

        $fields = (substr($fields, -2) == ', ') ? trim(substr($fields, 0, (strlen($fields) - 2))) : $fields ;

        $valCondicao = (substr($valCondicao, -4) == 'AND ') ? trim(substr($valCondicao, 0, (strlen($valCondicao) - 4))) : $valCondicao ;

        $sql .= "UPDATE {$this->table} SET {$fields} WHERE {$valCondicao}";

        return trim($sql);
    }

    private function buildDelete($arrayCondition)
    {

        $sql = null;
        $fields = null;

        foreach ($arrayCondition as $key => $value):
            $fields .= $key . (is_array($value) ? '('.str_repeat('?,', count($value) - 1) . '?) AND ' : '? AND ');
        endforeach;

        $fields = (substr($fields, -4) == 'AND ') ? trim(substr($fields, 0, (strlen($fields) - 4))) : $fields ;

        $sql .= "DELETE FROM {$this->table} WHERE {$fields}";

        return trim($sql);
    }

    public function insert($arrayData, $table)
    {
        try {
            $this->setTableName($table);

            $sql = $this->buildInsert($arrayData);

            $stm = $this->pdo->prepare($sql);

            $counter = 1;
            foreach ($arrayData as $value):
                $stm->bindValue($counter, $value);
                $counter++;
            endforeach;

            return $stm->execute() === true ? $this->pdo->lastInsertId() : false;

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function update($arrayData, $arrayCondition, $table)
    {
        try {
            $this->setTableName($table);

            $sql = $this->buildUpdate($arrayData, $arrayCondition);

            $stm = $this->pdo->prepare($sql);

            $counter = 1;
            foreach ($arrayData as $value):
                $stm->bindValue($counter, $value);
                $counter++;
            endforeach;

            foreach ($arrayCondition as $value):
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        $stm->bindValue($counter, $subValue);
                        $counter++;
                    }
                } else {
                    $stm->bindValue($counter, $value);
                    $counter++;
                }
            endforeach;

            return $stm->execute();

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function update_check_log($arrayData, $arrayCondition, $table, $primary_field = 'id')
    {
        try {
            $table_log = $table.'_log';
            $checkTableLog = $this->query('DESCRIBE '.$table_log);

            $sql = "SELECT * FROM {$table} WHERE ";
            $fields = null;

            foreach ($arrayCondition as $key => $value):
                $fields .= $key . (is_array($value) ? '('.str_repeat('?,', count($value) - 1) . '?) AND ' : '? AND ');
            endforeach;

            $fields = (substr($fields, -4) == 'AND ') ? trim(substr($fields, 0, (strlen($fields) - 4))) : $fields ;

            $sql .= $fields;

            $sql = trim($sql);

            $original_records = $this->query($sql, $arrayCondition);
            $records = array();
            if (!empty($original_records)) {
                if (is_array($original_records)) {
                    foreach ($original_records as $record) {
                        if (is_array($record)) {
                            $records[] = $record;
                        } elseif (is_object($record)) {
                            $records[] = get_object_vars($record);
                        }
                    }
                } elseif (is_object($original_records)) {
                    $records[] = get_object_vars($original_records);
                }
            }

            $records_updated = 0;
            if (!empty($records)) {
                foreach ($records as $record) {
                    $returnMsg = null;
                    $doUpdate = false;
                    foreach ($arrayData as $key => $value) {
                        if (isset($this->datetime_fields) && in_array($key, $this->datetime_fields)) {
                            continue;
                        }

                        if (isset($this->time_fields) && in_array($key, $this->time_fields)) {
                            $value = DataFilter::dateOrHour($value, 'H:i:s', 'H:i');
                        }

                        if ($record[$key] != $value) {
                            $returnMsg = 'Há alteração';
                            $doUpdate = true;
                            break;
                        } else {
                            $returnMsg = 'Sem alteração';
                        }
                    }
                    if ($doUpdate) {
                        if (isset($record[$primary_field]) || isset($record['meta_'.$primary_field])) {
                            if (isset($record[$primary_field])) {
                                $record['original_'.$primary_field] = $record[$primary_field];
                                unset($record[$primary_field]);
                            } else {
                                $record['original_'.$primary_field] = $record['meta_'.$primary_field];
                                unset($record['meta_'.$primary_field]);
                            }

                            if (!empty($checkTableLog)) {
                                $insert = $this->insert($record, $table_log);
                            }

                            $records_updated++;
                        }
                    }
                }

                if ($records_updated > 0) {
                    $update = $this->update($arrayData, $arrayCondition, $table);
                }
            }

            return $records_updated > 0 ? $update : $records_updated;

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function delete($arrayCondition, $table)
    {
        try {
            $this->setTableName($table);

            $sql = $this->buildDelete($arrayCondition);

            $stm = $this->pdo->prepare($sql);

            $counter = 1;
            foreach ($arrayCondition as $value):
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        $stm->bindValue($counter, $subValue);
                        $counter++;
                    }
                } else {
                    $stm->bindValue($counter, $value);
                    $counter++;
                }
            endforeach;

            return $stm->execute();

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function query($sql, $arrayParams = null, $fetchAll = true)
    {
        try {

            $stm = $this->pdo->prepare($sql);

            if (!empty($arrayParams)):
                $counter = 1;
                foreach ($arrayParams as $value):
                    if (is_array($value)) {
                        foreach($value as $subValue) {
                            $stm->bindValue($counter, $subValue);
                            $counter++;
                        }
                    } else {
                        $stm->bindValue($counter, $value);
                        $counter++;
                    }
                endforeach;
            endif;

            $stm->execute();

            if ($fetchAll):
                $result = $stm->fetchAll(PDO::FETCH_OBJ);
            else:
                $result = $stm->fetch(PDO::FETCH_OBJ);
            endif;

            return $result;

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function errorInfo()
    {
        try {
            return $this->pdo->errorCode();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}