<?php namespace ShellPHP\CmdLine;

class CmdLineFlag extends CmdLineData
{
	var $default;
	var $present;
	
	public function __construct(CmdLineProcessor $parent, $name, $default)
	{
		$this->parent = $parent;
		$this->name = $name;
		$this->default = $default;
	}
	
	protected function setData($cli_args)
	{
		$this->present = false;
		foreach( array_merge(array($this->name),$this->aliases) as $n )
		{
			$i = array_search($n,$cli_args,false);
			if( $i === false )
				continue;
			
			$this->present = true;
			unset($cli_args[$i]);
		}
		return array_values($cli_args);
	}
	
	protected function validate()
	{
		$this->value = $this->present?true:$this->default;
	}	

	public function syntax($short = true,$eol="")
	{
		if( $short )
			CLI::write("[{$this->syntaxName}]{$eol}");
		else
			CLI::write("\t{$this->syntaxName}\t(default: {$this->defaultValue})\t{$this->description}{$eol}");
	}
}
