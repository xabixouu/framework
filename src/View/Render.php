<?php

namespace Xabi\View;

use Smarty;
use Xabi\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
* Render
*/
class Render {

	public $response;

	private $bundle;
	private $viewPath;
	private $variables = [];
	private $smarty;

	private $layoutPath;
	private $layoutFile;
	private $viewExt;
	private $request;
	private $controller;

	function __construct($_bundle, $_view_path, $_controller) {
		$this->bundle		= $_bundle;
		$this->viewPath		= $_view_path;
		$this->controller	= $_controller;
		$this->request		= $_controller->request;
		$this->response		= new Response();
		$this->layoutPath	= config('views.layouts');
		$this->layoutFile	= config('views.master');
		$this->viewExt		= config('views.extension');
		$this->smarty		= new Smarty();

		/* Set template engine properties. */
		$this->smarty->setCompileDir(cache_path('Smarty' . DIRECTORY_SEPARATOR . 'templates_c'));
		$this->smarty->setCacheDir(cache_path('Smarty' . DIRECTORY_SEPARATOR . 'cache'));
		$this->smarty->setConfigDir(cache_path('Smarty' . DIRECTORY_SEPARATOR . 'configs'));
	}

	/**
	 * Set views path
	 */
	public function setDisplayLayout($layout) {
		$this->displayLayout = $layout;
	}

	/**
	 * Set views path
	 */
	public function setViewPath($path = '') {
		if ($path != ''){
			$this->viewPath = $path;
		}
	}

	/**
	 * Set views path
	 */
	public function getFullViewPath($view) {
		return $this->viewPath . DIRECTORY_SEPARATOR . $view . $this->viewExt;
	}


	/**
	 * Set views path
	 */
	public function getFullLayoutPath() {
		return $this->layoutPath . DIRECTORY_SEPARATOR . $this->layoutFile . $this->viewExt;
	}

	/**
	 * Display view with layout
	 */
	public function display($view, $datas = []) {
		$this->response		= new Response();
		if (count($datas)){
			$this->variables = array_merge($this->variables, $datas);
		}

		$this->initSmarty($this->getFullViewPath($view));

		$this->response->setContent(
			$this->smarty->fetch($this->getFullLayoutPath())
		);

		return $this->response;
	}
	/**
	 * Fetch view
	 */
	public function fetch($view, $datas = []) {
		$this->response		= new Response();
		if (count($datas)){
			$this->variables = array_merge($this->variables, $datas);
		}

		$this->initSmarty($this->getFullViewPath($view));
		$this->response->setContent(
			$this->smarty->fetch($this->getFullViewPath($view))
		);

		return $this->response;
	}

	/**
	 * Fetch view
	 */
	public function fetchHTML($view, $datas = []) {
		$this->response		= new Response();
		if (count($datas)){
			$this->variables = array_merge($this->variables, $datas);
		}

		$this->initSmarty($this->getFullViewPath($view));

		return $this->smarty->fetch($this->getFullViewPath($view));
	}

	/**
	 * Return json encoded response
	 */
	public function json($datas = [], $status = 200) {
		$this->response		= new Response();

		$this->response->setContent(
			$datas
		);

		$this->response->setStatusCode($status);

		return $this->response;
	}

	/**
	 * Return file response
	 */
	public function file(string $file) {
		$this->response		= new Response();

		$response = new BinaryFileResponse($file);
		$response->trustXSendfileTypeHeader();

		$response->prepare($this->request);

		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			basename($file),
			iconv('UTF-8', 'ASCII//TRANSLIT', basename($file))
		);

		return $response;
	}

	/**
	 * Assign key value to smarty
	 */
	public function assign($key, $value) {
		$this->smarty->assign($key, $value);
	}


	private function initSmarty($viewPath){

		// Set defines to smarty
		$constantsUser = get_defined_constants(true);

		foreach ($constantsUser['user'] as $constant => $value) {
			$this->smarty->assign($constant, $value);
		}

		// Set important variables to smart
		$this->smarty->assign('bundle', $this->bundle);
		$this->smarty->assign('viewPath', $viewPath);
		$this->smarty->assign('HOST', $this->request->root());

		// Assign vars from $this->variables
		$this->smarty->assign($this->variables);

		// Assign messages
		$this->smarty->assign("messageError", @$this->controller->getMessage("messageError"));
		$this->smarty->assign("messageSuccess", @$this->controller->getMessage("messageSuccess"));
		$this->smarty->assign("messageWarning", @$this->controller->getMessage("messageWarning"));
		$this->smarty->assign("messageInfo", @$this->controller->getMessage("messageInfo"));

	}
}
