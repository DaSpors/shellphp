<?php namespace ShellPHP\CmdLine;

class CmdLineCommand extends CmdLineProcessor
{
	var $name;
	var $present;
	
	public function __construct(CmdLineProcessor $parent, $name, $present)
	{
		$this->parent = $parent;
		$this->name = $name;
		$this->present = $present;
	}
	
	public function end() { return $this->parent; }
}
