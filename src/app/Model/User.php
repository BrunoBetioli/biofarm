<?php

namespace app\Model;

use app\Libraries\DataFilter;
use libs\Model;
use libs\Router;
use libs\SessionHandler;
use libs\UploadHandler;
use libs\Auth\BlowfishPasswordHasher;

class User extends Model
{
    public $table = 'users';

    public $user = null;

    public $fields = array(
        'id', 'name', 'email', 'password', 'status', 'picture', 'created', 'modified'
    );

    public $fields_slashed = array(
        'name', 'email', 'status', 'picture'
    );

    public $datetime_fields = array(
        'created', 'modified'
    );

    public function emptyUser()
    {
        return (object) array(
            'id' => null,
            'name' => null,
            'email' => null,
            'password' => null,
            'confirm_password' => null,
            'status' => null,
            'picture' => null,
            'picture_base64' => null,
            'remove_picture' => null,
            'created' => null,
            'modified' => null
        );
    }

    public function users($options = array())
    {
        $default = array(
            'join' => null,
            'where' => ' WHERE 1 = 1',
            'offset' => false,
            'count' => 0,
            'orderBy' => 'u.name'
        );
        $options = $options + $default;

        $query = "
            SELECT DISTINCT u.id, u.name, u.email, u.status, u.created, u.modified
            FROM {$this->table} u
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

        $users = DataFilter::stripslashes_fields($this->query($query), $this->fields_slashed);
        if (defined('SITE_DATE_FORMAT') && defined('SITE_TIME_FORMAT')) {
            $users = DataFilter::date_format_fields($users, $this->datetime_fields, SITE_DATE_FORMAT.' '.SITE_TIME_FORMAT);
        }
        return $users;
    }

    public function countUsers($fields = array())
    {
        $default = array(
            'join' => null,
            'where' => ' WHERE 1 = 1'
        );
        $options = $fields + $default;

        return $this->query("SELECT COUNT(id) as count FROM {$this->table} u{$options['join']}{$options['where']}");
    }

    public function user($data)
    {
        $fieldsAllowed = array('id', 'email');

        $dataKeys = array_keys($data);
        $dataValues = array_values($data);

        $fieldSearch = array_shift($dataKeys);
        $fieldValue = array_shift($dataValues);

        if (in_array($fieldSearch, $fieldsAllowed)) {
            if ($fieldSearch == 'id') {
                $fieldValue = DataFilter::numeric($fieldValue);
            } elseif ($fieldSearch == 'email') {
                $fieldValue = filter_var($fieldValue, FILTER_VALIDATE_EMAIL);
            } else {
                $fieldValue = DataFilter::cleanString($fieldValue);
            }
        }

        $this->user = null;
        if (!empty($fieldValue)) {
            $sql = "SELECT * FROM {$this->table} WHERE {$fieldSearch} = ? ";
            $user = $this->query($sql, array($fieldValue), false);
            if (!empty($user)) {
                $this->user = DataFilter::stripslashes_fields($user, $this->fields_slashed);
                $this->user = DataFilter::date_format_fields($this->user, $this->datetime_fields, SITE_DATE_FORMAT.' '.SITE_TIME_FORMAT);
                unset($this->user->password);
            }
        }

        return $this->user;
    }

    public function saveUser($user)
    {
        $id = $user['id'];
        unset($user['id']);

        if (empty($user['password'])) {
            unset($user['password']);
        } else {
            $passwordHasher = new BlowfishPasswordHasher();
            $user['password'] = $passwordHasher->hash($user['password']);
        }

        if ($id > 0) {
            $user['modified'] = date('Y-m-d H:i:s');
            $save_user = $this->update_check_log($user, array('id =' => $id), $this->table);
        } else {
            $save_user = $this->insert($user, $this->table);
        }

        return $save_user;
    }

    public function deleteUser($id)
    {
        return $this->delete(array('id = ' => $id), $this->table);
    }
}