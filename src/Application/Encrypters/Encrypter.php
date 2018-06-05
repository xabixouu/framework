<?php

namespace Xabi\Application\Encrypters;

use RuntimeException;

class Encrypter {
	/**
	 * The encryption key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * The algorithm used for encryption.
	 *
	 * @var string
	 */
	protected $cipher;

	/**
	 * Create a new encrypter instance.
	 *
	 * @param  string  $key
	 * @param  string  $cipher
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($key, $cipher = 'AES-128-CBC') {
		$key = (string) $key;

		if (static::supported($key, $cipher)) {
			$this->key = $key;
			$this->cipher = $cipher;
		} else {
			throw new RuntimeException(
				sprintf(
					'The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths. You have key: %s, cipher: ',
					$key,
					$cipher
				)
			);
		}
	}

	/**
	 * Determine if the given key and cipher combination is valid.
	 *
	 * @param  string  $key
	 * @param  string  $cipher
	 * @return bool
	 */
	public static function supported($key, $cipher) {
		$length = mb_strlen($key, '8bit');

		return ($cipher === 'AES-128-CBC' && $length === 16) ||
			   ($cipher === 'AES-256-CBC' && $length === 32);
	}

	/**
	 * Encrypt the given value.
	 *
	 * @param  mixed  $value
	 * @param  bool  $serialize
	 * @return string
	 *
	 * @throws \Illuminate\Contracts\Encryption\EncryptException
	 */
	public function encrypt($value, $serialize = true) {
		$iv = random_bytes(16);

		// First we will encrypt the value using OpenSSL. After this is encrypted we
		// will proceed to calculating a MAC for the encrypted value so that this
		// value can be verified later as not having been changed by the users.
		$value = \openssl_encrypt(
			$serialize ? serialize($value) : $value,
			$this->cipher, $this->key, 0, $iv
		);

		if ($value === false) {
			throw new RuntimeException('Could not encrypt the data.');
		}

		// Once we have the encrypted value we will go ahead base64_encode the input
		// vector and create the MAC for the encrypted value so we can verify its
		// authenticity. Then, we'll JSON encode the data in a "payload" array.
		$mac = $this->hash($iv = base64_encode($iv), $value);

		$json = json_encode(compact('iv', 'value', 'mac'));

		if (! is_string($json)) {
			throw new RuntimeException('Could not encrypt the data.');
		}

		return base64_encode($json);
	}

	/**
	* @api {PUBLIC} encryptPassword DESCRIPTION
	*
	* @apiName encryptPassword
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	**/
	public function encryptPassword($string){
		return password_hash(
			$string,
			PASSWORD_DEFAULT,
			['cost' => 10]
		);
	}

	/**
	* @api {PUBLIC} verifyPassword DESCRIPTION
	*
	* @apiName verifyPassword
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	**/
	public function verifyPassword($string, $hash){
		return password_verify($string, $hash);
	}

	/**
	 * Encrypt a string without serialization.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function encryptString($value) {
		return $this->encrypt($value, false);
	}

	/**
	 * Decrypt the given value.
	 *
	 * @param  mixed  $payload
	 * @param  bool  $unserialize
	 * @return string
	 *
	 * @throws \Illuminate\Contracts\Encryption\RuntimeException
	 */
	public function decrypt($payload, $unserialize = true) {
		$payload = $this->getJsonPayload($payload);

		$iv = base64_decode($payload['iv']);

		// Here we will decrypt the value. If we are able to successfully decrypt it
		// we will then unserialize it and return it out to the caller. If we are
		// unable to decrypt this value we will throw out an exception message.
		$decrypted = \openssl_decrypt(
			$payload['value'], $this->cipher, $this->key, 0, $iv
		);

		if ($decrypted === false) {
			throw new RuntimeException('Could not decrypt the data.');
		}

		return $unserialize ? unserialize($decrypted) : $decrypted;
	}

	/**
	 * Decrypt the given string without unserialization.
	 *
	 * @param  string  $payload
	 * @return string
	 */
	public function decryptString($payload) {
		return $this->decrypt($payload, false);
	}

	/**
	 * Create a MAC for the given value.
	 *
	 * @param  string  $iv
	 * @param  mixed  $value
	 * @return string
	 */
	protected function hash($iv, $value) {
		return hash_hmac('sha256', $iv.$value, $this->key);
	}

	/**
	 * Get the JSON array from the given payload.
	 *
	 * @param  string  $payload
	 * @return array
	 *
	 * @throws \Illuminate\Contracts\Encryption\RuntimeException
	 */
	protected function getJsonPayload($payload) {
		$payload = json_decode(base64_decode($payload), true);

		// If the payload is not valid JSON or does not have the proper keys set we will
		// assume it is invalid and bail out of the routine since we will not be able
		// to decrypt the given value. We'll also check the MAC for this encryption.
		if (! $this->validPayload($payload)) {
			throw new RuntimeException('The payload is invalid.');
		}

		if (! $this->validMac($payload)) {
			throw new RuntimeException('The MAC is invalid.');
		}

		return $payload;
	}

	/**
	 * Verify that the encryption payload is valid.
	 *
	 * @param  mixed  $payload
	 * @return bool
	 */
	protected function validPayload($payload) {
		return is_array($payload) && isset(
			$payload['iv'], $payload['value'], $payload['mac']
		);
	}

	/**
	 * Determine if the MAC for the given payload is valid.
	 *
	 * @param  array  $payload
	 * @return bool
	 */
	protected function validMac(array $payload) {
		$calculated = $this->calculateMac($payload, $bytes = random_bytes(16));

		return hash_equals(
			hash_hmac('sha256', $payload['mac'], $bytes, true), $calculated
		);
	}

	/**
	 * Calculate the hash of the given payload.
	 *
	 * @param  array  $payload
	 * @param  string  $bytes
	 * @return string
	 */
	protected function calculateMac($payload, $bytes) {
		return hash_hmac(
			'sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true
		);
	}

	/**
	 * Get the encryption key.
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}


	/**
	 * Generate random string
	 * @param  integer $nbBytes Nb chars
	 * @return string           generated string
	 */
	private function getRandomBytes($nbBytes = 32){
		$bytes = openssl_random_pseudo_bytes($nbBytes, $strong);
		if (false !== $bytes) {
			return $bytes;
		}
	}
	/**
	 * Generate password
	 * @param  integer $length Length of password
	 * @return string          generated password
	 */
	public function generatePassword($length = 10){
		return substr(preg_replace("/[^a-zA-Z0-9]/", "", base64_encode($this->getRandomBytes($length+1))), 0, $length);
	}

	/**
	 * Encrypt without time component
	 * @return string encrypted data
	 */
	public function safe_encrypt($string){

		$this->key2 = substr($this->key, 0, 16);
		$encrypted = base64_encode(
			\openssl_encrypt(
				$string,
				$this->cipher,
				$this->key,
				0,
				$this->key2
			)
		);
		return $encrypted;
	}

	/**
	 * Encrypt without time component
	 * @return string encrypted data
	 */
	public function safe_decrypt($string){

		$this->key2 = substr($this->key, 0, 16);
		$decrypted = \openssl_decrypt(
			base64_decode($string),
			$this->cipher,
			$this->key,
			0,
			$this->key2
		);
		return $decrypted;
	}
}
