<?php namespace ShellPHP\CmdLine;

abstract class CmdLineParser
{
	protected $parent = false;
	protected $data = array();
	
	protected function setData($cli_args) { $this->data = $cli_args; return $this; }
	protected function get($index){ return isset($this->data[$index])?$this->data[$index]:null; }
	
	protected function indexOf($str)
	{
		return array_search($str,$this->data,false);
	}
	
	protected function err($msg)
	{
		throw new CmdLineParserException($msg);
	}
	
	protected function root()
	{
		if( $this->parent )
			return $this->parent->root();
		return $this;
	}
	
	protected abstract function validate();
	
	public function go()
	{
		try
		{
			$r = $this->root();
			$r->validate();
			return $r;
		}
		catch(CmdLineParserException $ex)
		{
			die($ex->getMessage()."\n");
		}
	}

	public function __call($name,$args)
	{
		$obj = $this->__findImplementor($name);
		if( $obj != null )
			return call_user_func_array(array($obj, $name),$args);
		throw new CmdLineParserException("Call to undefined method '$name' on object of type '".get_class($this)."'");
	}

	protected function __findImplementor($methodname)
	{
		if( method_exists($this,$methodname) )
			return $this;
		if( isset($this->parent) && $this->parent )
			return $this->parent->__findImplementor($methodname);
		return null;
	}
}
