<?php

namespace Xabi\Databases;

/**
* MongoModel
*/
class MongoModel implements ModelInterface {

	protected $client;
	protected $connectionDriver;
	protected $tmpConnectionDriver;
	protected $table;
	protected $tmpTable;

	/**
	* Init connection driver
	*/
	function __construct($database = NULL) {
		if ($database === NULL){
			if (DEVELOPMENT_ENVIRONMENT){
				print "MongoModel: Database NULL";
				die();
			}
			return FALSE;
		}

		$this->connect($database);
	}

	/**
	 * Connect to the given database
	 * @param  array  $options Database connection informations
	 * @return Connection      Database connection
	 */
	public function connect($db = NULL){

		if ($db === NULL) {

			if (DEVELOPMENT_ENVIRONMENT){
				print "MongoModel: Database NULL";
				die();
			}
			return FALSE;
		}

		try {
			$this->client = new MongoDB\Client($this->buildMongoUri($db), [
				"authSource"		=> $db['AUTH_BASE'],
				"username"			=> $db['USER'],
				"password"			=> $db['PASSWORD']
			]);
			$dbs = $this->client->listDatabases();
			$this->connectionDriver = $this->client->{$db['BASE']};
		}
		catch (Exception $e) {

			if (DEVELOPMENT_ENVIRONMENT){
				echo '<pre>';
				print_r($e);
				print "Connection mongo driver error : " . $e->getMessage() . "<br/>";
				echo '</pre>';
				die();
			}
			return FALSE;
		}
	}

	/**
	 * Build mongodb: URI
	 * @param  Array $db 	Database information
	 * @return String    	Built URI
	 */
	private function buildMongoUri($db = NULL) {

		$uri = "mongodb://";
		$uri .= $db['HOST'];
		$uri .= ':'.$db['PORT'];

		return $uri;
	}

	/**
	* Set Database
	*/
	public function setDatabase($_database) {
		$this->tmpConnectionDriver	= $this->connectionDriver;
		$this->connectionDriver		= $this->client->{$_database};
	}

	/**
	* Reset Database
	*/
	public function resetDatabase() {
		$this->connectionDriver = $this->tmpConnectionDriver;
		unset($this->tmpConnectionDriver);
	}

	/**
	* Set table
	*/
	public function setTable($_table) {
		$this->tmpTable = $this->table;
		$this->table = $_table;
	}

	/**
	* reset table
	*/
	public function resetTable() {
		$this->table = $this->tmpTable;
		unset($this->tmpTable);
	}


	/**
	* Get needed return from $stmt by $type
	*/
	public function getReturn($cursor, $_type = "array"){

		switch ($_type) {
			case 'array':
				$res = $cursor->toArray();
				if (count($res) > 0){
					return $res;
				}
				return FALSE;
			case 'one':
				$res = $cursor->toArray();
				if (count($res) > 0){
					return $res[0];
				}
				return FALSE;
			case 'insert':
				if ($cursor->getInsertedCount() > 0){
					return $cursor->getInsertedId();
				}
				return FALSE;
			case 'update':
				if ($cursor->getMatchedCount() > 0){
					return $cursor->getModifiedCount();
				}
				return FALSE;
			case 'cursor':
				return $cursor;
			default:
				return $cursor;
		}

	}

	/**
	* Find in database
	*/
	public function find($query = [], $projection = [], $returnType = 'array'){

		if (isset($query['id'])){
			$query['_id'] = new MongoDB\BSON\ObjectID($query['id']);
			unset($query['id']);
		}

		$cursor = $this->connectionDriver->{$this->table}->find($query, $projection);

		return $this->getReturn($cursor, $returnType);
	}

	/**
	* Find in database by given ID
	*/
	public function findByID($_id, $projection = []){
		return $this->findOne(['id' => $_id], $projection, 'one');
	}

	/**
	* Find in database by given parameters
	*/
	public function findOne($query = [], $projection = []){
		return $this->find($query, $projection, 'one');
	}

	/**
	* Insert or update data
	*/
	public function save($document = [], $setter = []){

		if (count($setter) > 0) {
			// UPDATE
			if (isset($document['id'])){
				$document['_id'] = new MongoDB\BSON\ObjectID($document['id']);
				unset($document['id']);
			}
			$cursor = $this->connectionDriver->{$this->table}->updateOne($document, $setter);
			return $this->getReturn($cursor, 'update');
		}
		else{
			// INSERT
			$cursor = $this->connectionDriver->{$this->table}->insertOne($document);
			return $this->getReturn($cursor, 'insert');
		}

	}

	/**
	* Execute a query and return fetchAll
	* Use only if JOIN queries are needed.
	*/
	public function query($query){

		if (DEVELOPMENT_ENVIRONMENT){
			print "MongoModel-> query method should not be used!!!!";
			die();
		}
		return FALSE;
	}

	/**
	* Delete an entry from Database relative to ID
	*/
	public function delete($id = NULL){

		if (DEVELOPMENT_ENVIRONMENT){
			print "MongoModel-> delete method should not be used!!!!";
			die();
		}
		return FALSE;
	}
}

?>