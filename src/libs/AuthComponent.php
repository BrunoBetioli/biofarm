<?php

namespace libs;

use \Exception;
use libs\Loader;
use libs\Router;
use libs\Session;
use libs\SessionHandler;

class AuthComponent {

	const ALL = 'all';
    
	public $authenticate = array('Form');

	protected $_authenticateObjects = array();
    
	public $authorize = false;

	protected $_authorizeObjects = array();

	public $loginModel = null;
    
	public $flash = array(
		'element' => 'default',
		'key' => 'auth',
		'params' => array()
	);
    
	public $classAlert = array(
		'success' => 'alert alert-success',
		'warning' => 'alert alert-warning',
		'info' => 'alert alert-info',
		'danger' => 'alert alert-danger'
	);
    
	public static $sessionKey = 'Auth.User';
    
	protected static $_user = array();
    
	public $loginAction = array(
		'controller' => 'users',
		'action' => 'login',
		'plugin' => null
	);
    
	public $logoutAction = array(
		'controller' => 'users',
		'action' => 'logout',
		'plugin' => null
	);
    
	public $loginRedirect = null;
    
	public $logoutRedirect = null;
    
	public $authError = 'You are not authorized to access that location.';
    
	public $unauthorizedRedirect = true;
    
	public $allowedActions = array();
    
	public $request;
    
	public $requestFields;
    
	protected $SessionHandler;
    
	protected $_Controller;
    
	protected $_methods = array();
    
	public function initialize(Controller $controller) {
		$this->request = $controller->request;
		$this->requestFields = $controller->requestFields;
		$this->_methods = $controller->methods;
        if (!$this->SessionHandler) {
            $this->SessionHandler = new SessionHandler();
        }
        return $controller;
	}
    
