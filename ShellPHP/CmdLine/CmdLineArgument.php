<?php namespace ShellPHP\CmdLine;

class CmdLineArgument extends CmdLineData
{
	var $count1;
	var $count2;
	var $default;
	var $required;

	public function __construct(CmdLineProcessor $parent, $varname, $default, $count1, $count2=false)
	{
		$this->parent = $parent;
		$this->count1 = $count1;
		$this->count2 = $count2;
		$this->default = $default;
		$this->required = is_null($default);
		$this->map($varname);
	}

	protected function validate()
	{
		if( count($this->data) < $this->count1 )
			$this->err("Missing argument '{$this->varname}'");
	
		$cnt = $this->count2 === false?$this->count1:$this->count2;

		if( count($this->data) < $cnt )
			$this->err("Missing argument '{$this->varname}'");

		if( count($this->data) > $cnt )
			$this->err("Useless argument '{$this->varname}'");
		
		$this->value = $this->count==1?$this->data[0]:$this->data;
	}	
}
