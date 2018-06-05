<?php

namespace Xabi\Databases\Builders;

use Closure;
use Xabi\Database\ModelInterface;
use Xabi\Utils\ArrayManager as Arr;

/**
* Where
*/
class WhereBuilder {

	/**
	 * The database connection instance.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	public $connection;

	/**
	 * The current query value bindings.
	 *
	 * @var array
	 */
	public $bindings = [];

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
	public function __construct($connection) {
		$this->connection = $connection;
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param  string|array|\Closure  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and') {
		// If the column is an array, we will assume it is an array of key-value pairs
		// and can add them each as a where clause. We will maintain the boolean we
		// received when the method was called and pass it into the nested where.
		if (is_array($column)) {
			return $this->addArrayOfWheres($column, $boolean);
		}

		// Here we will make some assumptions about the operator. If only 2 values are
		// passed to the method, we will assume that the operator is an equals sign
		// and keep going. Otherwise, we'll require the operator to be passed in.
		list($value, $operator) = $this->prepareValueAndOperator(
			$value, $operator, func_num_args() == 2
		);

		// If the columns is actually a Closure instance, we will assume the developer
		// wants to begin a nested where statement which is wrapped in parenthesis.
		// We'll add that Closure to the query then return back out immediately.
		if ($column instanceof Closure) {
			return $this->whereNested($column, $boolean);
		}

		// If the given operator is not found in the list of valid operators we will
		// assume that the developer is just short-cutting the '=' operators and
		// we will set the operators to '=' and set the values appropriately.
		if ($this->invalidOperator($operator)) {
			list($value, $operator) = [$operator, '='];
		}

		// If the value is "null", we will just assume the developer wants to add a
		// where null clause to the query. So, we will allow a short-cut here to
		// that method for convenience so the developer doesn't have to check.
		if (is_null($value)) {
			return $this->whereNull($column, $boolean, $operator != '=');
		}

		// Now that we are working with just a simple query we can put the elements
		// in our array and add the query binding to our array of bindings that
		// will be bound to each SQL statements when it is finally executed.
		$type = 'Basic';

		$this->wheres[] = compact(
			'type', 'column', 'operator', 'value', 'boolean'
		);

		$this->addBinding($value);

		return $this;
	}

	/**
	 * Add an array of where clauses to the query.
	 *
	 * @param  array  $column
	 * @param  string  $boolean
	 * @param  string  $method
	 * @return $this
	 */
	protected function addArrayOfWheres($column, $boolean, $method = 'where') {
		return $this->whereNested(function ($query) use ($column, $method) {
			foreach ($column as $key => $value) {
				if (is_numeric($key) && is_array($value)) {
					$query->{$method}(...array_values($value));
				} else {
					$query->$method($key, '=', $value);
				}
			}
		}, $boolean);
	}

	/**
	 * Add a nested where statement to the query.
	 *
	 * @param  \Closure $callback
	 * @param  string   $boolean
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function whereNested(Closure $callback, $boolean = 'and') {
		call_user_func($callback, $query = $this->forNestedWhere());

		return $this->addNestedWhereQuery($query, $boolean);
	}

	/**
	 * Create a builder query instance for nested where condition.
	 *
	 * @return \Xabi\Database\Builders\WhereBuilder
	 */
	public function forNestedWhere() {
		return $this->newQuery();
	}

	/**
	 * Add another query builder as a nested where to the query builder.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder|static $query
	 * @param  string  $boolean
	 * @return $this
	 */
	public function addNestedWhereQuery($query, $boolean = 'and') {
		if (count($query->wheres)) {
			$type = 'Nested';

			$this->wheres[] = compact('type', 'query', 'boolean');

			$this->addBinding($query->getBindings(), 'where');
		}

		return $this;
	}

	/**
	 * Get the current query value bindings in a flattened array.
	 *
	 * @return array
	 */
	public function getBindings() {
		return Arr::flatten($this->bindings);
	}

