<?php

namespace Xabi\Application\Loaders;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Xabi\Application\Application;

/**
* EnvironmentLoader
*/
class EnvironmentLoader {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Xabi\Application\Application  $app
	 * @return void
	 */
	public function load(Application $app) {
		try {
			$env = new Dotenv($app->environmentPath(), $app->environmentFile());
			$env->overload();
		}
		catch (InvalidPathException $e) {
			//
		}
	}
}