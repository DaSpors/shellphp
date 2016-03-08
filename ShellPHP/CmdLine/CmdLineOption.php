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
	
	protected function setData($cli_args)
	{
		foreach( array_merge(array($this->name),$this->aliases) as $n )
		{
			$i = array_search($n,$cli_args,false);
			if( $i === false )
				continue;
			
			$this->present = true;
			$this->data = array_splice($cli_args,$i+1,1);
			unset($cli_args[$i]);
			return array_values($cli_args);
		}
		$this->present = false;
		return $cli_args;
	}
	
	protected function validate()
	{
		$this->value = trim(implode(" ",$this->data));
		if( !$this->value )
			$this->value = $this->default;
		if( is_null($this->value) )
			$this->err("Missing option '{$this->syntaxName}'");
		
		$this->validateRole();
	}	

	public function syntax($short=true,$eol="")
	{
		if( $short )
		{
			if( $this->required )
				CLI::write("{$this->syntaxName}{$eol}");
			else
				CLI::write("[{$this->syntaxName}]{$eol}");
			return;
		}
		CLI::write("\t{$this->syntaxName}\t(default: {$this->defaultValue})\t{$this->description}{$eol}");
	}
}