	public function startup(Controller $controller) {
        $this->_Controller = $controller;
		$methods = array_flip(array_map('strtolower', $controller->methods));
		$action = strtolower($controller->request['action']);

		$isMissingAction = (
			!isset($methods[$action])
		);
        
		if ($isMissingAction) {
            return true;
		}

		if (!$this->_setDefaults()) {
			return false;
		}

		if ($this->_isAllowed($controller)) {
			return true;
		}

		if (!$this->_getUser()) {
			return $this->_unauthenticated($controller);
		}

		if ($this->_isLoginAction($controller) ||
            empty($this->authorize) ||
			$this->isAuthorized($this->user())
		) {
			return true;
		}

		return $this->_unauthorized($controller);
	}

/**
 * Checks whether current action is accessible without authentication.
 *
 * @param Controller $controller A reference to the instantiating controller object
 * @return boolean True if action is accessible without authentication else false
 */
	protected function _isAllowed(Controller $controller) {
		$action = strtolower($controller->request['action']);
		if (in_array($action, array_map('strtolower', $this->allowedActions))) {
			return true;
		}
		return false;
	}

/**
 * Handles unauthenticated access attempt. First the `unathenticated()` method
 * of the last authenticator in the chain will be called. The authenticator can
 * handle sending response or redirection as appropriate and return `true` to
 * indicate no furthur action is necessary. If authenticator returns null this
 * method redirects user to login action. If it's an ajax request and
 * $ajaxLogin is specified that element is rendered else a 403 http status code
 * is returned.
 *
 * @param Controller $controller A reference to the controller object.
 * @return boolean True if current action is login action else false.
 */
	protected function _unauthenticated(Controller $controller) {
		if ($this->_isLoginAction($controller)) {
			if (empty($controller->requestFields)) {
				if (!$this->SessionHandler->check('Auth.redirect') && isset($_SERVER['HTTP_REFERER'])) {
					$this->SessionHandler->write('Auth.redirect', $_SERVER['HTTP_REFERER']);
				}
			}
			return true;
		}

		if ($controller->request['request'] != 'ajax') {
            if (!$this->_isLogoutAction($controller)) {
                $this->flash['params']['class'] = $this->classAlert['danger'];
                $this->flash($this->authError);
                $this->SessionHandler->write('Auth.redirect', $controller->url);
            }
            $controller->redirect(null, $this->loginAction);
			return false;
		} else {
            $controller->View->layout = 'ajax';
            $controller->set('return', $this->authError);
            $controller->run();
            exit();
			return false;
		}
		$controller->redirect(null, 403);
		return false;
	}

/**
 * Normalizes $loginAction and checks if current request URL is same as login action.
 *
 * @param Controller $controller A reference to the controller object.
 * @return boolean True if current action is login action else false.
 */
	protected function _isLoginAction(Controller $controller) {
		$url = '';
		if (isset($controller->url)) {
			$url = $controller->url;
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		return $loginAction === $url;
	}

	protected function _isLogoutAction(Controller $controller) {
		$url = '';
		if (isset($controller->url)) {
			$url = $controller->url;
		}
		$url = Router::normalize($url);
		$logoutAction = Router::normalize($this->logoutAction);

		return $logoutAction === $url;
	}

/**
 * Handle unauthorized access attempt
 *
 * @param Controller $controller A reference to the controller object
 * @return boolean Returns false
 * @throws Exception
 * @see AuthComponent::$unauthorizedRedirect
 */
	protected function _unauthorized(Controller $controller) {
		if ($this->unauthorizedRedirect === false) {
			throw new Exception($this->authError);
		}

        $this->flash['params']['class'] = $this->classAlert['danger'];
		$this->flash($this->authError);
		if ($this->unauthorizedRedirect === true) {
			$default = '/';
			if (!empty($this->loginRedirect)) {
				$default = $this->loginRedirect;
			}
			$url = Router::url($default);
		} else {
			$url = $this->unauthorizedRedirect;
		}
		$controller->redirect(null, $url);
		return false;
	}

/**
 * Attempts to introspect the correct values for object properties.
 *
 * @return boolean True
 */
	protected function _setDefaults() {
		$defaults = array(
			'logoutRedirect' => $this->loginAction,
			'authError' => 'You are not authorized to access that location.'
		);
		foreach ($defaults as $key => $value) {
			if (!isset($this->{$key}) || $this->{$key} === true) {
				$this->{$key} = $value;
			}
		}
		return true;
	}

/**
 * Check if the provided user is authorized for the request.
 *
 * Uses the configured Authorization adapters to check whether or not a user is authorized.
 * Each adapter will be checked in sequence, if any of them return true, then the user will
 * be authorized for the request.
 *
 * @param array $user The user to check the authorization of. If empty the user in the session will be used.
 * @param array $request The request to authenticate for. If empty, the current request will be used.
 * @return boolean True if $user is authorized, otherwise false
 */
    public function isAuthorized($user = null, $request = null) {
        if (empty($user) && !$this->user()) {
            return false;
        }
        if (empty($user)) {
            $user = $this->user();
        }
        if (empty($request)) {
            $request = $this->request;
        }
        if (empty($this->_authorizeObjects)) {
            $this->constructAuthorize();
        }
        foreach ($this->_authorizeObjects as $authorizer) {
            if ($authorizer->authorize($user, $request) === true) {
                return true;
            }
        }
        return false;
    }

/**
 * Loads the authorization objects configured.
 *
 * @return mixed Either null when authorize is empty, or the loaded authorization objects.
 * @throws Exception
 */
	public function constructAuthorize() {
		if (empty($this->authorize)) {
			return;
		}
		$this->_authorizeObjects = array();
		$config = Hash::normalize((array)$this->authorize);
		$global = array();
		if (isset($config[AuthComponent::ALL])) {
			$global = $config[AuthComponent::ALL];
			unset($config[AuthComponent::ALL]);
		}
		foreach ($config as $class => $settings) {
			$className = $class . 'Authorize';
			$pathClassName = 'libs\Auth\\'.$className;
			if (!class_exists($pathClassName)) {
				throw new Exception('Authorization adapter '.$class.' was not found.');
			}
			if (!method_exists($pathClassName, 'authorize')) {
				throw new Exception('Authorization objects must implement an authorize() method.');
			}
			$settings['flash'] = $this->flash;
			$settings['classAlert'] = $this->classAlert;
			$settings = array_merge($global, (array)$settings);
			$this->_authorizeObjects[] = new $pathClassName($this->_Controller, $settings);
		}
		return $this->_authorizeObjects;
	}
    
	public function allow($action = null) {
		$args = func_get_args();
		if (empty($args) || $action === null) {
			$this->allowedActions = $this->_methods;
			return;
		}
		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		$this->allowedActions = array_merge($this->allowedActions, $args);
	}
    
	public function deny($action = null) {
		$args = func_get_args();
		if (empty($args) || $action === null) {
			$this->allowedActions = array();
			return;
		}
		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as $arg) {
			$i = array_search($arg, $this->allowedActions);
			if (is_int($i)) {
				unset($this->allowedActions[$i]);
			}
		}
		$this->allowedActions = array_values($this->allowedActions);
	}

/**
 * Log a user in.
 *
 * If a $user is provided that data will be stored as the logged in user. If `$user` is empty or not
 * specified, the request will be used to identify a user. If the identification was successful,
 * the user record is written to the session key specified in AuthComponent::$sessionKey. Logging in
 * will also change the session id in order to help mitigate session replays.
 *
 * @param array $user Either an array of user data, or null to identify a user using the current request.
 * @return boolean True on login success, false on failure
 */
	public function login($user = null) {
		$this->_setDefaults();

		if (empty($user)) {
			$user = $this->identify($this->requestFields);
		}
		if ($user) {
			$this->SessionHandler->renew();
			$this->SessionHandler->write(self::$sessionKey, $user);
		}
		return $this->loggedIn();
	}

/**
 * Log a user out.
 *
 * Returns the logout action to redirect to. Triggers the logout() method of
 * all the authenticate objects, so they can perform custom logout logic.
 * AuthComponent will remove the session data, so there is no need to do that
 * in an authentication object. Logging out will also renew the session id.
 * This helps mitigate issues with session replays.
 *
 * @return string AuthComponent::$logoutRedirect
 * @see AuthComponent::$logoutRedirect
 */
	public function logout() {
		$this->_setDefaults();
		$this->SessionHandler->delete(self::$sessionKey);
		$this->SessionHandler->delete('Auth.redirect');
		$this->SessionHandler->renew();
		return Router::url($this->logoutRedirect);
	}

/**
 * Get the current user.
 *
 * Will prefer the static user cache over sessions. The static user
 * cache is primarily used for stateless authentication. For stateful authentication,
 * cookies + sessions will be used.
 *
 * @param string $key field to retrieve. Leave null to get entire User record
 * @return mixed User record. or null if no user is logged in.
 */
	public static function user($key = null) {
		if (!empty(self::$_user)) {
			$user = self::$_user;
		} elseif (self::$sessionKey && Session::check(self::$sessionKey)) {
			$user = Session::read(self::$sessionKey);
		} else {
			return null;
		}
		if ($key === null) {
			return $user;
		}
		return Hash::get($user, $key);
	}

/**
 * Similar to AuthComponent::user() except if the session user cannot be found, connected authentication
 * objects will have their getUser() methods called. This lets stateless authentication methods function correctly.
 *
 * @return boolean true if a user can be found, false if one cannot.
 */
	protected function _getUser() {
		$user = $this->user();
		if ($user) {
			$this->SessionHandler->delete('Auth.redirect');
			return true;
		}

		return false;
	}

/**
 * Backwards compatible alias for AuthComponent::redirectUrl().
 *
 * @param string|array $url Optional URL to write as the login redirect URL.
 * @return string Redirect URL
 * @deprecated 2.3 Use AuthComponent::redirectUrl() instead
 */
	public function redirect($url = null) {
		return $this->redirectUrl($url);
	}

/**
 * Get the URL a user should be redirected to upon login.
 *
 * Pass a URL in to set the destination a user should be redirected to upon
 * logging in.
 *
 * If no parameter is passed, gets the authentication redirect URL. The URL
 * returned is as per following rules:
 *
 *  - Returns the normalized URL from session Auth.redirect value if it is
 *    present and for the same domain the current app is running on.
 *  - If there is no session value and there is a $loginRedirect, the $loginRedirect
 *    value is returned.
 *  - If there is no session and no $loginRedirect, / is returned.
 *
 * @param string|array $url Optional URL to write as the login redirect URL.
 * @return string Redirect URL
 */
	public function redirectUrl($url = null) {
		if ($url !== null) {
			$redir = $url;
			$this->SessionHandler->write('Auth.redirect', $redir);
		} elseif ($this->SessionHandler->check('Auth.redirect')) {
			$redir = $this->SessionHandler->read('Auth.redirect');
			$this->SessionHandler->delete('Auth.redirect');

			if (Router::normalize($redir) == Router::normalize($this->loginAction)) {
				$redir = $this->loginRedirect;
			}
		} elseif ($this->loginRedirect) {
			$redir = $this->loginRedirect;
		} else {
			$redir = '/';
		}
		if (is_array($redir)) {
			return Router::url($redir);
		}
		return $redir;
	}

