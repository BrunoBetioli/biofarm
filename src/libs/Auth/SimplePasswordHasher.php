<?php
namespace libs\Auth;

use libs\Auth\AbstractPasswordHasher;
use libs\Security;

class SimplePasswordHasher extends AbstractPasswordHasher
{

    protected $_config = array('hashType' => null);

    public function hash($password) {
        return Security::hash($password, $this->_config['hashType'], true);
    }

    public function check($password, $hashedPassword)
    {
        return $hashedPassword === $this->hash($password);
    }

}