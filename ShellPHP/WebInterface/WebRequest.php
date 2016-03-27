<?php namespace ShellPHP\WebInterface;

class WebRequest
{
	public $method = '';  
	public $url = '';
	public $headers = array();
	public $components = array();
	public $path = '';
	public $arguments = array();
	public $body = '';
	
	public static function sanitizeMethod($method)
	{
		if( !is_string($method) )
			return '';
		$method = strtoupper($method);
		foreach( array('GET','POST','HEAD','PUT','DELETE','OPTIONS','CONNECT') as $m )
			if( $m == $method )
				return $m;
		return '';
	}
	
	function __construct($request)
	{
		$lines = explode( "\n", $request );
		@list($this->method,$this->url) = explode(' ',array_shift($lines));
		$this->method = self::sanitizeMethod($this->method);
		$this->components = parse_url($this->url);
		if( isset($this->components['path']) )
			$this->path = trim($this->components['path']);
		if( isset($this->components['query']) )
			parse_str($this->components['query'],$this->arguments);
		
		$this->headers = [];
		foreach( $lines as $i=>$line )
		{
			$line = trim($line);
			if ( strpos($line,': ') === false )
			{
				array_splice($lines,0,$i);
				$this->body = trim(implode("\n",$lines));
				
				if( $this->method == 'POST' )
				{
					parse_str($this->body,$post_args);
					$this->arguments = array_merge($this->arguments,$post_args);
				}
				
				break;
			}
			list($key,$value) = explode(': ', $line, 2);
			$headers[$key] = $value;
		}
	}
	
	public function header($key, $default = null)  
	{
		if( !isset($this->headers[$key]) )
			return $default;
		return $this->headers[$key];
	}
	
	public function arg($key, $default = null)  
	{
		if( !isset($this->arguments[$key]) )
			return $default;
		return $this->arguments[$key];
	}
	
	public function handle($handler,$statics)
	{
		$lp = strtolower($this->path);
		
		if( isset($handler[$this->method][$lp]) )
			return call_user_func($handler[$this->method][$lp],$this);
		if( isset($handler['all'][$lp]) )
			return call_user_func($handler['all'][$lp],$this);
		
		foreach( $statics as $path=>$folder )
		{
			if( stripos($this->path,$path) !== 0 )
				continue;
			
			$local = str_replace("//","/",$folder."/".substr($this->path,strlen($path)));
			if( is_dir($local) )
			{
				$local = rtrim($local,"/")."/";
				$search = array("{$local}index.html", "{$local}index.htm");
			}
			else
				$search = array($local);
			foreach( $search as $file )
			{
				if( !file_exists($file) || !is_file($file) )
					continue;
				return WebResponse::File($file);
			}
		}
		
		return WebResponse::Make(404);
	}
}