    public function identify($requestFields) {
		if (empty($this->_authenticateObjects)) {
			$this->constructAuthenticate();
		}
		foreach ($this->_authenticateObjects as $auth) {
			$result = $auth->authenticate($requestFields);
			if (!empty($result) && is_array($result)) {
				return $result;
			}
		}
		return false;
    }

/**
 * Loads the configured authentication objects.
 *
 * @return mixed either null on empty authenticate value, or an array of loaded objects.
 * @throws Exception
 */
	public function constructAuthenticate() {
		if (empty($this->authenticate)) {
			return;
		}
		$this->_authenticateObjects = array();
		$config = Hash::normalize((array)$this->authenticate);
		$global = array();
		if (isset($config[AuthComponent::ALL])) {
			$global = $config[AuthComponent::ALL];
			unset($config[AuthComponent::ALL]);
		}
		foreach ($config as $class => $settings) {
			$className = $class . 'Authenticate';
			$pathClassName = 'libs\Auth\\'.$className;
			if (!class_exists($pathClassName)) {
				throw new Exception('Authentication adapter '.$class.' was not found.');
			}
			if (!method_exists($pathClassName, 'authenticate')) {
				throw new Exception('Authentication objects must implement an authenticate() method.');
			}
			$settings['flash'] = $this->flash;
			$settings['classAlert'] = $this->classAlert;
			$settings = array_merge($global, (array)$settings);
			$this->_authenticateObjects[] = new $pathClassName($settings);
		}
		return $this->_authenticateObjects;
	}

/**
 * Hash a password with the application's salt value (as defined with Configure::write('Security.salt');
 *
 * This method is intended as a convenience wrapper for Security::hash(). If you want to use
 * a hashing/encryption system not supported by that method, do not use this method.
 *
 * @param string $password Password to hash
 * @return string Hashed password
 * @deprecated Since 2.4. Use Security::hash() directly or a password hasher object.
 */
	public static function password($password) {
		return Security::hash($password, null, true);
	}

/**
 * Check whether or not the current user has data in the session, and is considered logged in.
 *
 * @return boolean true if the user is logged in, false otherwise
 */
	public function loggedIn() {
		return (bool) $this->user();
	}

/**
 * Set a flash message. Uses the Session component, and values from AuthComponent::$flash.
 *
 * @param string $message The message to set.
 * @return void
 */
	public function flash($message) {
		if ($message === false) {
			return;
		}
		$this->SessionHandler->setFlash($message, $this->flash['element'], $this->flash['params'], $this->flash['key']);
	}

}
