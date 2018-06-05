<?php

namespace Xabi\Databases;

/**
* ModelTrait
*/
trait ModelTrait {

	/*--------------------------------------------------|
	| !!        DO NOT MODIFY BELOW THIS LINE        !! |
	|--------------------------------------------------*/
		protected $_instance;
		public $builder;

		/**
		 * Decorator Constructor
		 *
		 * - Init Driver instance into local protected variable
		 * - Set table to correct name
		 *
		 * @param Class $instance Instance of 'Driver'Model class
		 */
		public function __construct($instance) {
			if ($instance === FALSE){
				if (config('app.debug')){
					print "Could not init Model Interface Connection";
					die();
				}
				return FALSE;
			}
			$this->_instance = $instance;
			$this->builder = $this->_instance->builder;
		}

		/**
		 * __call Override
		 *
		 * Allows to call 'Driver'Model methods
		 *
		 * @param  string $method 	Method called
		 * @param  array $args   	Array of parameters for called method
		 * @return callback         Whatever Called internal method returns
		 */
		public function __call($method, $args) {
			return call_user_func_array(array($this->_instance, $method), $args);
		}


}