	/**
	 * Add an "or where" clause to the query.
	 *
	 * @param  \Closure|string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhere($column, $operator = null, $value = null) {
		return $this->where($column, $operator, $value, 'or');
	}

	/**
	 * Add a "where" clause comparing two columns to the query.
	 *
	 * @param  string|array  $first
	 * @param  string|null  $operator
	 * @param  string|null  $second
	 * @param  string|null  $boolean
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function whereColumn($first, $operator = null, $second = null, $boolean = 'and') {
		// If the column is an array, we will assume it is an array of key-value pairs
		// and can add them each as a where clause. We will maintain the boolean we
		// received when the method was called and pass it into the nested where.
		if (is_array($first)) {
			return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
		}

		// If the given operator is not found in the list of valid operators we will
		// assume that the developer is just short-cutting the '=' operators and
		// we will set the operators to '=' and set the values appropriately.
		if ($this->invalidOperator($operator)) {
			list($second, $operator) = [$operator, '='];
		}

		// Finally, we will add this where clause into this array of clauses that we
		// are building for the query. All of them will be compiled via a grammar
		// once the query is about to be executed and run against the database.
		$type = 'Column';

		$this->wheres[] = compact(
			'type', 'first', 'operator', 'second', 'boolean'
		);

		return $this;
	}

	/**
	 * Add an "or where" clause comparing two columns to the query.
	 *
	 * @param  string|array  $first
	 * @param  string|null  $operator
	 * @param  string|null  $second
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereColumn($first, $operator = null, $second = null) {
		return $this->whereColumn($first, $operator, $second, 'or');
	}

	/**
	 * Add a raw where clause to the query.
	 *
	 * @param  string  $sql
	 * @param  mixed   $bindings
	 * @param  string  $boolean
	 * @return $this
	 */
	public function whereRaw($sql, $bindings = [], $boolean = 'and') {
		$this->wheres[] = ['type' => 'Raw', 'sql' => $sql, 'boolean' => $boolean];

		$this->addBinding((array) $bindings, 'where');

		return $this;
	}

	/**
	 * Add a raw or where clause to the query.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereRaw($sql, array $bindings = []) {
		return $this->whereRaw($sql, $bindings, 'or');
	}

	/**
	 * Add a "where in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  mixed   $values
	 * @param  string  $boolean
	 * @param  bool    $not
	 * @return $this
	 */
	public function whereIn($column, $values, $boolean = 'and', $not = false) {
		$type = $not ? 'NotIn' : 'In';

		// If the value is a query builder instance we will assume the developer wants to
		// look for any values that exists within this given query. So we will add the
		// query accordingly so that this query is properly executed when it is run.
		if ($values instanceof static) {
			return $this->whereInExistingQuery(
				$column, $values, $boolean, $not
			);
		}

		// If the value of the where in clause is actually a Closure, we will assume that
		// the developer is using a full sub-select for this "in" statement, and will
		// execute those Closures, then we can re-construct the entire sub-selects.
		if ($values instanceof Closure) {
			return $this->whereInSub($column, $values, $boolean, $not);
		}

		// Next, if the value is Arrayable we need to cast it to its raw array form so we
		// have the underlying array value instead of an Arrayable object which is not
		// able to be added as a binding, etc. We will then add to the wheres array.
		if ($values instanceof Arrayable) {
			$values = $values->toArray();
		}

		$this->wheres[] = compact('type', 'column', 'values', 'boolean');

		// Finally we'll add a binding for each values unless that value is an expression
		// in which case we will just skip over it since it will be the query as a raw
		// string and not as a parameterized place-holder to be replaced by the PDO.
		foreach ($values as $value) {
			if (! $value instanceof Expression) {
				$this->addBinding($value, 'where');
			}
		}

		return $this;
	}

	/**
	 * Add an "or where in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  mixed   $values
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereIn($column, $values) {
		return $this->whereIn($column, $values, 'or');
	}

	/**
	 * Add a "where not in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  mixed   $values
	 * @param  string  $boolean
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function whereNotIn($column, $values, $boolean = 'and') {
		return $this->whereIn($column, $values, $boolean, true);
	}

	/**
	 * Add an "or where not in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  mixed   $values
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereNotIn($column, $values) {
		return $this->whereNotIn($column, $values, 'or');
	}

	/**
	 * Add a "where null" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $boolean
	 * @param  bool    $not
	 * @return $this
	 */
	public function whereNull($column, $boolean = 'and', $not = false) {
		$type = $not ? 'NotNull' : 'Null';

		$this->wheres[] = compact('type', 'column', 'boolean');

		return $this;
	}

