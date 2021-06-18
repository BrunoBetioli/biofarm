<?php

 namespace libs;

/**
 * Parses the request URL into controller, action, and parameters. Uses the connected routes
 * to match the incoming URL string to parameters that will allow the request to be dispatched. Also
 * handles converting parameter lists into URL strings, using the connected routes. Routing allows you to decouple
 * the way the world interacts with your application (URLs) and the implementation (controllers and actions).
 *
 * ### Connecting routes
 *
 * Connecting routes is done using Router::connect(). When parsing incoming requests or reverse matching
 * parameters, routes are enumerated in the order they were connected. You can modify the order of connected
 * routes using Router::promote(). For more information on routes and how to connect them see Router::connect().
 *
 * ### Named parameters
 *
 * Named parameters allow you to embed key:value pairs into path segments. This allows you create hash
 * structures using URLs. You can define how named parameters work in your application using Router::connectNamed()
 */
class Router {

/**
 * Array of routes connected with Router::connect()
 *
 * @var array
 */
    public static $routes = array();

/**
 * Have routes been loaded
 *
 * @var bool
 */
    public static $initialized = false;

/**
 * Contains the base string that will be applied to all generated URLs
 * For example `https://example.com`
 *
 * @var string
 */
    protected static $_fullBaseUrl;

    protected static $_baseFolder;

/**
 * List of action prefixes used in connected routes.
 * Includes admin prefix
 *
 * @var array
 */
    protected static $_prefixes = array();

/**
 * Directive for Router to parse out file extensions for mapping to Content-types.
 *
 * @var bool
 */
    protected static $_parseExtensions = false;

/**
 * List of valid extensions to parse from a URL. If null, any extension is allowed.
 *
 * @var array
 */
    protected static $_validExtensions = array();

/**
 * Regular expression for action names
 *
 * @var string
 */
    const ACTION = 'index|show|add|create|edit|update|remove|del|delete|view|item';

/**
 * Regular expression for years
 *
 * @var string
 */
    const YEAR = '[12][0-9]{3}';

/**
 * Regular expression for months
 *
 * @var string
 */
    const MONTH = '0[1-9]|1[012]';

/**
 * Regular expression for days
 *
 * @var string
 */
    const DAY = '0[1-9]|[12][0-9]|3[01]';

/**
 * Regular expression for auto increment IDs
 *
 * @var string
 */
    const ID = '[0-9]+';

/**
 * Regular expression for UUIDs
 *
 * @var string
 */
    const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

/**
 * Named expressions
 *
 * @var array
 */
    /* protected static $_namedExpressions = array(
        'Action' => Router::ACTION,
        'Year' => Router::YEAR,
        'Month' => Router::MONTH,
        'Day' => Router::DAY,
        'ID' => Router::ID,
        'UUID' => Router::UUID
    ); */

/**
 * Stores all information necessary to decide what named arguments are parsed under what conditions.
 *
 * @var string
 */
    protected static $_namedConfig = array(
        'default' => array('page', 'fields', 'order', 'limit', 'recursive', 'sort', 'direction', 'step', 'type'),
        'greedyNamed' => true,
        'separator' => ':',
        'rules' => false,
    );

