<?php
namespace libs\Auth;

use libs\Auth\AbstractPasswordHasher;
use libs\Security;

class BlowfishPasswordHasher extends AbstractPasswordHasher
{

    public function hash($password)
    {
        return Security::hash($password, 'blowfish', false);
    }

    public function check($password, $hashedPassword)
    {
        return $hashedPassword === Security::hash($password, 'blowfish', $hashedPassword);
    }

}