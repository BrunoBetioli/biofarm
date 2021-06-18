<?php

namespace libs\Auth;

use libs\Auth\BaseAuthorize;

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize using the AclComponent,
 * If AclComponent is not already loaded it will be loaded using the Controller's ComponentCollection.
 */
class ActionsAuthorize extends BaseAuthorize {

/**
 * Authorize a user using the AclComponent.
 *
 * @param array $user The user to authorize
 * @param $request The request needing authorization.
 * @return boolean
 */
    public function authorize($user, $request) {
        $Acl = $this->_Collection->load('Acl');
        $user = array($this->settings['userModel'] => $user);
        return $Acl->check($user, $this->action($request));
    }

}