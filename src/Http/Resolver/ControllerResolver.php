<?php

namespace Xabi\Http\Resolver;

use ReflectionClass;
use ReflectionMethod;
use Xabi\Utils\StringManager as Str;
use Xabi\Http\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
* Controller Resolver
*/
class ControllerResolver {


	/**
	 * If the ...$arg functionality is available.
	 *
	 * Requires at least PHP 5.6.0 or HHVM 3.9.1
	 *
	 * @var bool
	 */
	private $supportsVariadic;

	/**
	 * If scalar types exists.
	 *
	 * @var bool
	 */
	private $supportsScalarTypes;

	/**
	 * If scalar types exists.
	 *
	 * @var bool
	 */
	private $argumentResolver;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger A LoggerInterface instance
	 */
	function __construct() {

		$this->supportsVariadic = method_exists('ReflectionParameter', 'isVariadic');
		$this->supportsScalarTypes = method_exists('ReflectionParameter', 'getType');
	}

	/**
	 * Returns the arguments to pass to the controller.
	 *
	 * @param Request  $request
	 * @param callable $controller
	 *
	 * @return array An array of arguments to pass to the controller
	 *
	 * @throws \RuntimeException When no value could be provided for a required argument
	 */
	public function getArguments(Request $request) {

		// Remove Controller And Method from request
		return array_slice($request->segments(), 2);
	}

	/**
	 * This method looks for a Controller in the URL that represents
	 * the controller name (a string like ClassName::MethodName).
	 */
	public function getController(Request $request) {

		$className = ucfirst(
			Str::lower(
				$request->segment(
					1,
					app()->config('app.defaultController')
				)
			)
		);
		$class	= 	user_namespace() . $className . 'Controller';

		$method	= $request->segment(2, 'index');

		if (!class_exists($class)) {
			throw new NotFoundHttpException(sprintf('Class "%s" does not exist.', $class));
		}

		// Usable defines
		define("CUR_CONTROLLER", $className);
		define("CUR_METHOD", $method);

		$callable = [$this->instantiateController($class), $method];

		if (!is_callable($callable)) {
			throw new NotFoundHttpException(sprintf('The controller for URI "%s" is not callable. %s', $request->getPathInfo(), $this->getControllerError($callable)));
		}


		return $callable;
	}

	/**
	 * Returns an instantiated controller.
	 *
	 * @param string $class A class name
	 *
	 * @return object
	 */
	protected function instantiateController($class) {
		return new $class();
	}

	private function getControllerError($callable) {
		if (is_string($callable)) {
			if (false !== strpos($callable, '::')) {
				$callable = explode('::', $callable);
			}

			if (class_exists($callable) && !method_exists($callable, '__invoke')) {
				return sprintf('Class "%s" does not have a method "__invoke".', $callable);
			}

			if (!function_exists($callable)) {
				return sprintf('Function "%s" does not exist.', $callable);
			}
		}

		if (!is_array($callable)) {
			return sprintf('Invalid type for controller given, expected string or array, got "%s".', gettype($callable));
		}

		if (2 !== count($callable)) {
			return sprintf('Invalid format for controller, expected array(controller, method) or controller::method.');
		}

		list($controller, $method) = $callable;

		if (is_string($controller) && !class_exists($controller)) {
			return sprintf('Class "%s" does not exist.', $controller);
		}

		$className = is_object($controller) ? get_class($controller) : $controller;

		if (method_exists($controller, $method)) {
			return sprintf('Method "%s" on class "%s" should be public and non-abstract.', $method, $className);
		}

		$collection = get_class_methods($controller);

		$alternatives = array();

		foreach ($collection as $item) {
			$lev = levenshtein($method, $item);

			if ($lev <= strlen($method) / 3 || false !== strpos($item, $method)) {
				$alternatives[] = $item;
			}
		}

		asort($alternatives);

		$message = sprintf('Expected method "%s" on class "%s"', $method, $className);

		if (count($alternatives) > 0) {
			$message .= sprintf(', did you mean "%s"?', implode('", "', $alternatives));
		} else {
			$message .= sprintf('. Available methods: "%s".', implode('", "', $collection));
		}

		return $message;
	}
}