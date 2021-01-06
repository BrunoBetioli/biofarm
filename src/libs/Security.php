<?php
namespace libs;

include ROOT . DS . APP . DS . 'Config' . DS . 'security.php';

/**
 * Security Library contains utility methods related to security
 */
class Security {

/**
 * Default hash method
 *
 * @var string
 */
	public static $hashType = null;

/**
 * Default cost
 *
 * @var string
 */
	public static $hashCost = '10';

/**
 * Create a hash from string using given method or fallback on next available method.
 *
 * #### Using Blowfish
 *
 * - Creating Hashes: *Do not supply a salt*. Cake handles salt creation for
 * you ensuring that each hashed password will have a *unique* salt.
 * - Comparing Hashes: Simply pass the originally hashed password as the salt.
 * The salt is prepended to the hash and php handles the parsing automagically.
 * For convenience the BlowfishAuthenticate adapter is available for use with
 * the AuthComponent.
 * - Do NOT use a constant salt for blowfish!
 *
 * Creating a blowfish/bcrypt hash:
 *
 * {{{
 * 	$hash = Security::hash($password, 'blowfish');
 * }}}
 *
 * @param string $string String to hash
 * @param string $type Method to use (sha1/sha256/md5/blowfish)
 * @param mixed $salt If true, automatically prepends the application's salt
 *     value to $string (Security.salt). If you are using blowfish the salt
 *     must be false or a previously generated salt.
 * @return string Hash
 */
	public static function hash($string, $type = null, $salt = false) {
		if (empty($type)) {
			$type = self::$hashType;
		}
		$type = strtolower($type);

		if ($type === 'blowfish') {
			return self::_crypt($string, $salt);
		}
		if ($salt) {
			if (!is_string($salt)) {
				$salt = SALT;
			}
			$string = $salt . $string;
		}

		if (!$type || $type === 'sha1') {
			if (function_exists('sha1')) {
				return sha1($string);
			}
			$type = 'sha256';
		}

		if ($type === 'sha256' && function_exists('mhash')) {
			return bin2hex(mhash(MHASH_SHA256, $string));
		}

		if (function_exists('hash')) {
			return hash($type, $string);
		}
		return md5($string);
	}

/**
 * Sets the default hash method for the Security object. This affects all objects using
 * Security::hash().
 *
 * @param string $hash Method to use (sha1/sha256/md5/blowfish)
 * @return void
 * @see Security::hash()
 */
	public static function setHash($hash) {
		self::$hashType = $hash;
	}

/**
 * Sets the cost for they blowfish hash method.
 *
 * @param integer $cost Valid values are 4-31
 * @return void
 */
	public static function setCost($cost) {
		if ($cost < 4 || $cost > 31) {
			trigger_error(
                'Invalid value, cost must be between 4 and 31',
                E_USER_WARNING
            );
			return null;
		}
		self::$hashCost = $cost;
	}

/**
 * Runs $text through a XOR cipher.
 *
 * *Note* This is not a cryptographically strong method and should not be used
 * for sensitive data. Additionally this method does *not* work in environments
 * where suhosin is enabled.
 *
 * Instead you should use Security::rijndael() when you need strong
 * encryption.
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use
 * @return string Encrypted/Decrypted string
 */
	public static function cipher($text, $key) {
		if (empty($key)) {
			trigger_error('You cannot use an empty key for Security::cipher()', E_USER_WARNING);
			return '';
		}

		srand(CYPHER_SEED);
		$out = '';
		$keyLength = strlen($key);
		for ($i = 0, $textLength = strlen($text); $i < $textLength; $i++) {
			$j = ord(substr($key, $i % $keyLength, 1));
			while ($j--) {
				rand(0, 255);
			}
			$mask = rand(0, 255);
			$out .= chr(ord(substr($text, $i, 1)) ^ $mask);
		}
		srand();
		return $out;
	}

/**
 * Encrypts/Decrypts a text using the given key using rijndael method.
 *
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use as the encryption key for encrypted data.
 * @param string $operation Operation to perform, encrypt or decrypt
 * @return string Encrypted/Decrypted string
 */
	public static function rijndael($text, $key, $operation) {
		if (empty($key)) {
			trigger_error('You cannot use an empty key forSecurity::rijndael()', E_USER_WARNING);
			return '';
		}
		if (empty($operation) || !in_array($operation, array('encrypt', 'decrypt'))) {
			trigger_error('You must specify the operation for Security::rijndael(), either encrypt or decrypt', E_USER_WARNING);
			return '';
		}
		if (strlen($key) < 32) {
			trigger_error('You must use a key larger than 32 bytes for Security::rijndael()', E_USER_WARNING);
			return '';
		}
		$algorithm = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;
		$ivSize = mcrypt_get_iv_size($algorithm, $mode);

		$cryptKey = substr($key, 0, 32);

		if ($operation === 'encrypt') {
			$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
			return $iv . '$$' . mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
		}
		// Backwards compatible decrypt with fixed iv
		if (substr($text, $ivSize, 2) !== '$$') {
			$iv = substr($key, strlen($key) - 32, 32);
			return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
		}
		$iv = substr($text, 0, $ivSize);
		$text = substr($text, $ivSize + 2);
		return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
	}

/**
 * Generates a pseudo random salt suitable for use with php's crypt() function.
 * The salt length should not exceed 27. The salt will be composed of
 * [./0-9A-Za-z]{$length}.
 *
 * @param integer $length The length of the returned salt
 * @return string The generated salt
 */
	protected static function _salt($length = 22) {
		$salt = str_replace(
			array('+', '='),
			'.',
			base64_encode(sha1(uniqid(SALT, true), true))
		);
		return substr($salt, 0, $length);
	}

/**
 * One way encryption using php's crypt() function. To use blowfish hashing see ``Security::hash()``
 *
 * @param string $password The string to be encrypted.
 * @param mixed $salt false to generate a new salt or an existing salt.
 * @return string The hashed string or an empty string on error.
 */
	protected static function _crypt($password, $salt = false) {
		if ($salt === false) {
			$salt = self::_salt(22);
			$salt = vsprintf('$2a$%02d$%s', array(self::$hashCost, $salt));
		}

		if ($salt === true || strpos($salt, '$2a$') !== 0 || strlen($salt) < 29) {
			trigger_error(
				'Invalid salt: '.$salt.' for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.',
                E_USER_WARNING
            );
			return '';
		}
		return crypt($password, $salt);
	}
}