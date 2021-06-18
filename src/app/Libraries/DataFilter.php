<?php

namespace app\Libraries;

use \DateTime;

class DataFilter
{
    /**
    * Retira pontuacao da string
    * @param string $st_data
    * @return string
    */
    static function alphaNum($st_data)
    {
        $st_data = preg_replace("([[:punct:]]| )", '', $st_data);
        return $st_data;
    }

    /**
    * Retira caracteres nao numericos da string
    * @param string $st_data
    * @return string
    */
    static function numeric($st_data)
    {
        $st_data = preg_replace("([[:punct:]]|[[:alpha:]]| )", '', $st_data);
        return $st_data;
    }

    /**
    *
    * Retira tags HTML / XML e adiciona "\" antes
    * de aspas simples e aspas duplas
    * @param string $st_string
    */
    static function cleanString($st_string)
    {
        return trim(addslashes(strip_tags($st_string)));
    }

    /**
    *
    * Aplica cleanString a um array
    * @param array $st_array
    */
    static function cleanArray($st_array, $allowedFields = array())
    {
        $data_array = array();
        foreach ($st_array as $key => $value) {
            if (!empty($allowedFields)) {
                if (in_array($key, $allowedFields)) {
                    $data_array[$key] = is_array($value) ? self::cleanArray($value, $allowedFields) : self::cleanString($value);
                }
            } else {
                $data_array[$key] = is_array($value) ? self::cleanArray($value, $allowedFields) : self::cleanString($value);
            }
        }
        //return array_filter($data_array);
        return $data_array;
    }

    /**
    *
    * Aplica stripslashes a um array, objeto ou string
    * @param mixed $st_array
    */
    static function stripslashes_fields($st_array, $fields = array())
    {
        $is_object = false;

        if (is_object($st_array)) {
            $st_array = (array) $st_array;
            $is_object = true;
        }

        foreach ($st_array as $key => $value) {
            if (!empty($fields)) {
                if (in_array($key, $fields)) {
                    if (is_array($value)) {
                        $st_array[$key] = self::stripslashes_fields($value, $fields);
                    } elseif(is_object($value)) {
                        $value = (array) $value;
                        $st_array[$key] = (object) self::stripslashes_fields($value, $fields);
                    } else {
                        $st_array[$key] = stripslashes($value);
                    }
                }
            } else {
                if (is_array($value)) {
                    $st_array[$key] = self::stripslashes_fields($value);
                } elseif(is_object($value)) {
                    $value = (array) $value;
                    $st_array[$key] = (object) self::stripslashes_fields($value);
                } else {
                    $st_array[$key] = stripslashes($value);
                }
            }
        }

        return ($is_object ? (object) $st_array : $st_array);
    }

    /**
    *
    * Aplica format date a um array, objeto ou string
    * @param mixed $st_array
    */
    static function date_format_fields($st_array, $fields = array(), $to_format = 'Y-m-d H:i:s', $from_format = 'Y-m-d H:i:s')
    {
        $is_object = false;

        if (is_object($st_array)) {
            $st_array = (array) $st_array;
            $is_object = true;
        }

        foreach ($st_array as $key => $value) {
            if (is_numeric($key)) {
                $st_array[$key] = self::date_format_fields($value, $fields, $to_format, $from_format);
            } else {
                if (!empty($fields)) {
                    if (in_array($key, $fields)) {
                        if (is_array($value)) {
                            $st_array[$key] = self::date_format_fields($value, $fields, $to_format, $from_format);
                        } elseif(is_object($value)) {
                            $value = (array) $value;
                            $st_array[$key] = (object) self::date_format_fields($value, $fields, $to_format, $from_format);
                        } else {
                            $st_array[$key] = self::dateOrHour($value, $to_format, $from_format);
                        }
                    }
                } else {
                    if (is_array($value)) {
                        $st_array[$key] = self::date_format_fields($value, $fields, $to_format, $from_format);
                    } elseif(is_object($value)) {
                        $value = (array) $value;
                        $st_array[$key] = self::date_format_fields($value, $fields, $to_format, $from_format);
                    } else {
                        $st_array[$key] = self::dateOrHour($value, $to_format, $from_format);
                    }
                }
            }
        }

        return ($is_object ? (object) $st_array : $st_array);
    }

    /**
    * Converte a data de um formato para outro se o dado passado é uma hora válida
    * @param mixed $mx_value;
    */
    static function dateOrHour($mx_value, $to_format = 'Y-m-d H:i:s', $from_format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($from_format, $mx_value);
        return ($d instanceof DateTime ? $d->format($to_format) : $mx_value);
    }

    /**
    * Deixa a primeira letra maiúscula de todas as palavras, exceto as preposições
    * @param string $st_capitalized;
    */
    static function capitalizeString($st_string)
    {
        $st_capitalized = null;
        $st_splited = explode(' ', $st_string);
        $exclude = array('da', 'das', 'de', 'do', 'dos', 'e', 'em', 'na', 'nas', 'no', 'nos');

        foreach ($st_splited as $key => $value) {
            if (in_array(mb_convert_case($value, MB_CASE_LOWER), $exclude)) {
                $st_splited[$key] = mb_convert_case($value, MB_CASE_LOWER);
            } else {
                $st_splited[$key] = mb_convert_case($value, MB_CASE_TITLE);
            }
        }
        $st_capitalized = implode(' ', $st_splited);

        return trim($st_capitalized);
    }

    /**
    *
    * Aplica capitalizeString a um array
    * @param array $st_array
    */
    static function capitalizeArray($st_array, $allowedFields = array())
    {
        $data_array = array();
        foreach ($st_array as $key => $value) {
            if (!empty($allowedFields)) {
                if (in_array($key, $allowedFields)) {
                    $data_array[$key] = is_array($value) ? self::capitalizeArray($value, $allowedFields) : self::capitalizeString($value);
                }
            } else {
                $data_array[$key] = is_array($value) ? self::capitalizeArray($value, $allowedFields) : self::capitalizeString($value);
            }
        }
        return $data_array;
    }
}