<?php

namespace Xabi\Application\Loaders;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Xabi\Application\Application;

/**
* SessionLoader
*/
class SessionLoader {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Xabi\Application\Application  $app
	 * @return void
	 */
	public function load(Application $app) {

		// var_dump(config('session.driver'));
		// die();

		if (config('session.driver') == "redis"){

			if (config('session.connection') != "" AND
				config('database.redis.'.config('session.connection')) != ""
			){
				$conf = config('database.redis.'.config('session.connection'));
				ini_set(
					'session.save_handler',
					'redis'
				);
				var_dump('tcp://CACHE_IP_VARIABLE:6379?auth=CACHE_PASSWORD_VARIABLE');
				die();
				ini_set(
					'session.save_path',
					'tcp://CACHE_IP_VARIABLE:6379?auth=CACHE_PASSWORD_VARIABLE'
				);
			}
			else{
				throw new \Exception(sprintf(
					"Cant find connection '%s' in database.redis config",
					config('session.connection')
				), 1);
			}
		}
		else if (config('session.driver') == "default"){
		}
		else{
			throw new \Exception(sprintf("Session Driver %s is not handled", config('session.driver')), 1);
		}


		session_start();

		// Get Symfony to interface with this existing session
		$app->session = new Session(new PhpBridgeSessionStorage());

		// symfony will now interface with the existing PHP session
		$app->session->start();
	}
}