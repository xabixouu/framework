<?php

use Xabi\Application;
use Xabi\Utils\Collection;
use Xabi\Utils\ArrayManager as Arr;
use Xabi\Utils\Debug\Dumper;

if (! function_exists('app')) {
	/**
	 * Get the available container instance.
	 *
	 * @param  string  $key
	 * @param  array   $parameters
	 * @return mixed|\Xabi\Application
	 */
	function app() {
		return Application\Application::getInstance();
	}
}

// PATHS
if (! function_exists('app_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function app_path($path = '') {
		return app()->path('app').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

if (! function_exists('layout_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function layout_path($path = '') {
		return app_path('layouts'.($path ? DIRECTORY_SEPARATOR.$path : $path));
	}
}

if (! function_exists('images_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function images_path($path = '') {
		return public_path('images'.($path ? DIRECTORY_SEPARATOR.$path : $path));
	}
}

if (! function_exists('css_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function css_path($path = '') {
		return public_path('css'.($path ? DIRECTORY_SEPARATOR.$path : $path));
	}
}

if (! function_exists('js_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function js_path($path = '') {
		return public_path('js'.($path ? DIRECTORY_SEPARATOR.$path : $path));
	}
}

if (! function_exists('src_path')) {
	/**
	 * Get the path to the application folder.
	 *
	 * @return string
	 */
	function src_path($path = '') {
		return app_path('src'.($path ? DIRECTORY_SEPARATOR.$path : $path));
	}
}

if (! function_exists('base_path')) {
	/**
	 * Get the path to the base of the install.
	 *
	 * @return string
	 */
	function base_path($path = '') {
		return app()->path('root').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

if (! function_exists('config_path')) {
	/**
	 * Get the configuration path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function config_path($path = '') {
		return app()->path('config').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

if (! function_exists('cache_path')) {
	/**
	 * Get the configuration path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function cache_path($path = '') {
		return app()->path('cache').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

if (! function_exists('public_path')) {
	/**
	 * Get the configuration path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function public_path($path = '') {
		return app()->path('public').($path ? DIRECTORY_SEPARATOR.$path : $path);
	}
}

// GLOBALS
if (! function_exists('config')) {
	/**
	 * Get / set the specified configuration value.
	 *
	 * If an array is passed as the key, we will assume you want to set an array of values.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function config($key = null, $default = null) {
		return app()->config($key, $default);
	}
}

if (! function_exists('lang')) {
	/**
	 * Get / set the specified configuration value.
	 *
	 * If an array is passed as the key, we will assume you want to set an array of values.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function lang($key = null, $default = null) {
		return app()->lang($key);
	}
}

if (! function_exists('user_namespace')) {
	/**
	 * Get the User's Namespace
	 *
	 * @param  string  $path
	 * @return string
	 */
	function user_namespace() {
		app()->setNamespace();
		return app()->getNamespace();
	}
}

if (! function_exists('set_cookie')) {
	/**
	 * Create a new cookie instance.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $minutes
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $httpOnly
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	function set_cookie(
		$name = null,
		$value = null,
		$minutes = 0,
		$path = null,
		$domain = null,
		$secure = false,
		$httpOnly = true
	) {
		if (is_null($name) || is_null($value)) {
			return false;
		}
		$name = config('session.domain', '') . $name;

		return app()->setCookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
	}
}

if (! function_exists('destroy_cookie')) {
	/**
	 * Create a new cookie instance.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $minutes
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $httpOnly
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	function destroy_cookie(
		$name = null
	) {
		if (is_null($name)) {
			return false;
		}
		$name = config('session.domain', '') . $name;

		return app()->setCookie($name, '', -2000000);
	}
}

if (! function_exists('env')) {
	/**
	 * Gets the value of an environment variable.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function env($key, $default = null) {
		$value = getenv($key);

		if ($value === false) {
			return $default;
		}

		switch (strtolower($value)) {
			case 'true':
			case '(true)':
				return true;
			case 'false':
			case '(false)':
				return false;
			case 'empty':
			case '(empty)':
				return '';
			case 'null':
			case '(null)':
				return;
		}
		if (strlen($value) > 1 && $value[0] == '"' && $value[-1] ==  '"') {
			return substr($value, 1, -1);
		}

		return $value;
	}
}

if (! function_exists('session')) {
	/**
	 * Get / set the specified session value.
	 *
	 * If an array is passed as the key, we will assume you want to set an array of values.
	 *
	 * @param  array|string  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function session($key = null, $default = null, $toRemove = FALSE) {
		if (is_null($key)) {
			return app()->session->all();
		}

		if (is_array($key)) {
			$keys = $key;
			foreach ($keys as $key => $value) {
				$key = config('session.domain', '') . $key;
				app()->session->set($key, $value);
			}
		}

		$key = config('session.domain', '') . $key;
		$res = app()->session->get($key, ($default != 'toRemove' ? $default : ''));

		if ($default === 'toRemove' AND $toRemove === TRUE) {
			app()->session->remove($key);
		}
		return $res;

	}
}

// ENCRYPTERS
if (! function_exists('encrypt')) {
	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function encrypt($value) {

		return app()->encrypter->encrypt($value);
	}
}

if (! function_exists('safe_encrypt')) {
	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function safe_encrypt($value) {

		return app()->encrypter->safe_encrypt($value);
	}
}

if (! function_exists('safe_decrypt')) {
	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function safe_decrypt($value) {

		return app()->encrypter->safe_decrypt($value);
	}
}

if (! function_exists('decrypt')) {
	/**
	 * Decrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function decrypt($value) {

		return app()->encrypter->decrypt($value);
	}
}

if (! function_exists('password_encrypt')) {
	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function password_encrypt($value) {
		return app()->encrypter->encryptPassword($value);
	}
}

if (! function_exists('password_verify')) {
	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function password_verify($value, $hash) {
		return app()->encrypter->verifyPassword($value, $hash);
	}
}

if (! function_exists('generate_password')) {
	/**
	 * Generate a password to the given length.
	 *
	 * @param  string  $length
	 * @return string
	 */
	function generate_password($length = 10) {

		return app()->encrypter->generatePassword($length);
	}
}


// UTILITARIES
if (! function_exists('value')) {
	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	function value($value) {
		return $value instanceof Closure ? $value() : $value;
	}
}

if (! function_exists('data_get')) {
	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param  mixed   $target
	 * @param  string|array  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function data_get($target, $key, $default = null) {
		if (is_null($key)) {
			return $target;
		}

		$key = is_array($key) ? $key : explode('.', $key);

		while (! is_null($segment = array_shift($key))) {
			if ($segment === '*') {
				if ($target instanceof Collection) {
					$target = $target->all();
				} elseif (! is_array($target)) {
					return value($default);
				}

				$result = Arr::pluck($target, $key);

				return in_array('*', $key) ? Arr::collapse($result) : $result;
			}

			if (Arr::accessible($target) && Arr::exists($target, $segment)) {
				$target = $target[$segment];
			} elseif (is_object($target) && isset($target->{$segment})) {
				$target = $target->{$segment};
			} else {
				return value($default);
			}
		}

		return $target;
	}
}

if (! function_exists('dd')) {
	/**
	 * Dump the passed variables and end the script.
	 *
	 * @param  mixed
	 * @return void
	 */
	function dd(...$args) {
		foreach ($args as $x) {
			(new Dumper)->dump($x);
		}
		die(1);
	}
}

if (! function_exists('collect')) {
	/**
	 * Create a collection from the given value.
	 *
	 * @param  mixed  $value
	 * @return \Xabi\Utils\Collection
	 */
	function collect($value = null) {
		return new Collection($value);
	}
}