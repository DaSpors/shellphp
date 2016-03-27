<?php namespace ShellPHP\CmdLine;

abstract class CmdLineData extends CmdLineParser
{
	var $name;
	var $varname;
	var $aliases = array();
	var $role;
	var $validValues = array();
	var $valueSeparator = ',';

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
				return trim(str_replace("||","|",implode("|",array_merge(array($this->name),$this->aliases))),"|");
			case 'defaultValue':
				if( $this instanceof CmdLineFlag )
					return $this->default?'on':'off';
				if( $this instanceof CmdLineOption )
					return $this->required?'required':CLI::toString($this->default);
				return CLI::toString($this->default);
			case 'requiredValue':
				if( $this instanceof CmdLineArgument )
					return $this->required?'required':'optional';
				if( $this instanceof CmdLineOption )
					return $this->required?'required':'optional';
				return CLI::toString($this->required);
		}
	}

	protected function validateRole()
	{
		switch( $this->role )
		{
			case 'file':
			case 'folder': 
				$this->value = realpath($this->value);
				if( !file_exists($this->value) )
					$this->err("{$this->syntaxName}: ".ucwords($this->role)." not found'");
				break;
			case 'enum':
				if( !in_array($this->value,$this->validValues) )
					$this->err("{$this->syntaxName}: invalid value '{$this->value}'.\nShould be one of ".implode($this->valueSeparator,$this->validValues));
				break;
			case 'set':
				$vals = explode($this->valueSeparator,$this->value);
				$wrong = array_diff($vals,$this->validValues);
				
				if( count($wrong)>0 )
					$this->err("{$this->syntaxName}: invalid values '".implode("'{$this->valueSeparator}'",$wrong)."'.\nShould be any of ".implode($this->valueSeparator,$this->validValues));
				break;
		}
	}
	
	public function file(){ $this->role = 'file'; return $this; }
	public function folder(){ $this->role = 'folder'; return $this; }
	
	public function oneOf()
	{
		$this->role = 'enum';
		$this->validValues = func_get_args();
		return $this;
	}
	
	public function setOf($separator=',')
	{
		$this->role = 'set';
		$this->validValues = func_get_args();
		$this->valueSeparator = array_shift($this->validValues);
		return $this;
	}
}
