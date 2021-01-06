<?php

use libs\Router;

/**
 * Connects the default, built-in routes, including prefix and plugin routes. The following routes are created
 * in the order below:
 *
 * For each of the Routing.prefixes the following routes are created. Routes containing `:plugin` are only
 * created when your application has one or more plugins.
 *
 * - `/:prefix/:plugin` a plugin shortcut route.
 * - `/:prefix/:plugin/:controller`
 * - `/:prefix/:plugin/:controller/:action/*`
 * - `/:prefix/:controller`
 * - `/:prefix/:controller/:action/*`
 *
 * If plugins are found in your application the following routes are created:
 *
 * - `/:plugin` a plugin shortcut route.
 * - `/:plugin/:controller`
 * - `/:plugin/:controller/:action/*`
 *
 * And lastly the following catch-all routes are connected.
 *
 * - `/:controller'
 * - `/:controller/:action/*'
 *
 * You can disable the connection of default routes by deleting the require inside APP/Config/routes.php.
 */
$prefixes = Router::prefixes();

foreach ($prefixes as $prefix) {
	$params = array('prefix' => $prefix, $prefix => true);
	$indexParams = $params + array('action' => 'index');
	Router::connect("/{$prefix}/:controller", $indexParams);
	Router::connect("/{$prefix}/:controller/:action/*", $params);
}
Router::connect('/:controller', array('action' => 'index'));
Router::connect('/:controller/:action/*');

unset($namedConfig, $params, $indexParams, $prefix, $prefixes, $shortParams, $match,
	$pluginPattern, $plugins, $key, $value);
