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
	
	private static $prependDT = false;
	public static function prependDateTime($yes=true)
	{
		self::$prependDT = $yes?true:false;
	}
	
	public static function toString($variable)
	{
		return self::__renderVar($variable);
	}
	
	public static function write()
	{
		$out = array();
		if( self::$prependDT )
			$out[] = "[".date("Y-m-d H:i:s")."]";
		foreach( func_get_args() as $a )
			$out[] = self::toString($a);
		echo implode("\t",$out);
	}
	
	public static function writeln()
	{
		$out = array();
		if( self::$prependDT )
			$out[] = "[".date("Y-m-d H:i:s")."]";
		foreach( func_get_args() as $a )
			$out[] = self::toString($a);
		echo implode("\t",$out)."\n";
	}
	
	private static $current_progress = false;
	private static $current_progress_start;
	private static $current_progress_last;
	private static $current_progress_offset = 0;
	private static $current_progress_width;
	public static function progress($done,$total)
	{
		if( self::$current_progress === false )
		{
			if( $done == $total ) 
				return;
			self::$current_progress_start = self::$current_progress_last = time();
			self::$current_progress = 0;
			self::$current_progress_offset = $done;
			self::$current_progress_width = ISWIN?50:(intval(shell_exec("tput cols")) - 20);
		}
		$perc_float = $done / $total * 100;
		$perc = floor($perc_float);
		if( $perc == self::$current_progress && time() == self::$current_progress_last )
			return;
		
		self::$current_progress_last = time();
		$running = time() - self::$current_progress_start;
		
		$eta_perc = ($done-self::$current_progress_offset) / ($total-self::$current_progress_offset) * 100;
		$eta = ($eta_perc * $running > 0)
			?floor(100 / $eta_perc * $running) - $running + 1
			:'NA';
			
		self::$current_progress = $perc;
		$bar = floor($perc * self::$current_progress_width / 100);
		echo "[".str_repeat("=",$bar).str_repeat(" ",self::$current_progress_width-$bar)."]";
		
		
		if( $done == $total )
		{
			self::$current_progress = false;
			echo " {$perc}% DUR ".Format::duration($running)."\n";
		}
		else
			echo " {$perc}% ETA ".Format::duration($eta)."\r";
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

	public static function flushTable($outer_bar=false,$skip_if_empty=false)
	{
		if( count(self::$current_table) == 0 )
			return false;
		if( count(self::$current_table) == 1 && $skip_if_empty )
			return false;
		
		$lengths = array();
		$mli = 0; $len = 0;
		foreach( self::$current_table as $i=>$r )
		{
			if( $len < count($r) )
			{
				$mli = $i;
				$len = count($r);
			}
		}
		foreach( self::$current_table[$mli] as $i=>$null )
		{
			$column = array_map(function($item)use($i){ return isset($item[$i])?$item[$i]:''; },CLI::$current_table);
			$lengths[$i] = max(array_map('strlen',$column));
		}
		
		self::$current_table = array_map(function($row)use($lengths)
		{
			foreach( $row as $i=>$d )
				$row[$i] = str_pad($d,$lengths[$i]);
			return $row;
		},self::$current_table);
		
		$pad = "  ";
		$head = str_pad("-",strlen(implode($pad,self::$current_table[$mli])),'-');
		if( $outer_bar )
			self::writeln($head);
		foreach( self::$current_table as $row )
		{
			self::writeln(implode($pad,$row));
			if( self::$current_table_hp === false )
			{
				self::$current_table_hp = true;
				self::writeln($head);
			}
		}
		if( $outer_bar )
			self::writeln($head);
		self::$current_table = array();
		return true;
	}
}
