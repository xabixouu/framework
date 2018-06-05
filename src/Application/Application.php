<?php

namespace Xabi\Application;

use Carbon\Carbon;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Console\Input\ArgvInput;
use Xabi\Application\Loaders\EnvironmentLoader;
use Xabi\Application\Loaders\ConfigLoader;
use Xabi\Application\Loaders\TranslationLoader;
use Xabi\Application\Loaders\SessionLoader;
use Xabi\Application\Encrypters\Encrypter;
use Xabi\Http\Resolver\ControllerResolver;
use Xabi\Utils\StringManager as Str;
use Xabi\Utils\Repository;
use RuntimeException;

class Application extends HttpKernel {

	public $request;
	public $response;
	protected $resolver;
	public $encrypter;
	public $session;

	/**
	 * The framework Application Instance.
	 *
	 * @var string
	 */
	protected static $_instance;

	/**
	 * The framework version.
	 *
	 * @var string
	 */
	const VERSION = '0.1.0';

	/**
	 * The base path for the installation.
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $hasBeenBootstrapped = false;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Usefull Application paths
	 *
	 * @var string
	 */
	protected $paths = [];

	/**
	 * Application configurations
	 *
	 * @var string
	 */
	public $configs = [];

	/**
	 * Application cookies
	 *
	 * @var string
	 */
	public $cookies = [];

	/**
	 * Application locale
	 *
	 * @var string
	 */
	protected $locale;

	/**
	 * Application translations
	 *
	 * @var string
	 */
	public $translations = [];

	/**
	 * The custom environment path defined by the developer.
	 *
	 * @var string
	 */
	protected $environmentPath;

	/**
	 * The environment file to load during bootstrapping.
	 *
	 * @var string
	 */
	protected $environmentFile = '.env';

	/**
	 * The application namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Create a new Xabi application instance.
	 *
	 * @param  string|null  $basePath
	 * @return void
	 */
	public function __construct($basePath = null) {

		if ($basePath) {
			$this->setBasePath($basePath);
		}

		self::$_instance = $this;
	}

	/**
	 * Bootstrap all needed configs.
	 *
	 * @return string
	 */
	public function setup() {

		// Load .env file
		$this->loadEnvironment();

		// Load config files
		$this->loadConfig();

		// Load translations files
		$this->loadTranslations();

		// Load User's Application Namespace
		$this->setNamespace();

		// Set Encrypter
		$this->setEncrypter();

		// Load Session
		$this->loadSession();

		$this->cookies = new Repository([]);

		$this->booted = true;
	}

	/**
	 * Get the application instance.
	 *
	 * @return string
	 */
	public static function getInstance() {
		return self::$_instance;
	}

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version() {
		return static::VERSION;
	}

	/**
	 * Set the environment for the application.
	 *
	 * @return void
	 */
	public function loadEnvironment() {
		(new EnvironmentLoader())->load($this);
	}

	/**
	 * Set the configs for the application.
	 *
	 * @return void
	 */
	public function loadConfig() {
		(new ConfigLoader())->load($this);
	}

	/**
	 * Set the configs for the application.
	 *
	 * @return void
	 */
	public function loadSession() {
		(new SessionLoader())->load($this);
	}

	/**
	 * Set the translations for the application.
	 *
	 * @return void
	 */
	public function loadTranslations() {
		(new TranslationLoader())->load($this);
	}

	/**
	 * Set locale to value
	 * @param string $locale new value
	 */
	public function setLocale($locale = '') {
		if ($locale != ''){
			$this->locale = $locale;
		}
	}

	/**
	 * Get current locale
	 */
	public function getLocale($default = '') {
		return ($this->locale != '' ? $this->locale : $default);
	}

	/**
	 * Set the base path for the application.
	 *
	 * @param  string  $basePath
	 * @return $this
	 */
	public function setBasePath($basePath) {
		$this->basePath = rtrim($basePath, '\/');

		$this->bindPaths();

		return $this;
	}