    protected static $_namedExpressions = array(
        'rules' => array(
            'controller' => '(\w+)',
            'action' => '(\w+)',
            'id' => '(\d{1,})',
            'page' => '(\d{1,})',
            'type' => '(\w+)',
            'slug' => '([\w-]+)',
            'fields' => '[\w-]+',
            'order' => '[\w-]+',
            'limit' => '(\d{1,})',
            'sort' => '([\w-]+)'
        ),
        'separator' => ':',
    );

/**
 * The route matching the URL of the current request
 *
 * @var array
 */
    protected static $_currentRoute = array();

/**
 * Default HTTP request method => controller action map.
 *
 * @var array
 */
    protected static $_resourceMap = array(
        array('action' => 'index', 'method' => 'GET', 'id' => false),
        array('action' => 'view', 'method' => 'GET', 'id' => true),
        array('action' => 'add', 'method' => 'POST', 'id' => false),
        array('action' => 'edit', 'method' => 'PUT', 'id' => true),
        array('action' => 'delete', 'method' => 'DELETE', 'id' => true),
        array('action' => 'edit', 'method' => 'POST', 'id' => true)
    );

/**
 * List of resource-mapped controllers
 *
 * @var array
 */
    protected static $_resourceMapped = array();

/**
 * Maintains the request object stack for the current request.
 * This will contain more than one request object when requestAction is used.
 *
 * @var array
 */
    protected static $_requests = array();

/**
 * Initial state is populated the first time reload() is called which is at the bottom
 * of this file. This is a cheat as get_class_vars() returns the value of static vars even if they
 * have changed.
 *
 * @var array
 */
    protected static $_initialState = array();

/**
 * Gets the named route elements for use in app/Config/routes.php
 *
 * @return array Named route elements
 * @see Router::$_namedExpressions
 */
    public static function getNamedExpressions() {
        return self::$_namedExpressions;
    }

/**
 * Connects a new Route in the router.
 *
 * Routes are a way of connecting request URLs to objects in your application. At their core routes
 * are a set of regular expressions that are used to match requests to destinations.
 *
 * Examples:
 *
 * `Router::connect('/:controller/:action/*');`
 *
 * The first token ':controller' will be used as a controller name while the second is used as the action name.
 * the '/*' syntax makes this route greedy in that it will match requests like `/posts/index` as well as requests
 * like `/posts/edit/1/foo/bar`.
 *
 * `Router::connect('/home-page', array('controller' => 'pages', 'action' => 'display', 'home'));`
 *
 * The above shows the use of route parameter defaults, and providing routing parameters for a static route.
 *
 * ```
 * Router::connect(
 *   '/:lang/:controller/:action/:id',
 *   array(),
 *   array('id' => '[0-9]+', 'lang' => '[a-z]{3}')
 * );
 * ```
 *
 * Shows connecting a route with custom route parameters as well as providing patterns for those parameters.
 * Patterns for routing parameters do not need capturing groups, as one will be added for each route params.
 *
 * $defaults is merged with the results of parsing the request URL to form the final routing destination and its
 * parameters. This destination is expressed as an associative array by Router. See the output of {@link parse()}.
 *
 * $options offers four 'special' keys. `pass`, `named`, `persist` and `routeClass`
 * have special meaning in the $options array.
 *
 * - `pass` is used to define which of the routed parameters should be shifted into the pass array. Adding a
 *   parameter to pass will remove it from the regular route array. Ex. `'pass' => array('slug')`
 * - `persist` is used to define which route parameters should be automatically included when generating
 *   new URLs. You can override persistent parameters by redefining them in a URL or remove them by
 *   setting the parameter to `false`. Ex. `'persist' => array('lang')`
 * - `routeClass` is used to extend and change how individual routes parse requests and handle reverse routing,
 *   via a custom routing class. Ex. `'routeClass' => 'SlugRoute'`
 * - `named` is used to configure named parameters at the route level. This key uses the same options
 *   as Router::connectNamed()
 *
 * You can also add additional conditions for matching routes to the $defaults array.
 * The following conditions can be used:
 *
 * - `[type]` Only match requests for specific content types.
 * - `[method]` Only match requests with specific HTTP verbs.
 * - `[server]` Only match when $_SERVER['SERVER_NAME'] matches the given value.
 *
 * Example of using the `[method]` condition:
 *
 * `Router::connect('/tasks', array('controller' => 'tasks', 'action' => 'index', '[method]' => 'GET'));`
 *
 * The above route will only be matched for GET requests. POST requests will fail to match this route.
 *
 * @param string $route A string describing the template of the route
 * @param array $defaults An array describing the default route parameters. These parameters will be used by default
 *   and can supply routing parameters that are not dynamic. See above.
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments, supplying patterns for routing parameters and supplying the name of a
 *   custom routing class.
 * @see routes
 * @see parse().
 * @return array Array of routes
 * @throws Exception
 */
    public static function connect($route, $defaults = array()) {
        self::$initialized = true;

        foreach (self::$_prefixes as $prefix) {
            if (isset($defaults[$prefix])) {
                if ($defaults[$prefix]) {
                    $defaults['prefix'] = $prefix;
                } else {
                    unset($defaults[$prefix]);
                }
                break;
            }
        }
        if (isset($defaults['prefix']) && !in_array($defaults['prefix'], self::$_prefixes)) {
            self::$_prefixes[] = $defaults['prefix'];
        }
        if (empty($options['action'])) {
            $defaults += array('action' => 'index');
        }
        self::$routes[] = array($route => $defaults);
        return self::$routes;
    }

/**
 * Connects a new redirection Route in the router.
 *
 * Redirection routes are different from normal routes as they perform an actual
 * header redirection if a match is found. The redirection can occur within your
 * application or redirect to an outside location.
 *
 * Examples:
 *
 * `Router::redirect('/home/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => true));`
 *
 * Redirects /home/* to /posts/view and passes the parameters to /posts/view. Using an array as the
 * redirect destination allows you to use other routes to define where a URL string should be redirected to.
 *
 * `Router::redirect('/posts/*', 'http://google.com', array('status' => 302));`
 *
 * Redirects /posts/* to http://google.com with a HTTP status of 302
 *
 * ### Options:
 *
 * - `status` Sets the HTTP status (default 301)
 * - `persist` Passes the params to the redirected route, if it can. This is useful with greedy routes,
 *   routes that end in `*` are greedy. As you can remap URLs and not loose any passed/named args.
 *
 * @param string $route A string describing the template of the route
 * @param array $url A URL to redirect to. Can be a string or a CakePHP array-based URL
 * @param array $options An array matching the named elements in the route to regular expressions which that
 *   element should match. Also contains additional parameters such as which routed parameters should be
 *   shifted into the passed arguments. As well as supplying patterns for routing parameters.
 * @see routes
 * @return array Array of routes
 */
    public static function redirect($current = null, $url = array(), $options = array())
    {
        if (empty($current)) {
            $current = $_SERVER['REQUEST_URI'];
        }

        $currentParams = Router::loadUrl($current);

        if (isset($options['persistParams']) && $options['persistParams'] === true) {
            $url = $currentParams + $url;
        }

        $url = self::url($url, true);

        if (isset($options['statusCode']) && $options['statusCode'] > 0) {
            header("Location: ".$url, $options['statusCode']);
        } else {
            header("Location: ".$url);
        }
        exit();
    }

/**
 * Returns the list of prefixes used in connected routes
 *
 * @return array A list of prefixes used in connected routes
 */
    public static function prefixes()
    {
        return self::$_prefixes;
    }

/**
 * Reloads default Router settings. Resets all class variables and
 * removes all connected routes.
 *
 * @return void
 */
    public static function reload()
    {
        if (empty(self::$_initialState)) {
            self::$_initialState = get_class_vars('Router');
            return;
        }
        foreach (self::$_initialState as $key => $val) {
            if ($key !== '_initialState') {
                self::${$key} = $val;
            }
        }
    }

