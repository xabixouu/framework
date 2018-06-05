<?php

namespace Xabi\Application;

use Xabi\View\Render;
use Xabi\Http\Request;
use Xabi\Databases\EntityManager;
use BadMethodCallException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class Controller {


	protected $bundle		= NULL;

	private $render;
	public $request;

	public $EM;


	// Controller constructor
	public function __construct($_bundle) {
		$this->bundle = $_bundle;
		$this->request = app()->request;
		$this->render = new Render(
			$this->bundle,
			src_path($this->bundle . DIRECTORY_SEPARATOR . 'views'),
			$this
		);


		if (isset($_POST)) {
			$this->postData = $this->request->request->all();
		}

		if (isset($_GET)) {
			$this->getData = $this->request->query->all();
		}

		if (isset($_FILES)) {
			$this->fileData = $_FILES;
		}

		if (isset($_COOKIE)) {
			$this->cookie = $this->request->cookies;
		}
	}

	// Init an instance of entityManager
	public function setEntityManager($_database = NULL) {

		if ($this->EM === NULL){
			$this->EM = (new EntityManager($this->bundle, $_database))->Model();
		}
		return $this->EM;
	}

	/**
	 * Set application locale
	 */
	public function setLocale($locale) {
		return app()->setLocale($locale);
	}

	/**
	 * Set view path.
	 */
	public function setViewPath($path = '') {
		$this->render->setViewPath(base_path($path));
	}

	/**
	 * Render a view.
	 */
	public function display($view, $datas = []) {

		return $this->render->display($view, $datas);
	}

	/**
	 * Fetch a view.
	 */
	public function fetch($view, $datas = []) {

		return $this->render->fetch($view, $datas);
	}

	/**
	 * Fetch a template only.
	 */
	public function fetchHTML($view, $datas = []) {

		return $this->render->fetchHTML($view, $datas);
	}

	/**
	 * Response JSON.
	 */
	public function json($datas = []) {

		return $this->render->json($datas);
	}

	/**
	 * Download a file.
	 */
	public function file($file) {

		return $this->render->file($file);
	}

	/**
	*	Redirect to a controller/method
	*/
	public function redirect($path, $params = [], $forced_redirect = FALSE) {

		$statusCode	= 301;
		$scheme	= $this->request->getScheme();

		$url = $scheme.'://'.
				$this->request->getHost().'/'.
				$path.'/'.
				join('/', $params);


		if ($forced_redirect){
			header("Location: ".$url);
			die();
		}

		$res = new RedirectResponse($url, $statusCode);

		foreach (app()->cookies->all() as $name => $cookie) {
			$res->headers->setCookie($cookie);
		}
		return $res;
	}

	/**
	 * Set message in session
	 * @param string $key   Key in session
	 * @param string $value Value of key in session
	 */
	public function setMessage($key, $value) {
		session([
			$key => $value
		]);
	}

	/**
	 * Get message in session and unset it
	 * @param  string $key Key in session
	 * @return string      Value of key in session
	 */
	public function getMessage($key){
		return session($key, 'toRemove', TRUE);
	}

	/**
	 * Assign a key/value to the renderer
	 */
	public function assign($key = null, $value = null){

		if (null !== $key AND null !== $value){
			return $this->render->assign($key, $value);
		}
		return FALSE;
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function callAction($method, $parameters) {
		return call_user_func_array([$this, $method], $parameters);
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function missingMethod($parameters = []) {
		throw new NotFoundHttpException('Controller method not found.');
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters) {
		throw new BadMethodCallException("Method [{$method}] does not exist.");
	}
}
