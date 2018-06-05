<?php

namespace Xabi\Databases;

use Closure;
use Xabi\Database\ModelInterface;

/**
* Where
*/
class WhereBuilder {

	/**
	 * The database connection instance.
	 *
	 * @var \Xabi\Database\Connection
	 */
	public $connection;

	/**
	 * The where constraints for the query.
	 *
	 * @var array
	 */
	public $wheres;

	/**
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	public $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'like binary', 'not like', 'between', 'ilike',
		'&', '|', '^', '<<', '>>',
		'rlike', 'regexp', 'not regexp',
		'~', '~*', '!~', '!~*', 'similar to',
		'not similar to', 'not ilike', '~~*', '!~~*',
	];

	/**
	 * Create a new where builder instance.
	 *
	 * @param  \Xabi\Database\ModelInterface  $connection
	 * @return void
	 */
	public function __construct(ModelInterface $connection) {
		$this->connection = $connection;
	}
}