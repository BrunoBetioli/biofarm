<?php
namespace libs\Auth;

use libs\Auth\BaseAuthenticate;

class FormAuthenticate extends BaseAuthenticate
{

/**
 * Checks the fields to ensure they are supplied.
 *
 * @param array $request The request that contains login information.
 * @param string $model The model used for login verification.
 * @param array $fields The fields to be checked.
 * @return boolean False if the fields have not been supplied. True if they exist.
 */
    protected function _checkFields($fields)
    {
        $fieldsModel = $this->model->fields;
        foreach($fields as $field) {
            if (!in_array($field, $fieldsModel)) {
                return false;
            }
        }
        return true;
    }

/**
 * Authenticates the identity contained in a request. Will use the `settings.userModel`, and `settings.fields`
 * to find POST data that is used to find a matching record in the `settings.userModel`. Will return false if
 * there is no post data, either username or password is missing, or if the scope conditions have not been met.
 *
 * @param array $request The request that contains login information.
 * @return mixed False on login failure. An array of User data on success.
 */
    public function authenticate($request)
    {
        $model = $this->settings['userModel'];

        $fields = $this->settings['fields'];
        if (!$this->_checkFields($fields)) {
            return false;
        }
        return $this->_findUser(
            $request[$fields['username']],
            $request[$fields['password']]
        );
    }

}