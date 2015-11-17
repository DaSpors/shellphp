<?php namespace ShellPHP\CmdLine;

class CmdLineOption extends CmdLineData
{
	var $name;
	var $value;
	var $default;
	var $required;
	
	public function __construct(CmdLineProcessor $parent, $name, $default)
	{
		$this->parent = $parent;
		$this->name = $name;
		$this->default = $default;
		$this->required = is_null($default);
	}
	
	protected function validate()
	{
		$this->value = trim(implode(" ",$this->data));
		if( !$this->value )
			$this->value = $this->default;
		if( !$this->value )
			$this->err("Missing option '{$this->name}'");
	}	
}
