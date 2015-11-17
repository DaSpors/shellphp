<?php namespace ShellPHP\CmdLine;

abstract class CmdLineData extends CmdLineParser
{
	var $name;
	var $varname;
	var $aliases = array();
	var $role;
	var $mustExist = false;

	public function map($varname)
	{
		$this->varname = $varname;
		return $this;
	}

	public function alias()
	{
		$this->aliases = array_merge($this->aliases,func_get_args());
		return $this;
	}

	public function __get($name)
	{
		switch( $name )
		{
			case 'syntaxName':
				return trim(str_replace("||","|",implode("|",array_merge(array($this->name,$this->varname),$this->aliases))),"|");
		}
	}

	public function file(){ $this->role = 'file'; return $this; }
	public function folder(){ $this->role = 'folder'; return $this; }
	public function exists($yes=true){ $this->mustExist = $yes; return $this; }
	public function missing($yes=true){ $this->mustExist = $yes; return $this; }
}
