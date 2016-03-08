<?php namespace ShellPHP\CmdLine;

class CmdLineArgument extends CmdLineData
{
	var $default;
	var $required;
	var $repeat;

	public function __construct(CmdLineProcessor $parent, $varname, $default, $repeat = false)
	{
		$this->parent = $parent;
		$this->default = $default;
		$this->required = is_null($default);
		$this->repeat = $repeat;
		$this->name = $varname;
		$this->map($varname);
	}
	
	protected function setData($cli_args)
	{
		if( $this->repeat )
			$this->data = array_splice($cli_args,0);
		else
			$this->data = array_splice($cli_args,0,1);
		return array_values($cli_args);
	}

	protected function validate()
	{
		$this->value = trim(implode(" ",$this->data));
		if( !$this->value )
			$this->value = $this->default;
		if( is_null($this->value) )
			$this->err("Missing argument '{$this->syntaxName}'");
		
		$this->validateRole();
	}

	public function syntax($short=true,$eol="")
	{
		if( $short )
		{
			if( $this->required )
				CLI::write("<{$this->syntaxName}>{$eol}");
			else
				CLI::write("[<{$this->syntaxName}>]{$eol}");
			return;
		}
		CLI::write("\t{$this->syntaxName}\t({$this->requiredValue})\t{$this->description}{$eol}");
	}
}