    public static function url($url = null, $fullBaseUrl = false, $full = false)
    {
        $newUrl = null;

        if (is_bool($full)) {
            $escape = false;
        } else {
            extract($full + array('escape' => false, 'full' => false));
        }

        if (is_string($url)) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
            $url = self::normalize($url);

            if (strpos($url, "?")) {
                list($newUrl, $querystring) = explode("?", $url);
            } else {
                $newUrl = $url;
            }
        } else {
            if (isset($url['prefix'])) {
                $url[$url['prefix']] = true;
                if (isset($url['action'])) {
                    $url['action'] = str_ireplace($url['prefix']."_", "", $url['action']);
                }
            }
            $routes = Router::$routes;
            $rules = self::$_namedExpressions['rules'];
            $separator = self::$_namedExpressions['separator'];

            foreach($routes as $route => $arrRoute) {
                $routeEdited = key($arrRoute);
                $urlParams = $url;
                $params = isset($urlParams['params']) && !empty($urlParams['params']) ? $urlParams['params'] : null;
                unset($urlParams['params']);
                unset($urlParams['querystring']);
                $paramsURL = $arrParams = array();

                $allParams = false;
                if (array_search($urlParams, $arrRoute) !== false) {
                    if (!empty($params)) {
                        foreach ($params as $keyParam => $param) {
                            if (($pos = strpos($routeEdited, $separator.$keyParam)) !== false) {
                                $routeEdited = substr_replace($routeEdited, $param, $pos, strlen($separator.$keyParam));
                                $allParams = true;
                            }
                        }
                    } else {
                        $allParams = true;
                    }

                    if ($allParams) {
                        $rulesUrl = array_keys($rules);
                        $paramsMissing = false;
                        foreach ($rulesUrl as $ruleUrl) {
                            if (($pos = strpos($routeEdited, $separator.$ruleUrl)) !== false) {
                                $paramsMissing = true;
                                break;
                            }
                        }
                        if (!$paramsMissing) {
                            $newUrl = $routeEdited;
                            break;
                        }
                    }
                }
            }

            if (empty($newUrl)) {
                $newUrl = '/'.
                    (isset($url['prefix']) ? $url['prefix'].'/' : null).
                    (isset($url['controller']) ? strtolower($url['controller']).'/' : null).
                    (isset($url['action']) && $url['action'] != 'index' ? strtolower($url['action']).'/' : null).
                    (isset($url['params']['slug']) ? strtolower($url['params']['slug']).'/' : null).
                    (isset($url['params']['type']) ? strtolower($url['params']['type']).'/' : null).
                    (isset($url['params']['id']) ? strtolower($url['params']['id']) : null).
                    (isset($url['params']['page']) ? strtolower($url['params']['page']) : null);
            }
        }