	/**
	 * Set Encrypter
	 */
	public function setEncrypter() {

		if ($this->config('app.key') == ""){
			// Application key is empty, generating a new one
			$newKey = 'base64:'.base64_encode(random_bytes(
				$this->config('app.cipher') == 'AES-128-CBC' ? 16 : 32
			));
			$escaped = preg_quote('='.$this->config('app.key'), '/');

			$replacePattern = "/^APP_KEY{$escaped}/m";

			file_put_contents(
				$this->environmentFilePath(),
				preg_replace(
					$replacePattern,
					'APP_KEY='.$newKey,
					file_get_contents($this->environmentFilePath())
			));

			// Reload configs
			$this->loadEnvironment();
			$this->loadConfig();
		}
		$config = $this->config('app');

		// If the key starts with "base64:", we will need to decode the key before handing
		// it off to the encrypter. Keys may be base-64 encoded for presentation and we
		// want to make sure to convert them back to the raw bytes before encrypting.
		if (Str::startsWith($key = $this->config('app.key'), 'base64:')) {
			$key = base64_decode(substr($key, 7));
		}

		$this->encrypter = new Encrypter($key, $this->config('app.cipher'));
	}

	/**
	 * Bind all of the application paths
	 *
	 * @return void
	 */
	protected function bindPaths() {
		$this->paths = [
			'root'		=> $this->rootPath(),
			'src'		=> $this->srcPath(),
			'app'		=> $this->appPath(),
			'lang'		=> $this->langPath(),
			'config'	=> $this->configPath(),
			'public'	=> $this->publicPath(),
			'libraries'	=> $this->librairesPath(),
			'cache'		=> $this->cachePath(),
			'bootstrap'	=> $this->bootstrapPath(),
		];
	}

	/**
	 * Get the path to the $key.
	 *
	 * @param string $path 	a path to find
	 * @return string
	 */
	public function path($path = '') {
		if ($path !== '' AND isset($this->paths[$path])){
			return $this->paths[$path];
		}
		return '';
	}

	/**
	 * Get the config to the $key.
	 *
	 * @param string $config 	a config to find
	 * @param string $default 	a default value to return
	 * @return string
	 */
	public function config($config = NULL, $default = '') {
		if (NULL === $config){
			return $this->configs;
		}
		if (is_array($config)) {
			return $this->configs->set($config);
		}

		return $this->configs->get($config, $default);
	}