	/**
	 * Add an "or where null" clause to the query.
	 *
	 * @param  string  $column
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereNull($column) {
		return $this->whereNull($column, 'or');
	}

	/**
	 * Add a "where not null" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $boolean
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function whereNotNull($column, $boolean = 'and') {
		return $this->whereNull($column, $boolean, true);
	}

	/**
	 * Add an "or where not null" clause to the query.
	 *
	 * @param  string  $column
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereNotNull($column) {
		return $this->whereNotNull($column, 'or');
	}

	/**
	 * Add a where between statement to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $boolean
	 * @param  bool  $not
	 * @return $this
	 */
	public function whereBetween($column, array $values, $boolean = 'and', $not = false) {
		$type = 'between';

		$this->wheres[] = compact('column', 'type', 'boolean', 'not');

		$this->addBinding($values, 'where');

		return $this;
	}

	/**
	 * Add an or where between statement to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereBetween($column, array $values) {
		return $this->whereBetween($column, $values, 'or');
	}

	/**
	 * Add a where not between statement to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $boolean
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function whereNotBetween($column, array $values, $boolean = 'and') {
		return $this->whereBetween($column, $values, $boolean, true);
	}

	/**
	 * Add an or where not between statement to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @return \Xabi\Database\Builders\WhereBuilder|static
	 */
	public function orWhereNotBetween($column, array $values) {
		return $this->whereNotBetween($column, $values, 'or');
	}




	/**
	 * Prepare the value and operator for a where clause.
	 *
	 * @param  string  $value
	 * @param  string  $operator
	 * @param  bool  $useDefault
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function prepareValueAndOperator($value, $operator, $useDefault = false) {
		if ($useDefault) {
			return [$operator, '='];
		} elseif ($this->invalidOperatorAndValue($operator, $value)) {
			throw new InvalidArgumentException('Illegal operator and value combination.');
		}

		return [$value, $operator];
	}

	/**
	 * Determine if the given operator and value combination is legal.
	 *
	 * Prevents using Null values with invalid operators.
	 *
	 * @param  string  $operator
	 * @param  mixed  $value
	 * @return bool
	 */
	protected function invalidOperatorAndValue($operator, $value) {
		return is_null($value) && in_array($operator, $this->operators) &&
			 ! in_array($operator, ['=', '<>', '!=']);
	}

	/**
	 * Determine if the given operator is supported.
	 *
	 * @param  string  $operator
	 * @return bool
	 */
	protected function invalidOperator($operator) {
		return ! in_array(strtolower($operator), $this->operators, true);
	}

	/**
	 * Add a binding to the query.
	 *
	 * @param  mixed   $value
	 * @param  string  $type
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function addBinding($value) {

		if (is_array($value)) {
			$this->bindings = array_values(array_merge($this->bindings, $value));
		}
		else {
			$this->bindings[] = $value;
		}

		return $this;
	}

	/**
	 * Get a new instance of the query builder.
	 *
	 * @return \Xabi\Database\Builders\WhereBuilder
	 */
	public function newQuery() {
		return new static($this->connection);
	}


	/**
	 * TO STRING METHODS
	 */



	/**
	 * Compile the "where" portions of the query.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @return string
	 */
	public function compileWheres(WhereBuilder $builder) {

		// Each type of where clauses has its own compiler function which is responsible
		// for actually creating the where clauses SQL. This helps keep the code nice
		// and maintainable since each clause has a very small method that it uses.
		if (is_null($builder->wheres)) {
			return '';
		}

		// If we actually have some where clauses, we will strip off the first boolean
		// operator, which is added by the query builders for convenience so we can
		// avoid checking for the first clauses in each of the compilers methods.
		if (count($sql = $this->compileWheresToArray($builder)) > 0) {
			return $this->concatenateWhereClauses($builder, $sql);
		}

		return '';
	}

	/**
	 * Get an array of all the where clauses for the query.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @return array
	 */
	protected function compileWheresToArray(WhereBuilder $builder) {
		$allWheres = collect($builder->wheres)->map(function ($where) use ($builder) {
			return $where['boolean'].' '.$this->{"compileWhere{$where['type']}"}($builder, $where);
		})->all();

		// Clean wheres
		$builder->wheres = [];
		return $allWheres;
	}

