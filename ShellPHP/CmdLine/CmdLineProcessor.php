<?php namespace ShellPHP\CmdLine;

abstract class CmdLineProcessor extends CmdLineParser
{
	var $name;
	protected $commands = array();
	protected $flags = array();
	protected $options = array();
	protected $arguments = array();
	protected $handlers = array();
	
	protected function validate()
	{
		if( count($this->commands) > 0 )
		{
			$missing = array();
			foreach( $this->commands as $c )
			{
				if( $c->present )
					$c->validate();
				else
					$missing[] = $c->name;
			}
			if( count($missing) == count($this->commands) )
				$this->err("Missing or wrong command '{$this->get(0)}'.\nAllowed values are: ".implode(" | ",$missing));
		}
		$data = array();
		foreach( array_merge($this->flags,$this->options,$this->arguments) as $obj )
		{
			$obj->validate();
			$data[ $obj->varname?$obj->varname:$obj->name ] = $obj->value;
		}
			
		if( count($this->handlers) > 0 )
		{
			foreach( $this->handlers as $h )
				call_user_func($h,$data);
		}
	}

	public function syntax()
	{
		echo "Syntax:\n\t{$this->name} ";
		foreach( array_merge($this->flags,$this->options,$this->arguments) as $obj )
			$obj->syntax();

		if( count($this->commands) > 0 )
		{
			echo " <command>\nCommands:\n";
			foreach( $this->commands as $cmd )
				echo "\t{$cmd->name}\t{$cmd->description}\n";
		}
	}
	
	public function command($name)
	{
		$i = $this->indexOf($name);
		$this->commands[] = $cmd = new CmdLineCommand($this,$name,$i!==false);
		if( $i !== false )
			$cmd->setData(array_slice($this->data,$i+1));
		return $cmd;
	}
	
	public function handler($handler)
	{
		$this->handlers[] = $handler;
		return $this;
	}
	
	public function flag($name,$default)
	{
		$i = $this->indexOf($name);
		$this->flags[] = $flag = new CmdLineFlag($this,$name,$default,$i!==false);
		if( $i !== false )
			$flag->setData(array_slice($this->data,$i+1,1));
		return $flag;
	}

	public function opt($name,$default=null)
	{
		$i = $this->indexOf($name);
		$this->options[] = $opt = new CmdLineOption($this,$name,$default);
		if( $i !== false )
			$opt->setData(array_slice($this->data,$i+1,1));
		return $opt;
	}

	public function arg($varname,$default=null)
	{
		$this->arguments[] = $arg = new CmdLineArgument($this,$varname,$default,1);
		$arg->setData(array_shift($this->data));
		return $arg;
	}

	public function arrayArg($varname,$count1=1,$count2=false)
	{
		$len = $count2?$count2-$count1:1;
		$this->arguments[] = $arg = new CmdLineArgument($this,$varname,null,$count1,$count2);
		$arg->setData(array_slice($this->data,0,$len));
		return $arg;
	}
}
