<?php
namespace libs\Auth;

use \Exception;
use libs\Hash;
use libs\Security;
use libs\SessionHandler;

abstract class BaseAuthenticate
{

    public $settings = array(
        'fields' => array(
            'username' => 'username',
            'password' => 'password'
        ),
        'userModel' => 'User',
        'conditions' => array(),
		'messages' => array(
			'error_user' => 'User not found or not authorized!',
			'error_password' => 'Invalid password!',
			'success' => 'Login successfully. Welcome {{name}}!'
		),
		'success' => array(
			'return' => true,
			'replace' => true,
			'field' => 'name',
		),
        'passwordHasher' => 'Simple',
		'flash' => array(
			'element' => 'alert',
			'key' => 'flash',
			'params' => array()
		),
		'classAlert' => array(
			'success' => 'alert alert-success',
			'warning' => 'alert alert-warning',
			'info' => 'alert alert-info',
			'danger' => 'alert alert-danger'
		)
    );

    protected $_passwordHasher;

    public function __construct($settings)
    {
        $this->SessionHandler = new SessionHandler();
        $this->settings = Hash::merge($this->settings, $settings);
        $pathModel = 'app\Model\\'.$this->settings['userModel'];
        $this->model = new $pathModel();
    }

/**
 * Find a user record using the standard options.
 *
 * The $username parameter can be a (string)username or an array containing
 * conditions for Model::find('first'). If the $password param is not provided
 * the password field will be present in returned array.
 *
 * Input passwords will be hashed even when a user doesn't exist. This
 * helps mitigate timing attacks that are attempting to find valid usernames.
 *
 * @param string|array $username The username/identifier, or an array of find conditions.
 * @param string $password The password, only used if $username param is string.
 * @return boolean|array Either false on failure, or an array of user data.
 */
    protected function _findUser($username, $password = null)
    {
        $userModel = $this->settings['userModel'];
        $fields = $this->settings['fields'];

        $query = "SELECT *
                    FROM {$this->model->table}
                    WHERE {$fields['username']} = ?";
        $queryParams = array(
            1 => $username
        );
        if (!empty($this->settings['conditions'])) {
            foreach($this->settings['conditions'] as $field => $condition) {
                $query .= " AND {$field} = ?";
                $queryParams[] = $condition;
            }
        }
        $result = $this->model->query($query, $queryParams, false);

        if (empty($result)) {
            $this->passwordHasher()->hash($password);
            $this->SessionHandler->setFlash($this->settings['messages']['error_user'], $this->settings['flash']['element'], array('class' => $this->settings['classAlert']['danger']), $this->settings['flash']['key']);
            return false;
        }

        $user = (array) $result;
        if ($password !== null) {
            if (!$this->passwordHasher()->check($password, $user[$fields['password']])) {
                $this->SessionHandler->setFlash($this->settings['messages']['error_password'], $this->settings['flash']['element'], array('class' => $this->settings['classAlert']['danger']), $this->settings['flash']['key']);
                return false;
            } elseif ($this->settings['success']['return'] === true) {
				$message = $this->settings['success']['replace'] === true ? str_replace('{{'.$this->settings['success']['field'].'}}', $user[$this->settings['success']['field']], $this->settings['messages']['success']) : $this->settings['messages']['success'];
				$this->SessionHandler->setFlash($message, $this->settings['flash']['element'], array('class' => $this->settings['classAlert']['success']), $this->settings['flash']['key']);
			}
            unset($user[$fields['password']]);
        }

        unset($result);
        return $user;
    }

/**
 * Return password hasher object
 *
 * @return AbstractPasswordHasher Password hasher instance
 * @throws Exception If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
    public function passwordHasher()
    {
        if ($this->_passwordHasher) {
            return $this->_passwordHasher;
        }

        $config = array();
        if (is_string($this->settings['passwordHasher'])) {
            $class = $this->settings['passwordHasher'];
        } else {
            $class = $this->settings['passwordHasher']['className'];
            $config = $this->settings['passwordHasher'];
            unset($config['className']);
        }
        $className = 'libs\Auth\\'. ucfirst($class) . 'PasswordHasher';
        if (!class_exists($className)) {
            throw new Exception("Password hasher class '{$class}' was not found.");
        }
        if (!is_subclass_of($className, 'libs\Auth\AbstractPasswordHasher')) {
            throw new Exception("Password hasher must extend AbstractPasswordHasher class.");
        }
        $this->_passwordHasher = new $className($config);
        return $this->_passwordHasher;
    }

/**
 * Hash the plain text password so that it matches the hashed/encrypted password
 * in the datasource.
 *
 * @param string $password The plain text password.
 * @return string The hashed form of the password.
 */
    protected function _password($password)
    {
        return Security::hash($password, null, true);
    }

/**
 * Authenticate a user based on the request information.
 *
 * @param array $request Request to get authentication information from.
 * @return mixed Either false on failure, or an array of user data on success.
 */
    abstract public function authenticate($request);

/**
 * Allows you to hook into AuthComponent::logout(),
 * and implement specialized logout behavior.
 *
 * All attached authentication objects will have this method
 * called when a user logs out.
 *
 * @param array $user The user about to be logged out.
 * @return void
 */
    public function logout($user)
    {
    }

/**
 * Get a user based on information in the request. Primarily used by stateless authentication
 * systems like basic and digest auth.
 *
 * @param array $request Request object.
 * @return mixed Either false or an array of user information
 */
    public function getUser($request)
    {
        return false;
    }

/**
 * Handle unauthenticated access attempt.
 *
 * @param array $request A request object.
 * @return mixed Either true to indicate the unauthenticated request has been
 *  dealt with and no more action is required by AuthComponent or void (default).
 */
    public function unauthenticated($request)
    {
    }

}