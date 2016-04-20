ShellPHP
========
ShellPHP helps you bring the power and ease of installation of PHP to the command line.    
It is meant to provide a toolbox for developers that waht or need to develop programs in PHP that
will run in bash, not in WebServer environment.    

Feature overview
----------------
* Commandline argument handling
* Process management
* High level (Sqlite-based) data storage
* WebInterface (experimental)

\ShellPHP\CmdLine
=================
The commandline handler makes it easy to define flags, options and arguments and even provides the ability
to create commands that split your application into logical parts (like apt-get and many others do).    

```php
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
		->opt('-p')->map('path')->text('Local folder to add')
		->handler(function($args)
		{
			extract($args);
			echo "Add --> $path\n\n";
		})
		->end();
$cli->command('remove')->opt('-f');
$cli->go();
```

\ShellPHP\Process
=================
This is a class that helps you enumerate, find and run processes.

\ShellPHP\Storage
=================
Really powerful yet easy to use Storage. Every application needs to store some data somewhere every now and them.    
The Storage class provides a high level interface so that in most cases you wont need to perform any SQL queries.
```php
class MyModel extends \ShellPHP\Storage\StoredObject
{
	public $id = 'int primary autoinc';
	public $name = 'text unique';
	public $desc = 'text';
}
\ShellPHP\Storage\Storage::Make('my_app_database.sqlite');

$obj1 = MyModel::Make(array('name'=>'obj1'));
$obj1->Save();
MyModel::Make()->set('name','obj2')->Save();

var_dump(MyModel::Select()->like('name','obj%')->results());
$obj1->Delete();
var_dump(MyModel::Select()->results());
```

\ShellPHP\WebInterface
======================
There's a currently experimental feature to provide you with a webinterface.    
```php
$webinterface = \ShellPHP\WebInterface\WebInterface::Make()
	->index(__DIR__."/web")
	->handler('list',function($request){ return \ShellPHP\WebInterface\WebResponse::Json(array('hello'=>'world')); } )
	->go();
```