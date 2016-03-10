<?php namespace ShellPHP\CmdLine;

class CLI 
{
	private static function __renderVar($content,&$stack=array(),$indent="")
	{
		foreach( $stack as $s )
		{
			if( $s === $content )
				return "*RECURSION".(is_object($content)?"[".get_class($content)."]*":"*");
		}
		$res = array();
		if( is_array($content) )
		{
			if( count($content) == 0 )
				return "*EmptyArray*";
			$res[] = "Array(".count($content).")\n$indent(";
			foreach( $content as $i=>$val )
				$res[] = $indent."\t[$i]: ".self::__renderVar($val,$stack,$indent."\t");
			$res[] = $indent.")";
		}
		elseif( is_object($content) )
		{
			$stack[] = $content;
			if( $content instanceof Exception )
			{
				$res[] = get_class($content).": ".$content->getMessage();
				$res[] = "in ".$content->getFile().":".$content->getLine();
			}
			else
			{
				$res[] = "Object(".get_class($content).")\n$indent{";
				foreach( get_object_vars($content) as $name=>$val )
				{
					if( $val === $content )
						$res[] = $indent."\t->$name: *RECURSION*";
					else
						$res[] = $indent."\t->$name: ".self::__renderVar($val,$stack,$indent."\t");
				}
				$res[] = $indent."}";
			}
		}
		elseif( is_bool($content) )
			return (count($stack)>0?"(bool)":"").($content?"true":"false");
		elseif( is_null($content) )
			return (count($stack)>0?"(mixed)":"").'NULL';
		else
			return (count($stack)>0?"(".gettype($content).")":"").strval($content);
		return implode("\n",$res);
	}
	
	public static function toString($variable)
	{
		return self::__renderVar($variable);
	}
	
	public static function write()
	{
		$out = array();
		foreach( func_get_args() as $a )
			$out[] = self::toString($a);
		echo implode("\t",$out);
	}
	
	public static function writeln()
	{
		$out = array();
		foreach( func_get_args() as $a )
			$out[] = self::toString($a);
		echo implode("\t",$out)."\n";
	}
	
	private static $current_progress = false;
	private static $current_progress_start;
	public static function progress($done,$total)
	{
		if( self::$current_progress === false )
		{
			if( $done == $total ) 
				return;
			self::$current_progress_start = time();
			self::$current_progress = 0;
		}
		$perc = floor($done / $total * 100);
		if( $perc == self::$current_progress )
			return;
		
		$running = time() - self::$current_progress_start;
		$eta = floor(100 / $perc * $running) - $running + 1;
		
		self::$current_progress = $perc;
		echo '[';
		for($i=0; $i<100; $i+=2)
			echo ($i<$perc)?'=':' ';
		echo "] {$perc}% ETA ".Format::duration($eta)."\r";
		if( $done == $total )
		{
			self::$current_progress = false;
			echo "\n";
		}
	}
	
	private static $current_table = false;
	private static $current_table_hp = false;
	private static $current_table_af = false;
	public static function startTable($columns, $auto_flush_rows=100)
	{
		self::$current_table = array($columns);
		self::$current_table_hp = false;
		self::$current_table_af = $auto_flush_rows;
	}

	public static function addTableRow($row)
	{
		self::$current_table[] = array_values($row);
		if( count(self::$current_table) >= self::$current_table_af )
			flushTable();
	}

	public static function flushTable()
	{
		$lengths = array();
		foreach( self::$current_table[0] as $i=>$null )
		{
			$column = array_map(function($item)use($i){ return $item[$i]; },CLI::$current_table);
			$lengths[$i] = max(array_map('strlen',$column));
		}
		
		self::$current_table = array_map(function($row)use($lengths)
		{
			foreach( $row as $i=>$d )
				$row[$i] = str_pad($d,$lengths[$i]);
			return $row;
		},self::$current_table);
		
		foreach( self::$current_table as $row )
		{
			write(implode("  ",$row));
			if( self::$current_table_hp === false )
			{
				self::$current_table_hp = true;
				write(str_pad("-",strlen(implode("  ",$row)),'-'));
			}
		}
		self::$current_table = array();
	}
}
