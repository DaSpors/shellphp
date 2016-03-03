<?php namespace ShellPHP\CmdLine;

class CmdLineOption extends CmdLineData
{
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
		if( is_null($this->value) )
			$this->err("Missing option '{$this->name}'");
	}	

	public function syntax()
	{
		if( $this->required )
			echo "{this->syntaxName}";
		else
			echo "[{this->syntaxName}]";
	}
}
