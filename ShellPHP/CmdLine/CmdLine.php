<?php namespace ShellPHP\CmdLine;

class CmdLine extends CmdLineProcessor
{
	var $title;
	var $version;
	var $helpFlag;
	
	private function __construct($title=false, $version=false)
	{
		$this->name = $GLOBALS['argv'][0];
		$this->helpFlag = $this->setTitle($title)->setVersion($version)
			->setData( array_slice($GLOBALS['argv'],1) )
			->flag('-h',false)->alias('--help','/?');
	}
	
	public static function Make($title=false, $version=false)
	{
		return new CmdLine($title, $version);
	}
	
	public function setTitle($title){ $this->title = $title; return $this; }
	public function setVersion($version){ $this->version = $version; return $this; }

	public function help()
	{
		echo str_replace("\n\n","\n","{$this->title}\n{$this->version}\n");
		$this->syntax();
		die("\n");
	}
}
