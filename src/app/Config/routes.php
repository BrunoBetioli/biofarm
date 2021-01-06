<?php

use libs\Router;

    Router::connect(
        '/',
        array(
            'controller' => 'dashboard',
            'action' => 'index'
        )
    );

	Router::connect(
        '/login/',
        array(
            'controller' => 'users',
            'action' => 'login'
        )
    );
	Router::connect(
        '/logout/',
        array(
            'controller' => 'users',
            'action' => 'logout'
        )
    );
	Router::connect(
        '/profile/',
        array(
            'controller' => 'users',
            'action' => 'profile'
        )
    );

    Router::connect('/:controller/:page', array('action' => 'index'));
    Router::connect('/:controller/:action/:id');

	require ROOT . DS . LIBS . DS . 'routes.php';
