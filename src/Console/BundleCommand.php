<?php

namespace Xabi\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xabi\Console\Composer;

/**
* BundleCommand
*/
class BundleCommand extends Command {

	protected function configure() {
		$this->setName("bundle:create")
			->setDescription("Create a Bundle")
			->setDefinition([
				new InputArgument(
					'bundleName',
					InputArgument::REQUIRED,
					'Bundle name you want to create',
					null
				),
			])
			->setHelp("The <info>bundle</info> command handles Bundles");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

        $io = new SymfonyStyle($input, $output);

		if ($io->confirm(
			'Do you have a Middleware controller in your project ? /src/Controller.php - Namespace App\\',
			false
		)){
			$studNamespace = 'App\Controller as AppController';
			$studControllerName = 'AppController';
		}
		else{
			$studNamespace = 'Xabi\Application\Controller';
			$studControllerName = 'Controller';
		}

		$name = ucfirst(strtolower($input->getArgument('bundleName')));

        $io->title(sprintf('Generating a new bundle "%s"', $name));

		// $io->section('Checking for existing folder');
		$path = app_path('src'.DIRECTORY_SEPARATOR.$name);
		if (file_exists($path)){
			$io->error(sprintf("The bundle %s already exists", $name));
			die();
			// throw new \Exception(sprintf("The bundle %s already exists", $name), 1);
		}

		$io->text('Creating elements');
		$io->listing([
			app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$name.'Controller.php'),
			app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$name.'Model.php'),
			app_path(
				'src'.
				DIRECTORY_SEPARATOR.
				$name.
				DIRECTORY_SEPARATOR.
				'views'.
				DIRECTORY_SEPARATOR.
				'listing'.
				DIRECTORY_SEPARATOR.
				'main.tpl'
			)
		]);
		@mkdir(app_path('src'.DIRECTORY_SEPARATOR.$name));
		@mkdir(app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'views'));
		@mkdir(app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'listing'));
		@mkdir(public_path('js'.DIRECTORY_SEPARATOR.strtolower($name)));

		// Create Controller
		file_put_contents(
			app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$name.'Controller.php'),
			str_replace(
				[
					"DummyController",
					"DummyNamespace",
					"DummyName",
					"DummyInclude",
					"DummyCName",
					"DummyLower"
				], [
					$name.'Controller',
					trim(user_namespace(), '\\'),
					$name,
					$studNamespace,
					$studControllerName,
					strtolower($name)
				],
				file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'controller.stud')
			)
		);

		// Create Model
		file_put_contents(
			app_path('src'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$name.'Model.php'),
			str_replace(
				[
					"DummyModel",
					"DummyNamespace",
					"DummyName"
				], [
					$name.'Model',
					trim(user_namespace(), '\\'),
					strtolower($name)
				],
				file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'model.stud')
			)
		);

		// Create main.tpl
		file_put_contents(
			app_path(
				'src'.
				DIRECTORY_SEPARATOR.
				$name.
				DIRECTORY_SEPARATOR.
				'views'.
				DIRECTORY_SEPARATOR.
				'listing'.
				DIRECTORY_SEPARATOR.
				'main.tpl'
			),
			str_replace(
				[
					"BUNDLENAME",
					"bundlename"
				], [
					$name,
					strtolower($name)
				],
				file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'main.stud')
			)
		);

		// Create tableBody.tpl
		file_put_contents(
			app_path(
				'src'.
				DIRECTORY_SEPARATOR.
				$name.
				DIRECTORY_SEPARATOR.
				'views'.
				DIRECTORY_SEPARATOR.
				'listing'.
				DIRECTORY_SEPARATOR.
				'tableBody.tpl'
			),
			file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'tableBody.stud')
		);

		// Create createModal.tpl
		file_put_contents(
			app_path(
				'src'.
				DIRECTORY_SEPARATOR.
				$name.
				DIRECTORY_SEPARATOR.
				'views'.
				DIRECTORY_SEPARATOR.
				'listing'.
				DIRECTORY_SEPARATOR.
				'createModal.tpl'
			),
			file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'createModal.stud')
		);

		// Create view.js
		file_put_contents(
			public_path(
				'js'.
				DIRECTORY_SEPARATOR.
				strtolower($name).
				DIRECTORY_SEPARATOR.
				'view.js'
			),
			str_replace(
				[
					"BUNDLENAME",
					"bundlename"
				], [
					$name,
					strtolower($name)
				],
				file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'studs'.DIRECTORY_SEPARATOR.'js.stud')
			)
		);
		$io->success('All files created');
		$io->newLine();


		$io->section('Updating Composer.');
		$composer = new Composer(base_path());
		$res = $composer->dumpAutoloads();
		$io->text($res);
		$io->success('Composer updated');
	}
}