        if (isset($url['querystring'])) {
            $q = $url['querystring'];
        } elseif (!empty($querystring)) {
            $q = $querystring;
        } else {
            $q = array();
        }

        if (!empty(Router::$_baseFolder)) {
            $newUrl = Router::$_baseFolder.$newUrl;
        }

        return $newUrl.self::queryString($q, array(), $escape);
    }

    public static function baseFolder($folder = null)
    {
        if ($folder !== null) {
            self::$_baseFolder = $folder;
        }
        if (empty(self::$_baseFolder)) {
            self::$_baseFolder = '/'.BASE_FOLDER;
        }
        return self::$_baseFolder;
    }

    public static function checkRouteParams($route = null, $params)
    {
        if (empty($route)) {
            $route = $_SERVER['REQUEST_URI'];
        }
        $url = Router::url($params, true);

        return $route == $url;
    }

/**
 * Sets the full base URL that will be used as a prefix for generating
 * fully qualified URLs for this application. If no parameters are passed,
 * the currently configured value is returned.
 *
 * ## Note:
 *
 * If you change the configuration value ``App.fullBaseUrl`` during runtime
 * and expect the router to produce links using the new setting, you are
 * required to call this method passing such value again.
 *
 * @param string $base the prefix for URLs generated containing the domain.
 * For example: ``http://example.com``
 * @return string
 */
    public static function loadUrl()
    {
        $url = $_SERVER['REQUEST_URI'];

        if (strpos($url, "?") !== false) {
            list($url, $queryString) = explode("?", $url);
        }

        $url = Router::normalize($url);

        $routes = self::$routes;

        $preRoutes = array();
        foreach ($routes as $key => $value) {
            $preRoutes[$key] = array_keys($value);
        }

        $rules = self::$_namedExpressions['rules'];
        $separator = self::$_namedExpressions['separator'];

        $arrRuleRoutes = array();
        foreach ($preRoutes as $key => $ruleRoute) {
            $regexRoute = $ruleRoute[0] != "/" ? rtrim($ruleRoute[0], "/") : $ruleRoute[0];
            $arrParams = array();
            $countParam = 0;
            foreach ($rules as $ruleParam => $rule) {
                if (($count = substr_count($regexRoute, $separator.$ruleParam)) > 0) {
                    while (($pos = strpos($regexRoute, $separator.$ruleParam)) !== false) {
                        if (!in_array($ruleParam, $arrParams) || !in_array($ruleParam, array('controller', 'action'))) {
                            if (in_array($ruleParam, $arrParams)) {
                                $countParam++;
                                $arrParams[(int) $pos] = $ruleReplace = $ruleParam.$countParam;
                            } else {
                                $arrParams[(int) $pos] = $ruleReplace = $ruleParam;
                            }
                            $regexRoute = substr_replace($regexRoute, $rule, $pos, strlen($separator.$ruleReplace));

                        }
                    }
                }
            }
            $arrRuleRoutes[] = $currentRule = '/^'.str_replace('/', '\/', $regexRoute).'$/i';
            preg_match($currentRule, $url, $result);

            if (!empty($result)) {
                unset($result[0]);
                ksort($arrParams);
                $params = array_combine($arrParams, $result) + $routes[$key][$ruleRoute[0]];
                foreach ($params as $key => $value) {
                    if (!in_array($key, array('prefix', 'controller', 'action')) && !in_array($key, Router::$_prefixes)) {
                        $params['params'][$key] = $value;
                        unset($params[$key]);
                    }
                }
                break;
            }
        }
        if (strpos($params['action'], "_")) {
            list($prefix, $action) = explode("_", $params['action']);
            if (array_search($prefix, Router::$_prefixes) !== false) {
                $params['prefix'] = $prefix;
                $params['action'] = $action;
            }
        }

        if  (isset($params['prefix'])) {
            $params['action'] = $params['prefix'].'_'.$params['action'];
        }

        if (isset($queryString) && !empty($queryString)) {
            $params['querystring'] = $queryString;
            parse_str($queryString, $params['params']['query']);
        }

        return $params;
    }

