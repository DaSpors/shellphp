<?php namespace ShellPHP\CmdLine;

abstract class CmdLineProcessor extends CmdLineParser
{
	var $name;
	var $present = true;
	protected $commands = array();
	protected $flags = array();
	protected $options = array();
	protected $arguments = array();
	protected $handlers = array();
	
	protected function setData($cli_args)
	{
		$i = array_search($this->name,$cli_args,false);
		$this->present = $i !== false;
		if( $this->present )
			$this->data = array_splice($cli_args,$i+1);
		return array_values($cli_args);
	}
	
	protected function validate()
	{
		if( !$this->present )
			return;
		
		if( count($this->commands) > 0 )
		{
			$cmds = array(); $noi = count($this->commands) + 1;
			foreach( $this->commands as $c )
			{
				$i = array_search($c->name,$this->data,false);
				if( $i === false ) $i = $noi++;
				$cmds[$i] = $c;
			}
			ksort($cmds);
			
			$missing = array();
			foreach( $cmds as $c )
			{
				$this->data = $c->setData($this->data);
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
			$this->data = $obj->setData($this->data);
			$obj->validate();
			$data[ $obj->varname?$obj->varname:$obj->name ] = $obj->value;
		}
			
		if( count($this->handlers) > 0 )
		{
			foreach( $this->handlers as $h )
				call_user_func($h,$data);
		}
	}

	protected function syntax($short=true,$eol="")
	{
		if( $short )
		{
			CLI::write("\t{$this->name}\t{$this->description}{$eol}");
			return;
		}
		foreach( $this->commands as $c )
		{
			$this->data = $c->setData($this->data);
			
			if( $c->present )
			{
				$c->syntax(false);
				return;
			}
		}
		
		CLI::write("\nSyntax:\t{$this->name} ");
		foreach( array_merge($this->flags,$this->options,$this->arguments) as $obj )
		{
			$obj->syntax(true," ");
		}
		
		if( count($this->commands) > 0 )
		{
			CLI::write(" <command>\n\nCommands:\n");
			foreach( $this->commands as $cmd )
				$cmd->syntax(true,"\n");
		}
		if( count($this->flags) > 0 )
		{
			CLI::write("\n\nFlags:\n");
			foreach( $this->flags as $obj )
				$obj->syntax(false,"\n");
		}
		if( count($this->options) > 0 )
		{
			CLI::write("\n\nOptions:\n");
			foreach( $this->options as $obj )
				$obj->syntax(false,"\n");
		}
		if( count($this->arguments) > 0 )
		{
			CLI::write("\n\nArguments:\n");
			foreach( $this->arguments as $obj )
				$obj->syntax(false,"\n");
		}
	}
	
	public function command($name)
	{
		$this->commands[] = $cmd = new CmdLineCommand($this,$name);
		return $cmd;
	}
	
	public function handler($handler)
	{
		$this->handlers[] = $handler;
		return $this;
	}
	
	public function flag($name,$default)
	{
		$this->flags[] = $flag = new CmdLineFlag($this,$name,$default);
		return $flag;
	}

	public function opt($name,$default=null)
	{
		$this->options[] = $opt = new CmdLineOption($this,$name,$default);
		return $opt;
	}

	public function arg($varname,$default=null,$repeat=false)
	{
		$this->arguments[] = $arg = new CmdLineArgument($this,$varname,$default,$repeat);
		return $arg;
	}
}
