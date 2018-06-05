<?php


namespace Xabi\Application\Loaders;

use Xabi\Application\Application;
use Xabi\Utils\Repository;
use Symfony\Component\Finder\Finder;

/**
* TranslationLoader
*/
class TranslationLoader {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Xabi\Application\Application  $app
	 * @return void
	 */
	public function load(Application $app) {

		$items = [];

		$items = $this->getLocalesFiles($app);

		$app->translations = new Repository($items);
	}

	/**
	* @api {PUBLIC} getLocalesFiles DESCRIPTION
	*
	* @apiName getLocalesFiles
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	* @rights/getLocalesFiles/RIGHT/Users will be able to RIGHT_DESC
	**/
	public function getLocalesFiles(Application &$app){
		$files = [];

		$langExt = env("LANG_EXT", ".json");
		$langPath = realpath($app->langPath());

		foreach (Finder::create()->files()->name('*.json')->in($langPath) as $file) {
			$files[basename($file->getRealPath(), '.json')] = json_decode(file_get_contents($file->getRealPath()), true);
		}

		return $files;
	}
}