/**
 * Generates a well-formed querystring from $q
 *
 * @param string|array $q Query string Either a string of already compiled query string arguments or
 *    an array of arguments to convert into a query string.
 * @param array $extra Extra querystring parameters.
 * @param bool $escape Whether or not to use escaped &
 * @return array
 */
    public static function queryString($q, $extra = array(), $escape = false)
    {
        if (empty($q) && empty($extra)) {
            return null;
        }
        $join = '&';
        if ($escape === true) {
            $join = '&amp;';
        }
        $out = '';

        if (is_array($q)) {
            $q = array_merge($q, $extra);
        } else {
            $out = $q;
            $q = $extra;
        }
        $addition = http_build_query($q, null, $join);

        if ($out && $addition && substr($out, strlen($join) * -1, strlen($join)) !== $join) {
            $out .= $join;
        }

        $out .= $addition;

        if (isset($out[0]) && $out[0] !== '?') {
            $out = '?' . $out;
        }
        return $out;
    }

/**
 * Normalizes a URL for purposes of comparison.
 *
 * Will strip the base path off and replace any double /'s.
 * It will not unify the casing and underscoring of the input value.
 *
 * @param array|string $url URL to normalize Either an array or a string URL.
 * @return string Normalized URL
 */
    public static function normalize($url = '/')
    {
        if (is_array($url)) {
            $url = Router::url($url);
        }
        if (preg_match('/^[a-z\-]+:\/\//', $url) || filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (!empty(Router::$_baseFolder) && stristr($url, Router::$_baseFolder)) {
            $url = preg_replace('/^' . preg_quote(Router::$_baseFolder, '/') . '/', '', $url, 1);
        }
        $url = '/' . $url;

        while (strpos($url, '//') !== false) {
            $url = str_replace('//', '/', $url);
        }
        $url = preg_replace('/(?:(\/$))/', '', $url);

        if (empty($url)) {
            return '/';
        }
        return $url;
    }

/**
 * Loads route configuration
 *
 * @return void
 */
    public static function loadRoutes()
    {
        self::$initialized = true;
        include ROOT . DS . APP . DS . 'Config' . DS . 'routes.php';
    }

}

//Save the initial state
Router::reload();