<?php

namespace Xabi\Console;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
* Composer
*/
class Composer {

	/**
	 * The working path to regenerate from.
	 *
	 * @var string
	 */
	protected $workingPath;

	/**
	 * Create a new Composer manager instance.
	 *
	 * @param  string|null  $workingPath
	 * @return void
	 */
	public function __construct($workingPath = null) {
		$this->workingPath = $workingPath;
	}

	/**
	 * Regenerate the Composer autoloader files.
	 *
	 * @param  string  $extra
	 * @return void
	 */
	public function dumpAutoloads($extra = '') {
		$process = $this->getProcess();

		$process->setCommandLine(trim($this->findComposer().' dump-autoload '.$extra));

		$process->run(function ($type, $buffer) {
			if (Process::ERR === $type) {
				echo 'ERR > '.$buffer;
			} else {
				echo 'OUT > '.$buffer;
			}
		});

		$process->wait();

		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
		return $process->getOutput();
	}

	/**
	 * Regenerate the optimized Composer autoloader files.
	 *
	 * @return void
	 */
	public function dumpOptimized() {
		return $this->dumpAutoloads('--optimize');
	}

	/**
	 * Get the composer command for the environment.
	 *
	 * @return string
	 */
	protected function findComposer() {
		// if ($this->files->exists($this->workingPath.'/composer.phar')) {
		// 	return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)).' composer.phar';
		// }

		return 'composer';
	}

	/**
	 * Get a new Symfony process instance.
	 *
	 * @return \Symfony\Component\Process\Process
	 */
	protected function getProcess() {
		return (new Process('', $this->workingPath))->setTimeout(null);
	}

	/**
	 * Set the working path used by the class.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function setWorkingPath($path) {
		$this->workingPath = realpath($path);

		return $this;
	}
}