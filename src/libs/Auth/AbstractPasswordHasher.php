<?php
namespace libs\Auth;

abstract class AbstractPasswordHasher
{

    protected $_config = array();

    public function __construct($config = array())
    {
        $this->config($config);
    }

    public function config($config = null)
    {
        if (is_array($config)) {
            $this->_config = array_merge($this->_config, $config);
        }
        return $this->_config;
    }

    abstract public function hash($password);

    abstract public function check($password, $hashedPassword);

}