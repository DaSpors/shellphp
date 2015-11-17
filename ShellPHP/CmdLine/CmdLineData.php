<?php namespace ShellPHP\CmdLine;

abstract class CmdLineData extends CmdLineParser
{
	var $varname;
	var $description;
	var $role;
	var $mustExist = false;

	public function text($description)
	{
		$this->description = $description;
		return $this;
	}
	
	public function map($varname)
	{
		$this->varname = $varname;
		return $this;
	}

	public function file(){ $this->role = 'file'; return $this; }
	public function folder(){ $this->role = 'folder'; return $this; }
	public function exists($yes=true){ $this->mustExist = $yes; return $this; }
	public function missing($yes=true){ $this->mustExist = $yes; return $this; }
}
