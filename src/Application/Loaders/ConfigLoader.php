<?php

namespace Xabi\Application\Loaders;

use Xabi\Application\Application;
use Xabi\Utils\Repository;
use Symfony\Component\Finder\Finder;

/**
* ConfigLoader
*/
class ConfigLoader {

	/**
	* @api {PUBLIC} load DESCRIPTION
	*
	* @apiName load
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	**/
	public function load(Application &$app){

		$items = [];

		$app->configs = new Repository($items);

		$this->loadConfigurationFiles($app, $app->configs);
	}

	/**
	* @api {PUBLIC} loadConfigurationFiles DESCRIPTION
	*
	* @apiName loadConfigurationFiles
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	* @rights/loadConfigurationFiles/RIGHT/Users will be able to RIGHT_DESC
	**/
	public function loadConfigurationFiles(Application &$app, Repository &$repository){

		$files = $this->getConfigurationFiles($app);

		if (! isset($files['app'])) {
			throw new \Exception('Unable to load the "app" configuration file.');
		}

		foreach ($files as $key => $path) {
			$repository->set($key, require $path);
		}
	}

	/**
	* @api {PUBLIC} loadConfigurationFiles DESCRIPTION
	*
	* @apiName loadConfigurationFiles
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	* @rights/loadConfigurationFiles/RIGHT/Users will be able to RIGHT_DESC
	**/
	public function getConfigurationFiles(Application &$app){
		$files = [];

		$configPath = realpath($app->configPath());

		foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
			$directory = $this->getNestedDirectory($file, $configPath);

			$files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
		}

		return $files;
	}

	/**
	* @api {PUBLIC} loadConfigurationFiles DESCRIPTION
	*
	* @apiName loadConfigurationFiles
	* @apiGroup BUNDLE
	* @apiVersion 1.0.0
	*
	* @apiDescription DESCR
	* @rights/loadConfigurationFiles/RIGHT/Users will be able to RIGHT_DESC
	**/
	public function getNestedDirectory(\SplFileInfo $file, $configPath){
		$directory = $file->getPath();

		if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
			$nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
		}

		return $nested;
	}
}