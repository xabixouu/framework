<?php

namespace Xabi\Databases;


interface ModelInterface{

	/**
	 * Connect to the given database
	 * @param  array  $db 		Database connection informations
	 * @return Connection      	Database connection
	 */
	public function connect($db = NULL);

	/**
	* Set table
	*/
	public function setTable($_table);

	/**
	* reset table
	*/
	public function resetTable();

	/**
	* Get needed return from $stmt by $type
	*/
	public function getReturn($_type = "fetchAll");

	/**
	* Find in database
	*/
	public function find($params = array());

	/**
	* Find in database by given ID
	*/
	public function findByID($_id, $params = array());

	/**
	* Find in database by given parameters
	*/
	public function findOne($params = array());

	/**
	* Insert or update data
	*/
	public function save($params, $duplicates = array());

	/**
	* Execute a query and return fetchAll
	* Use only if JOIN queries are needed.
	*/
	public function query($query);

	/**
	* Delete an entry from Database relative to ID
	*/
	public function delete($id = NULL);
}

?>