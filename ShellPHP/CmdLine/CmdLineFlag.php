<?php namespace ShellPHP\CmdLine;

class CmdLineFlag extends CmdLineData
{
	var $present;
	
	public function __construct(CmdLineProcessor $parent, $name)
	{
		$this->parent = $parent;
		$this->name = $name;
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
		$this->value = $this->present;
	}	

	public function syntax($short = true,$eol="")
	{
		if( $short )
			CLI::write("[{$this->syntaxName}]{$eol}");
		else
			CLI::write("\t{$this->syntaxName}\t{$this->description}{$eol}");
	}
}
