<?php
namespace libs\Auth;

use libs\Auth\BaseAuthenticate;
use \Exception;

/**
 * Basic Authentication adapter for AuthComponent.
 *
 * Provides Basic HTTP authentication support for AuthComponent. Basic Auth will authenticate users
 * against the configured userModel and verify the username and passwords match. Clients using Basic Authentication
 * must support cookies. Since AuthComponent identifies users based on Session contents, clients using Basic
 * Auth must support cookies.
 *
 * ### Using Basic auth
 *
 * In your controller's components array, add auth + the required settings.
 * {{{
 *	public $components = array(
 *		'Auth' => array(
 *			'authenticate' => array('Basic')
 *		)
 *	);
 * }}}
 *
 * In your login function just call `$this->Auth->login()` without any checks for POST data. This
 * will send the authentication headers, and trigger the login dialog in the browser/client.
 */
class BasicAuthenticate extends BaseAuthenticate {

/**
 * Constructor, completes configuration for basic authentication.
 *
 * @param $collection The Component collection used on this request.
 * @param array $settings An array of settings.
 */
    public function __construct($settings) {
        parent::__construct($settings);
        if (empty($this->settings['realm'])) {
            $this->settings['realm'] = env('SERVER_NAME');
        }
    }

/**
 * Authenticate a user using HTTP auth. Will use the configured User model and attempt a
 * login using HTTP auth.
 *
 * @param $request The request to authenticate with.
 * @param $response The response to add headers to.
 * @return mixed Either false on failure, or an array of user data on success.
 */
    public function authenticate($request) {
        return $this->getUser($request);
    }

/**
 * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
 *
 * @param $request Request object.
 * @return mixed Either false or an array of user information
 */
    public function getUser($request) {
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $pass = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_PW'] : null;

        if (empty($username) || empty($pass)) {
            return false;
        }
        return $this->_findUser($username, $pass);
    }

/**
 * Handles an unauthenticated access attempt by sending appropriate login headers
 *
 * @param $request A request object.
 * @return void
 * @throws UnauthorizedException
 */
    public function unauthenticated($request) {
        $Exception = new Exception();
        $Exception->responseHeader(array($this->loginHeaders()));
        throw $Exception;
    }

/**
 * Generate the login headers
 *
 * @return string Headers for logging in.
 */
    public function loginHeaders() {
        return sprintf('WWW-Authenticate: Basic realm="%s"', $this->settings['realm']);
    }

}