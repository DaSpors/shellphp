<?php namespace ShellPHP\Process;

class Process 
{
	public $pid;
	public $command;
	public $args;
	
	private function __construct($pid,$command,$args='')
	{
		$this->pid = $pid;
		$this->command = $command;
		$this->args = $args;
	}
	
	public static function running($pid)
	{
		foreach( Process::all() as $p )
			if( $p->pid == $pid )
				return true;
		return false;
	}
	
	public static function find($pattern)
	{
		foreach( Process::all() as $p )
			if( fnmatch($pattern,$p->command,FNM_CASEFOLD) )
				return true;
		return false;
	}
	
	public static function all()
	{
		if( ISWIN )
			$cont = shell_exec("TASKLIST /NH /FO CSV");
		else
			$cont = shell_exec("ps h -A -o %c,%p,%a");
		
		$res = array();
		foreach( explode("\n",$cont) as $line )
		{
			$data = str_getcsv(trim($line));
			if( count($data)<3 )
				continue;
			if( ISWIN )
				$res[] = new Process(intval(trim($data[1])),trim($data[0]));
			else
				$res[] = new Process(intval(trim($data[1])),trim($data[0]),trim($data[2]));
		}
		return $res;
	}
	
	public static function Run($script,$args=array())
	{
		$descriptorspec = array(
			0 => STDIN,
			1 => STDOUT,
			2 => STDERR
		);
		foreach( $args as $a )
			$script .= " ".(strpos(' ',$a)===false?$a:"\"$a\"");
			
		if( ISWIN )
			$commandline = "start /MIN php {$script}";
		else
			$commandline = "php {$script} &";
		
		$child = array("process" => null, "pipes" => array()); 
		$child["process"] = proc_open($commandline, $descriptorspec, $child["pipes"]);
	}
}
