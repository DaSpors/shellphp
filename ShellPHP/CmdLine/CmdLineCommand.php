<?php namespace ShellPHP\CmdLine;

class CmdLineCommand extends CmdLineProcessor
{
	public function __construct(CmdLineProcessor $parent, $name)
	{
		$this->parent = $parent;
		$this->name = $name;
	}
	
	public function end() { return $this->parent; }
}
