<?php

namespace Xabi\Databases;

use PDO;

/**
* MySQLModel
*/
class MySQLModel implements ModelInterface{

	protected $connectionDriver;
	public $table;
	protected $tmpTable;
	protected $statement;
	public $builder;
	protected $whereClose;

	/**
	* Init connection driver
	*/
	function __construct($database = NULL) {
		if ($database === NULL){
			if (config('app.debug') == TRUE){
				echo '<pre>';
				print "MySQLModel: Database NULL<br/>";
				echo '</pre>';
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

			if (config('app.debug') == TRUE){
				echo '<pre>';
				print "MySQLModel: Database NULL<br/>";
				echo '</pre>';
				die();
			}
			return FALSE;
		}

		try {
			$this->connectionDriver = new PDO('mysql:host=' . $db['HOST'] . ';dbname=' . $db['BASE'].';charset=utf8', $db['USER'], $db['PASSWORD']);
			$this->connectionDriver->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connectionDriver->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
		}
		catch (\PDOException $e) {

			if (config('app.debug') == TRUE){
				echo '<pre>';
				print "Connection mysql driver error : " . $e->getMessage() . "<br/>";
				echo '</pre>';
				die();
			}
			return FALSE;
		}
		$this->builder = new Builders\WhereBuilder($this);
	}

	/**
	* Set table
	*/
	public function setTable($_table) {
		$this->tmpTable = $this->table;
		$this->table = $_table;
	}

	/**
	* Set table
	*/
	public function getTable() {
		return $this->table;
	}

	/**
	* reset table
	*/
	public function resetTable() {
		$this->table = $this->tmpTable;
	}


	/**
	* Get needed return from $this->statement by $type
	*/
	public function getReturn($_type = "fetchAll") {

		switch ($_type) {
			case 'fetchAll':
				return $this->statement->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'fetch':
				return $this->statement->fetch(PDO::FETCH_ASSOC);
				break;
			case 'insert':
				return $this->connectionDriver->lastInsertId();
				break;
			case 'update':
				return $this->statement->rowCount();
				break;
			case 'delete':
				return $this->statement;
				break;
			case 'none':
				return $this->statement;
				break;
			default:
				return $this->statement->fetchAll(PDO::FETCH_ASSOC);
				break;
		}
	}


	/**
	* Build Where Close
	*/
	private function buildWhereClose($conditions) : void {
		if (!is_array($conditions) AND !($conditions instanceof Builders\WhereBuilder) ){
			$this->whereClose = "WHERE $conditions";
			return;
		}
		$this->whereClose = $this->builder->compileWheres($this->builder);
	}

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    private function bindValues() {
		foreach ($this->builder->bindings as $key => $value) {
			$this->statement->bindValue(
				(is_string($key) ? $key : $key + 1),
				$value,
				(is_int($value) || is_float($value) ? PDO::PARAM_INT : PDO::PARAM_STR)
			);
		}

		// Reset Builder - ready for next statement
		$this->builder->bindings = [];
		$this->builder->wheres = [];
	}


	/**
	* Find in database
	*/
	public function find($params = array()) {

		$conditions	= isset($params["conditions"]) ? ($params["conditions"]) : ("1=1");
		$columns	= isset($params["columns"]) ? ($params["columns"]) : ("*");
		$order		= isset($params["order"]) ? ($params["order"]) : ("1 DESC");
		$limit		= isset($params["limit"]) ? ("LIMIT " . $params["limit"]) : ("");
		$group		= isset($params["group"]) ? ("GROUP BY " . $params["group"]) : ("");

		$this->buildWhereClose($conditions);

		$query	= "SELECT $columns FROM `" . $this->table . "` ".$this->whereClose." $group ORDER BY $order $limit ";
		$this->statement	= $this->connectionDriver->prepare($query);

		if (collect($this->builder->bindings)->count() > 0){
			$this->bindValues();
		}
		try {
			$this->statement->execute();
		}
		catch (\PDOException $e) {

			if (config('app.debug') == TRUE){
				echo "<pre>PDO::errorInfo():<br>";
				print_r($this->statement->errorInfo());
				var_dump($e);
				echo '<br> ' . $query . '</pre><br><br>';
			}
			return FALSE;
		}
		$datas = $this->getReturn("fetchAll");

		$this->statement->closeCursor();
		return $datas;
	}


	/**
	* Find in database by given ID
	*/
	public function findByID($_id, $params = array()) {

		$params['conditions'] = $this->builder->where('id', intval($_id));

		$datas = $this->find($params);
		if ($datas){
			return $datas[0];
		}
		return FALSE;
	}


	/**
	* Find in database by given ID
	*/
	public function findOne($params = array()) {

		$datas = $this->find($params);

		if ($datas){
			return $datas[0];
		}
		return FALSE;
	}


	/**
	* Insert or update data
	*/
	public function save($params, $duplicates = array()) {

		if (isset($params["id"]) && !empty($params["id"]) && intval($params["id"])) {
			$returnType = "update";

			$_id = $params['id'];
			unset($params["id"]);

			// We need to build a list of parameter place-holders of values that are bound
			// to the query. Each insert should have the exact same amount of parameter
			// bindings so we will loop through the record and parameterize them all.
			$sets = collect($params)->map(function ($val, $key) {
				$this->builder->addBinding($val);
				return $this->builder->columnize([$key]).'='.$this->builder->parameterize([$val]);
			})->implode(', ');

			$this->builder->addBinding($_id);

			$query = "UPDATE `" . $this->table . "` SET $sets WHERE `id`= ?";

		}
		else {
			$returnType = "insert";
			unset($params["id"]);

			if (! is_array(reset($params))) {
				$params = [$params];
			}

			$fields = $this->builder->columnize(array_keys(reset($params)));

			// We need to build a list of parameter place-holders of values that are bound
			// to the query. Each insert should have the exact same amount of parameter
			// bindings so we will loop through the record and parameterize them all.
			$values = collect($params)->map(function ($record) {
				$this->builder->addBinding($record);
				return '('.$this->builder->parameterize($record).')';
			})->implode(', ');

			$dup = collect($duplicates)->map(function ($dupli) {
				return $this->builder->columnize([$dupli]) . '=VALUES('.$this->builder->columnize([$dupli]).')';
			})->implode(', ');

			if ($dup != ""){
				$query = "INSERT IGNORE INTO `" . $this->table . "` (".$fields.") VALUES ".$values." ON DUPLICATE KEY UPDATE $dup";
			}
			else{
				$query = "INSERT INTO `" . $this->table . "` (".$fields.") VALUES ".$values."";
			}

		}

		$this->statement = $this->connectionDriver->prepare($query);

		if (collect($this->builder->bindings)->count() > 0){
			$this->bindValues();
		}

		try {
			$this->statement->execute();
		}
		catch (\PDOException $e) {

			if (config('app.debug') == TRUE){
				echo "<pre>PDO::errorInfo():<br>";
				print_r($this->statement->errorInfo());
				var_dump($e);
				echo '<br> ' . $query . '</pre><br><br>';
			}
			return FALSE;

		}

		$datas = $this->getReturn($returnType);
		$this->statement->closeCursor();

		return $datas;
	}


	/**
	* Execute a query and return fetchAll
	* Use only if JOIN queries are needed.
	*/
	public function query($query) {

		$this->statement = $this->connectionDriver->prepare($query);

		if (collect($this->builder->bindings)->count() > 0){
			$this->bindValues();
		}

		try {
			$this->statement->execute();
		}
		catch (\PDOException $e) {

			if (config('app.debug') == TRUE){
				echo "<pre>PDO::errorInfo():<br>";
				print_r($this->statement->errorInfo());
				var_dump($e);
				echo '<br> ' . $query . '</pre><br><br>';
			}
			return FALSE;
		}

		$type = trim(strtoupper(substr($query, 0, strpos($query, " "))));
		switch ($type) {
			case "SELECT":
				$return = "fetchAll";
				break;
			case "INSERT":
				$return = "insert";
				break;
			case "UPDATE":
				$return = "update";
				break;
			case "DELETE":
				$return = "delete";
				break;
			case "SHOW":
				$return = "fetchAll";
				break;
			case "DESCRIBE":
				$return = "fetchAll";
				break;
			default:
				$return = "none";
				break;
		}
		$datas = $this->getReturn($return);
		$this->statement->closeCursor();
		return $datas;
	}

	/**
	 * Delete an entry from Database relative to ID
	 *
	 * @param   int $id
	 * */
	public function delete($id = NULL) {
		if ($id == null) {
			return FALSE;
		}

		$query = "DELETE FROM " . $this->table . " WHERE `id`=?";

		$this->statement = $this->connectionDriver->prepare($query);

		try {

			$this->statement->bindValue(1, $id, PDO::PARAM_INT);
			$this->statement->execute();

		} catch (\PDOException $e) {

			if (config('app.debug') == TRUE){
				echo "<pre>PDO::errorInfo():<br>";
				print_r($this->statement->errorInfo());
				var_dump($e);
				echo '<br> ' . $query . '</pre><br><br>';
			}
			return FALSE;
		}
		$this->statement->closeCursor();
		return TRUE;
	}
}