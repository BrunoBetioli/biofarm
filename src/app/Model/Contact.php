<?php

namespace app\Model;

use libs\Model;
use libs\DataFilter;
use libs\DataValidator;
use libs\Router;
use libs\SessionHandler;
use vendors\DocumentChecker\DocumentChecker;

class Contact extends Model
{
    public $table = 'contacts';

    public $contact = null;

    public $fields = array(
        'id', 'name', 'email', 'phone', 'document', 'birth_date', 'picture', 'address', 'house_number', 'address_complement', 'zip_code', 'district', 'city', 'state', 'country', 'created', 'modified'
    );

    public $fields_slashed = array(
        'name', 'email', 'phone', 'document', 'picture', 'address', 'address_complement', 'district', 'city', 'state', 'country'
    );

    public $fields_capitalized = array(
        'name', 'address', 'address_complement', 'district', 'city', 'country'
    );

    public $datetime_fields = array(
        'created', 'modified'
    );

    public $date_fields = array(
        'birth_date'
    );

    public function emptyContact()
    {
        return (object) array(
            'id' => null,
            'name' => null,
            'email' => null,
            'phone' => null,
            'document' => null,
            'birth_date' => null,
            'picture' => null,
            'picture_base64' => null,
            'remove_picture' => null,
            'address' => null,
            'house_number' => null,
            'address_complement' => null,
            'zip_code' => null,
            'district' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'created' => null,
            'modified' => null
        );
    }

    public function contacts($fields = array())
    {
        $default = array(
            'join' => null,
            'where' => ' WHERE 1 = 1',
            'offset' => false,
            'count' => 0,
            'orderBy' =>  'c.name'
        );
        $options = $fields + $default;

        $query = "
            SELECT DISTINCT c.*
            FROM {$this->table} c
            {$options['join']}
            {$options['where']}
            ORDER BY {$options['orderBy']}";
        if ($options['offset'] !== false || $options['count'] > 0) {
            $query .= " LIMIT ";
            if ($options['offset'] !== false) {
                $query .= $options['offset'].", ";
            }
            if ($options['count'] > 0) {
                $query .= $options['count'];
            }
        }

		$contacts = DataFilter::stripslashes_fields($this->query($query), $this->fields_slashed);
        if (defined('SITE_DATE_FORMAT') && defined('SITE_TIME_FORMAT')) {
            $contacts = DataFilter::date_format_fields($contacts, $this->datetime_fields, SITE_DATE_FORMAT.' '.SITE_TIME_FORMAT);
            $contacts = DataFilter::date_format_fields($contacts, $this->date_fields, SITE_DATE_FORMAT, 'Y-m-d');
        }
        return $contacts;
    }

    public function countContacts($fields = array())
    {
        $default = array(
            'join' => null,
            'where' => ' WHERE 1 = 1'
        );
        $options = $fields + $default;

        return $this->query("SELECT COUNT(c.id) as count FROM {$this->table} c{$options['join']}{$options['where']}");
    }

    public function contact($data, $fields = '*')
    {
        $fieldsAllowed = array('id', 'document', 'email');

        $dataKeys = array_keys($data);
        $dataValues = array_values($data);

        $fieldSearch = array_shift($dataKeys);
        $fieldValue = array_shift($dataValues);

        if (in_array($fieldSearch, $fieldsAllowed)) {
            if ($fieldSearch == 'id' || $fieldSearch == 'document') {
                $fieldValue = DataFilter::numeric($fieldValue);
            } elseif ($fieldSearch == 'email') {
                $fieldValue = filter_var($fieldValue, FILTER_VALIDATE_EMAIL);
            } else {
                $fieldValue = DataFilter::cleanString($fieldValue);
            }
        }

        $fields = !is_array($fields) ? $fields : implode(', ', $fields);

        if (!empty($fieldValue)) {
            $sql = "SELECT {$fields} FROM {$this->table} WHERE {$fieldSearch} = ? ";
            $contact = $this->query($sql, array($fieldValue), false);
            if (!empty($contact)) {
                $this->contact = DataFilter::stripslashes_fields($contact, $this->fields_slashed);
                $this->contact = DataFilter::date_format_fields($this->contact, $this->datetime_fields, SITE_DATE_FORMAT.' '.SITE_TIME_FORMAT);
                $this->contact = DataFilter::date_format_fields($this->contact, $this->date_fields, SITE_DATE_FORMAT, 'Y-m-d');
            }
        }

        return $this->contact;
    }

    public function saveContact($contact)
    {
        $id = $contact['id'];
        unset($contact['id']);

        if ($id > 0) {
            $contact['modified'] = date('Y-m-d H:i:s');
            $save_contact = $this->update_check_log($contact, array('id =' => $id), $this->table);
        } else {
            $save_contact = $this->insert($contact, $this->table);
        }

        return $save_contact;
    }

    public function deleteContact($id)
    {
        return $this->delete(array('id = ' => $id), $this->table);
    }
}