<?php namespace ShellPHP\CmdLine;

class CmdLineFlag extends CmdLineData
{
	var $default;
	var $present;
	
	public function __construct(CmdLineProcessor $parent, $name, $default,$present)
	{
		$this->parent = $parent;
		$this->name = $name;
		$this->default = $default;
		$this->present = $present;
	}
	
	protected function validate()
	{
		$this->value = $this->present?true:$this->default;
	}	

	public function syntax()
	{
		echo "[{$this->syntaxName}]";
	}
}
