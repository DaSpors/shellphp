<?php namespace ShellPHP\CmdLine;

class CmdLine extends CmdLineProcessor
{
	var $title;
	var $version;
	var $helpFlag;
	
	private function __construct($title=false, $version=false)
	{
		$this->name = $GLOBALS['argv'][0];
		$this->setTitle($title)->setVersion($version);
			
		if( stripos(php_uname('s'),'windows') !== false )
			$this->helpFlag = $this->flag('/?',false)->alias('--help');
		else
			$this->helpFlag = $this->flag('--help',false)->alias('/?');
		
		$this->helpFlag->text('Shows help');
	}
	
	protected function setData($cli_args)
	{
		$this->data = $cli_args;
		$this->data = $this->helpFlag->setData($this->data);
		return array();
	}
	
	public static function Make($title=false, $version=false)
	{
		return new CmdLine($title, $version);
	}
	
	public function setTitle($title){ $this->title = $title; return $this; }
	public function setVersion($version){ $this->version = $version; return $this; }

	public function help()
	{
		CLI::write(str_replace("\n\n","\n","{$this->title}\n{$this->version}\n"));
		$this->syntax(false);
		die("\n");
	}
}
