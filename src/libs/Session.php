<?php

namespace libs;

class Session {

/**
 * True if the Session is still valid
 *
 * @var boolean
 */
	public static $valid = false;

/**
 * Error messages for this session
 *
 * @var array
 */
	public static $error = false;

/**
 * User agent string
 *
 * @var string
 */
	protected static $_userAgent = '';

/**
 * Path to where the session is active.
 *
 * @var string
 */
	public static $path = '/';

/**
 * Error number of last occurred error
 *
 * @var integer
 */
	public static $lastError = null;

/**
 * Start time for this session.
 *
 * @var integer
 */
	public static $time = false;

/**
 * Cookie lifetime
 *
 * @var integer
 */
	public static $cookieLifeTime;

/**
 * Time when this session becomes invalid.
 *
 * @var integer
 */
	public static $sessionTime = false;

/**
 * Current Session id
 *
 * @var string
 */
	public static $id = null;

/**
 * Hostname
 *
 * @var string
 */
	public static $host = null;

/**
 * Session timeout multiplier factor
 *
 * @var integer
 */
	public static $timeout = null;

/**
 * Number of requests that can occur during a session time without the session being renewed.
 *
 * @var integer
 * @see Session::_checkValid()
 */
	public static $requestCountdown = 10;

/**
 * Whether or not the init function in this class was already called
 *
 * @var boolean
 */
	protected static $_initialized = false;

/**
 * Pseudo constructor.
 *
 * @param string $base The base path for the Session
 * @return void
 */
	public static function init($base = null) {
		self::$time = time();
		self::$sessionTime = self::$time + (240 * 60);

		if ($_SERVER['HTTP_USER_AGENT']) {
			self::$_userAgent = $_SERVER['HTTP_USER_AGENT'];
		}

		self::_setPath($base);
		self::_setHost($_SERVER['HTTP_HOST']);

		if (!self::$_initialized) {
			register_shutdown_function('session_write_close');
		}

		self::$_initialized = true;
	}

/**
 * Setup the Path variable
 *
 * @param string $base base path
 * @return void
 */
	protected static function _setPath($base = null) {
		if (empty($base)) {
			self::$path = '/';
			return;
		}
		if (strpos($base, 'index.php') !== false) {
			$base = str_replace('index.php', '', $base);
		}
		if (strpos($base, '?') !== false) {
			$base = str_replace('?', '', $base);
		}
		self::$path = $base;
	}

/**
 * Set the host name
 *
 * @param string $host Hostname
 * @return void
 */
	protected static function _setHost($host) {
		self::$host = $host;
		if (strpos(self::$host, ':') !== false) {
			self::$host = substr(self::$host, 0, strpos(self::$host, ':'));
		}
	}

/**
 * Starts the Session.
 *
 * @return boolean True if session was started
 */
	public static function start() {
		if (self::started()) {
			return true;
		}

		$id = self::id();
		self::_startSession();

		if (!$id && self::started()) {
			self::_checkValid();
		}

		self::$error = false;
		self::$valid = true;
		return self::started();
	}

/**
 * Determine if Session has been started.
 *
 * @return boolean True if session has been started.
 */
	public static function started() {
		return isset($_SESSION) && session_id();
	}

/**
 * Returns true if given variable is set in session.
 *
 * @param string $name Variable name to check for
 * @return boolean True if variable is there
 */
	public static function check($name = null) {
		if (!self::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		return Hash::get($_SESSION, $name) !== null;
	}

/**
 * Returns the session id.
 * Calling this method will not auto start the session. You might have to manually
 * assert a started session.
 *
 * Passing an id into it, you can also replace the session id if the session
 * has not already been started.
 * Note that depending on the session handler, not all characters are allowed
 * within the session id. For example, the file session handler only allows
 * characters in the range a-z A-Z 0-9 , (comma) and - (minus).
 *
 * @param string $id Id to replace the current session id
 * @return string Session id
 */
	public static function id($id = null) {
		if ($id) {
			self::$id = $id;
			session_id(self::$id);
		}
		if (self::started()) {
			return session_id();
		}
		return self::$id;
	}

/**
 * Removes a variable from session.
 *
 * @param string $name Session variable to remove
 * @return boolean Success
 */
	public static function delete($name) {
		if (self::check($name)) {
			self::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
			return !self::check($name);
		}
		return false;
	}

/**
 * Used to write new data to _SESSION, since PHP doesn't like us setting the _SESSION var itself.
 *
 * @param array $old Set of old variables => values
 * @param array $new New set of variable => value
 * @return void
 */
	protected static function _overwrite(&$old, $new) {
		if (!empty($old)) {
			foreach ($old as $key => $var) {
				if (!isset($new[$key])) {
					unset($old[$key]);
				}
			}
		}
		foreach ($new as $key => $var) {
			$old[$key] = $var;
		}
	}

/**
 * Return error description for given error number.
 *
 * @param integer $errorNumber Error to set
 * @return string Error as string
 */
	protected static function _error($errorNumber) {
		if (!is_array(self::$error) || !array_key_exists($errorNumber, self::$error)) {
			return false;
		}
		return self::$error[$errorNumber];
	}

/**
 * Returns last occurred error as a string, if any.
 *
 * @return mixed Error description as a string, or false.
 */
	public static function error() {
		if (self::$lastError) {
			return self::_error(self::$lastError);
		}
		return false;
	}

/**
 * Returns true if session is valid.
 *
 * @return boolean Success
 */
	public static function valid() {
		if (self::read('Config')) {
			if (self::_validAgentAndTime() && self::$error === false) {
				self::$valid = true;
			} else {
				self::$valid = false;
				self::_setError(1, 'Session Highjacking Attempted !!!');
			}
		}
		return self::$valid;
	}

/**
 * Tests that the user agent is valid and that the session hasn't 'timed out'.
 * Since timeouts are implemented in Session it checks the current self::$time
 * against the time the session is set to expire. The User agent is only checked
 * if Session.checkAgent == true.
 *
 * @return boolean
 */
	protected static function _validAgentAndTime() {
		$config = self::read('Config');
		$validAgent = (self::$_userAgent == $config['userAgent']);
		return ($validAgent && self::$time <= $config['time']);
	}

/**
 * Get / Set the user agent
 *
 * @param string $userAgent Set the user agent
 * @return string Current user agent
 */
	public static function userAgent($userAgent = null) {
		if ($userAgent) {
			self::$_userAgent = $userAgent;
		}
		if (empty(self::$_userAgent)) {
			Session::init(self::$path);
		}
		return self::$_userAgent;
	}

/**
 * Returns given session variable, or all of them, if no parameters given.
 *
 * @param string|array $name The name of the session variable (or a path as sent to Set.extract)
 * @return mixed The value of the session variable
 */
	public static function read($name = null) {
		if (!self::start()) {
			return false;
		}
		if ($name === null) {
			return self::_returnSessionVars();
		}
		if (empty($name)) {
			return false;
		}
		$result = Hash::get($_SESSION, $name);

		if (isset($result)) {
			return $result;
		}
		return null;
	}

/**
 * Returns all session variables.
 *
 * @return mixed Full $_SESSION array, or false on error.
 */
	protected static function _returnSessionVars() {
		if (!empty($_SESSION)) {
			return $_SESSION;
		}
		self::_setError(2, 'No Session vars set');
		return false;
	}

/**
 * Writes value to given session variable name.
 *
 * @param string|array $name Name of variable
 * @param string $value Value to write
 * @return boolean True if the write was successful, false if the write failed
 */
	public static function write($name, $value = null) {
		if (!self::start()) {
			return false;
		}
		if (empty($name)) {
			return false;
		}
		$write = $name;
		if (!is_array($name)) {
			$write = array($name => $value);
		}
		foreach ($write as $key => $val) {
			self::_overwrite($_SESSION, Hash::insert($_SESSION, $key, $val));
			if (Hash::get($_SESSION, $key) !== $val) {
				return false;
			}
		}
		return true;
	}

/**
 * Helper method to destroy invalid sessions.
 *
 * @return void
 */
	public static function destroy() {
		if (!self::started()) {
			self::_startSession();
		}

		session_destroy();

		$_SESSION = null;
		self::$id = null;
	}

/**
 * Clears the session, the session id, and renews the session.
 *
 * @return void
 */
	public static function clear() {
		$_SESSION = null;
		self::$id = null;
		self::renew();
	}

/**
 * Helper method to start a session
 *
 * @return boolean Success
 */
	protected static function _startSession() {
		self::init();
		session_write_close();
		self::$sessionTime = self::$time + (240 * 60);

		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
		} else {
			// For IE<=8
			session_cache_limiter("must-revalidate");
			session_start();
		}
		return true;
	}

/**
 * Helper method to create a new session.
 *
 * @return void
 */
	protected static function _checkValid() {
		$config = self::read('Config');
		if ($config) {

			if (self::valid()) {
				self::write('Config.time', self::$sessionTime);
                $check = $config['countdown'];
                $check -= 1;
                self::write('Config.countdown', $check);

                if ($check < 1) {
                    self::renew();
                    self::write('Config.countdown', self::$requestCountdown);
                }
			} else {
				$_SESSION = array();
				self::destroy();
				self::_setError(1, 'Session Highjacking Attempted !!!');
				self::_startSession();
				self::_writeConfig();
			}
		} else {
			self::_writeConfig();
		}
	}

/**
 * Writes configuration variables to the session
 *
 * @return void
 */
	protected static function _writeConfig() {
		self::write('Config.userAgent', self::$_userAgent);
		self::write('Config.time', self::$sessionTime);
		self::write('Config.countdown', self::$requestCountdown);
	}

/**
 * Restarts this session.
 *
 * @return void
 */
	public static function renew() {
		if (session_id()) {
			if (session_id() || isset($_COOKIE[session_name()])) {
				setcookie('ACADEMICO', '', time() - 42000, self::$path);
			}
			session_regenerate_id(true);
		}
	}

/**
 * Helper method to set an internal error message.
 *
 * @param integer $errorNumber Number of the error
 * @param string $errorMessage Description of the error
 * @return void
 */
	protected static function _setError($errorNumber, $errorMessage) {
		if (self::$error === false) {
			self::$error = array();
		}
		self::$error[$errorNumber] = $errorMessage;
		self::$lastError = $errorNumber;
	}

}
