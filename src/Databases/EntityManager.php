<?php

namespace Xabi\Databases;

use Xabi\Utils\StringManager as Str;

/**
* EntityManager
*/
class EntityManager {

	private	$db;
	private $bundle;

	private $driver;
	private $default;

	public function __construct($_bundle = NULL, $_db = NULL) {

		if ($_bundle === NULL){
			return FALSE;
		}

		$this->driver	= config('database.driver');
		$this->default	= config('database.default_connection');

		$this->bundle	= $_bundle;

		if ($_db === NULL) {
			$this->db = config('database.connections.'.$this->driver.'.'.$this->default);
		}
		else{
			$this->db = $_db;
		}

	}

	// Model access
	public $model = NULL;
	public function Model() {

		$class = $this->getModel();

		if ($this->model === NULL){

			switch (strtolower($this->driver)) {
				case 'mysql':
					// init decorator new Bundle(new MySQLModel($database))
					$this->model = new $class(new MySQLModel($this->db));
				break;

				case 'mongo':
					// init decorator new Bundle(new MongoModel($database))
					$this->model = new $class(new MongoModel($this->db));
				break;

				default:
					throw new \InvalidArgumentException(sprintf('Configuration Error: Database Driver "%s" does not exist.', $this->driver));
				break;
			}
		}

		return $this->model;
	}

	private function getModel() {
		$class	= 	user_namespace() . ucfirst(Str::lower($this->bundle)) . 'Model';

		if (!class_exists($class)) {
			throw new \InvalidArgumentException(sprintf('Model "%s" does not exist.', $class));
		}

		return $class;
	}

}
?>