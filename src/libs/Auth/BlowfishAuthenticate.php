<?php

namespace libs\Auth;

use libs\Auth\FormAuthenticate;

/**
 * An authentication adapter for AuthComponent. Provides the ability to authenticate using POST data using Blowfish
 * hashing. Can be used by configuring AuthComponent to use it via the AuthComponent::$authenticate setting.
 *
 * {{{
 * 	$this->Auth->authenticate = array(
 * 		'Blowfish' => array(
 * 			'scope' => array('User.active' => 1)
 * 		)
 * 	)
 * }}}
 *
 * When configuring BlowfishAuthenticate you can pass in settings to which fields, model and additional conditions
 * are used. See FormAuthenticate::$settings for more information.
 *
 * For initial password hashing/creation see Security::hash(). Other than how the password is initially hashed,
 * BlowfishAuthenticate works exactly the same way as FormAuthenticate.
 */
class BlowfishAuthenticate extends FormAuthenticate {

/**
 * Constructor. Sets default passwordHasher to Blowfish
 *
 * @param $collection The Component collection used on this request.
 * @param array $settings Array of settings to use.
 */
    public function __construct($settings)
    {
        $this->settings['passwordHasher'] = 'Blowfish';
        parent::__construct($settings);
    }

}