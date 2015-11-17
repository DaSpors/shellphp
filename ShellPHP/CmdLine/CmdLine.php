<?php namespace ShellPHP\CmdLine;

class CmdLine extends CmdLineProcessor
{
	var $title;
	var $version;
	
	private function __construct($title=false, $version=false)
	{
		$this->setTitle($title)->setVersion($version)
			->setData( array_slice($GLOBALS['argv'],1) );
	}
	
	public static function Make($title=false, $version=false)
	{
		return new CmdLine($title, $version);
	}
	
	public function setTitle($title){ $this->title = $title; return $this; }
	public function setVersion($version){ $this->version = $version; return $this; }
}
