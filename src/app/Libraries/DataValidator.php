<?php

namespace app\Libraries;

use \DateTime;
 /**
 * Classe designada a validacao de formato de dados
 * @author Bruno Betioli
 * @version 1.0.2
 */
class DataValidator
{
    /**
    * Verifica se o dado passado esta vazio
    * @param mixed $mx_value
    * @return boolean
    */
    static function isEmpty($mx_value)
    {
        if (!(strlen($mx_value) > 0)) {
            return true;
        }
        return false;
    }

    /**
    * Verifica se o dado passado e um numero
    * @param mixed $mx_value;
    * @return boolean
    */
    static function isNumeric( $mx_value )
    {
        $mx_value = str_replace(',', '.', $mx_value);
        if (!(is_numeric($mx_value))) {
            return false;
        }
        return true;
    }

    /**
    * Verifica se o dado passado e um numero inteiro
    * @param mixed $mx_value;
    * @return boolean
    */
    static function isInteger($mx_value)
    {
        if (!DataValidator::isNumeric($mx_value)) {
            return false;
        }
        if (preg_match('/[[:punct:]&^-]/', $mx_value) > 0) {
            return false;
        }
        return true;
    }

    /**
    * Verifica se o dado passado é uma data ou hora válida
    * @param mixed $mx_value;
    * @return boolean
    */
    static function isDateOrHour($mx_value, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $mx_value);
        return $d && $d->format($format) == $mx_value;
    }

    static function isJSON($string)
    {
        // decode the JSON data
        $return['decode'] = json_decode($string);
        $return['error'] = null;

        // switch and check possible JSON errors
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $return['error'] = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $return['error'] = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $return['error'] = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $return['error'] = 'Syntax error, malformed JSON.';
                break;
            // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $return['error'] = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $return['error'] = 'One or more recursive references in the value to be encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $return['error'] = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $return['error'] = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $return['error'] = 'Unknown JSON error occured.';
                break;
        }

        // everything is OK
        return $return;
    }
}