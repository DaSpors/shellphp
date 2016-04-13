<?php

include('shellphp.php');

$cli = \ShellPHP\CmdLine\CmdLine::Make("ShellPHP Test Script","Version 0.0.0.2")
	->command('list')
		->opt('-f','none')->map('filter')
		->arrayArg('folder')
		->handler(function($args)
		{
			extract($args);
			var_dump($args);
			var_dump(get_defined_vars());
			echo "\n --> $filter $folder\n\n";
		})
		->end()
	->command('add')
		->opt('-p')->map('path')->text('Local folder to scan')
		->handler(function($args)
		{
			extract($args);
			echo "Add --> $path\n\n";
		})
		->end()
	->command('remove')
		->opt('-f')
	->go();

