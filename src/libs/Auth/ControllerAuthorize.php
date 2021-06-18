<?php

namespace libs\Auth;

use libs\Auth\BaseAuthorize;
use libs\Controller;
use \Exception;

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize using a controller callback.
 * Your controller's isAuthorized() method should return a boolean to indicate whether or not the user is authorized.
 *
 * {{{
 *	public function isAuthorized($user) {
 *		if (!empty($this->request->params['admin'])) {
 *			return $user['role'] === 'admin';
 *		}
 *		return !empty($user);
 *	}
 * }}}
 *
 * the above is simple implementation that would only authorize users of the 'admin' role to access
 * admin routing.
 */
class ControllerAuthorize extends BaseAuthorize {

/**
 * Get/set the controller this authorize object will be working with. Also checks that isAuthorized is implemented.
 *
 * @param Controller $controller null to get, a controller to set.
 * @return mixed
 * @throws CakeException
 */
    public function controller(Controller $controller = null) {
        if ($controller) {
            if (!method_exists($controller, 'isAuthorized')) {
                throw new Exception('$controller does not implement an isAuthorized() method.');
            }
        }
        return parent::controller($controller);
    }

/**
 * Checks user authorization using a controller callback.
 *
 * @param array $user Active user data
 * @param $request
 * @return boolean
 */
    public function authorize($user, $request) {
        return (bool) $this->_Controller->isAuthorized($user);
    }

}