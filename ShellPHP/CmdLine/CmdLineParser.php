<?php namespace ShellPHP\CmdLine;

abstract class CmdLineParser
{
	protected $parent = false;
	protected $data = array();
	var $description;
	
	protected function get($index){ return isset($this->data[$index])?$this->data[$index]:null; }
	
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
	
	protected abstract function setData($cli_args);
	protected abstract function validate();
	protected abstract function completion();
	
	public function go($completion_variable='__check__')
	{
		global $argv;
		try
		{
			$r = $this->root();
			if( isset($argv[1]) && $argv[1] === $completion_variable )
			{
				if( count($argv)<3 )
					exit(0);
				array_splice($argv,0,3);
				$r->setData($argv);
				$r->completion();
				exit(0);
			}
			
			$r->setData(array_slice($argv,1));
			if( count($r->data) == 0 || $r->helpFlag->present )
				$r->help();
			else
				$r->validate();
			return $r;
		}
		catch(CmdLineParserException $ex)
		{
			die($ex->getMessage()."\n");
		}
	}

	public function text($description)
	{
		$this->description = $description;
		return $this;
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