	/**
	 * Get the lang to the $key.
	 *
	 * @param string $string 	a string to find
	 * @param string $default 	a default value to return
	 * @return string
	 */
	public function lang($translation = NULL, $default = '') {
		if (NULL === $translation){
			return $this->translations;
		}
		if (is_array($translation)) {
			return $this->translations->set($translation);
		}

		$translation = $this->getLocale(config('app.locale')) . '.' . $translation;

		return $this->translations->get($translation, $default);
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param string $path Optionally, a path to append to the app path
	 * @return string
	 */
	public function rootPath($path = '') {
		return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param string $path Optionally, a path to append to the app path
	 * @return string
	 */
	public function appPath($path = '') {
		return $this->rootPath().DIRECTORY_SEPARATOR.'app'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the bootstrap directory.
	 *
	 * @param string $path Optionally, a path to append to the bootstrap path
	 * @return string
	 */
	public function bootstrapPath($path = '') {
		return $this->rootPath().DIRECTORY_SEPARATOR.'bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * @param string $path Optionally, a path to append to the config path
	 * @return string
	 */
	public function configPath($path = '') {
		return $this->rootPath().DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the language files.
	 *
	 * @return string
	 */
	public function langPath($path = '') {
		return $this->appPath().DIRECTORY_SEPARATOR.'langs'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the language files.
	 *
	 * @return string
	 */
	public function srcPath() {
		return $this->appPath().DIRECTORY_SEPARATOR.'src';
	}

	/**
	 * Get the path to the public / web directory.
	 *
	 * @return string
	 */
	public function publicPath() {
		return $this->rootPath().DIRECTORY_SEPARATOR.'public';
	}

	/**
	 * Get the path to the resources directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function librairesPath($path = '') {
		return $this->basePath.DIRECTORY_SEPARATOR.'libraries'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Get the path to the resources directory.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function cachePath($path = '') {
		return $this->basePath.DIRECTORY_SEPARATOR.'cache'.($path ? DIRECTORY_SEPARATOR.$path : $path);
	}

	/**
	 * Set the environment file to be loaded during bootstrapping.
	 *
	 * @param  string  $file
	 * @return $this
	 */
	public function setEnvironmentPath($path) {
		$this->environmentPath = $path;

		return $this;
	}

	/**
	 * Set the environment file to be loaded during bootstrapping.
	 *
	 * @param  string  $file
	 * @return $this
	 */
	public function setEnvironmentFile($file) {
		$this->environmentFile = $file;

		return $this;
	}

	/**
	 * Get the path to the environment file directory.
	 *
	 * @return string
	 */
	public function environmentPath() {
		return $this->environmentPath ?: $this->basePath;
	}

	/**
	 * Get the environment file the application is using.
	 *
	 * @return string
	 */
	public function environmentFile() {
		return $this->environmentFile ?: '.env';
	}

	/**
	 * Get the fully qualified path to the environment file.
	 *
	 * @return string
	 */
	public function environmentFilePath() {
		return $this->environmentPath().'/'.$this->environmentFile();
	}

	/**
	 * Get the application namespace.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Set the application namespace.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	public function setNamespace() {
		if (! is_null($this->namespace)) {
			return $this->namespace;
		}

		$composer = json_decode(file_get_contents(base_path('composer.json')), true);
		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
			foreach ((array) $path as $pathChoice) {
				if (realpath(src_path()) == realpath(base_path().'/'.$pathChoice)) {
					return $this->namespace = $namespace;
				}
			}
		}

		throw new RuntimeException('Unable to detect application namespace.');
	}

	/**
	 * Set a cookie
	 */
	public function setCookie(
		$name,
		$value,
		$minutes = 0,
		$path = null,
		$domain = null,
		$secure = false,
		$httpOnly = null
	){

		$time = ($minutes == 0) ? 0 : Carbon::now()->getTimestamp() + ($minutes * 60);

		$cookie = new Cookie(
			$name,
			$value,
			$time,
			($path !== null ? $path : $this->config('session.path')),
			($domain !== null ? $domain : $this->config('session.cookie_domain') ?: $this->request->getHost()),
			($secure ?: $this->config('session.secure')),
			($httpOnly !== null ? $httpOnly : $this->config('session.http_only', true))
		);
		$this->cookies->set($name, $cookie);
	}

	/**
	 * Implementation of HttpKernel handler
	 */
	public function handle(
		SymfonyRequest $request,
		$type = self::MASTER_REQUEST,
		$catch = true
	) {
		$this->request = $request;
		$this->resolver = new ControllerResolver();

		// load controller
		try {
			$controller = $this->resolver->getController($this->request);
		}
		catch (NotFoundHttpException $e) {
			if ($this->config('app.debug')){
				dd($e);
			}
			header("Location: ".$this->request->root()."/errors");
		}

		// call controller
		$this->response = call_user_func_array($controller, $this->resolver->getArguments($this->request));

		// view
		if (!$this->response instanceof SymfonyResponse) {
			$msg = sprintf('The controller must return a response (%s given).', $this->varToString($this->response));

			// the user may have forgotten to return something
			if (null === $this->response) {
				$msg .= ' Did you forget to add a return statement somewhere in your controller?';
			}
			throw new \LogicException($msg);
		}

		$this->AddQueuedCookiesToResponse();

		$this->response->send();

	}

	/**
	 * Add Queued Cookies To Response
	 */
	private function AddQueuedCookiesToResponse() {

		foreach (($this->cookies->all()) as $name => $cookie) {
			$this->response->headers->setCookie($cookie);
		}

	}

	/**
	 * Translates a variable to typed string
	 * @param  [type] $var
	 * @return string      string
	 */
	private function varToString($var) {
		if (is_object($var)) {
			return sprintf('Object(%s)', get_class($var));
		}

		if (is_array($var)) {
			$a = array();
			foreach ($var as $k => $v) {
				$a[] = sprintf('%s => %s', $k, $this->varToString($v));
			}

			return sprintf('Array(%s)', implode(', ', $a));
		}

		if (is_resource($var)) {
			return sprintf('Resource(%s)', get_resource_type($var));
		}

		if (null === $var) {
			return 'null';
		}

		if (false === $var) {
			return 'false';
		}

		if (true === $var) {
			return 'true';
		}

		return (string) $var;
	}
}