	/**
	 * Format the where clause statements into one string.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $sql
	 * @return string
	 */
	protected function concatenateWhereClauses(WhereBuilder $builder, $sql) {

		return 'WHERE '.$this->removeLeadingBoolean(implode(' ', $sql));
	}

	/**
	 * Remove the leading boolean from a statement.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function removeLeadingBoolean($value) {
		return preg_replace('/and |or /i', '', $value, 1);
	}


	/**
	 * Compile a nested where clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereNested(WhereBuilder $builder, $where) {
		$offset = 6;

		return '('.substr($this->compileWheres($where['query']), $offset).')';
	}


	/**
	 * Compile a basic where clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereBasic(WhereBuilder $builder, $where) {
		$value = $this->parameter($where['value']);

		return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
	}



	/**
	 * Get the appropriate query parameter place-holder for a value.
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	public function parameter($value) {
		return '?';
	}

	/**
	 * Convert an array of column names into a delimited string.
	 *
	 * @param  array   $columns
	 * @return string
	 */
	public function columnize(array $columns) {
		return implode(', ', array_map([$this, 'wrap'], $columns));
	}

	/**
	 * Create query parameter place-holders for an array.
	 *
	 * @param  array   $values
	 * @return string
	 */
	public function parameterize(array $values) {
		return implode(', ', array_map([$this, 'parameter'], $values));
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrap($value) {
		return $this->wrapSegments(explode('.', $value));
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  array  $segments
	 * @return string
	 */
	protected function wrapSegments($segments) {
		return collect($segments)->map(function ($segment, $key) use ($segments) {
			return
				$key == 0 ? $this->wrapTable($segment) : $this->wrapValue($segment);
		})->implode('.');
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrapValue($value) {
		if ($value !== '*') {
			return '"'.str_replace('"', '""', $value).'"';
		}

		return $value;
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrapTable($value) {
		if ($value !== '*') {
			return '`'.str_replace('"', '""', $value).'`';
		}

		return $value;
	}

	/**
	 * Compile a "where in" clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereIn(WhereBuilder $query, $where) {
		if (! empty($where['values'])) {
			return $this->wrap($where['column']).' in ('.$this->parameterize($where['values']).')';
		}

		return '0 = 1';
	}

	/**
	 * Compile a "where not in" clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereNotIn(WhereBuilder $query, $where) {
		if (! empty($where['values'])) {
			return $this->wrap($where['column']).' not in ('.$this->parameterize($where['values']).')';
		}

		return '1 = 1';
	}

	/**
	 * Compile a where in sub-select clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereInSub(WhereBuilder $query, $where) {
		return $this->wrap($where['column']).' in ('.$this->compileSelect($where['query']).')';
	}

	/**
	 * Compile a where not in sub-select clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereNotInSub(WhereBuilder $query, $where) {
		return $this->wrap($where['column']).' not in ('.$this->compileSelect($where['query']).')';
	}

	/**
	 * Compile a "where null" clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereNull(WhereBuilder $query, $where) {
		return $this->wrap($where['column']).' is null';
	}

	/**
	 * Compile a "where not null" clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereNotNull(WhereBuilder $query, $where) {
		return $this->wrap($where['column']).' is not null';
	}

	/**
	 * Compile a raw where clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereRaw(WhereBuilder $query, $where) {
		return $where['sql'];
	}

	/**
	 * Compile a "between" where clause.
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereBetween(WhereBuilder $query, $where) {
		$between = $where['not'] ? 'not between' : 'between';

		return $this->wrap($where['column']).' '.$between.' ? and ?';
	}

	/**
	 * Compile a where clause comparing two columns..
	 *
	 * @param  \Xabi\Database\Builders\WhereBuilder  $builder
	 * @param  array  $where
	 * @return string
	 */
	protected function compileWhereColumn(WhereBuilder $query, $where) {
		return $this->wrap($where['first']).' '.$where['operator'].' '.$this->wrap($where['second']);
	}

}