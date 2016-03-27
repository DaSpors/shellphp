<?php namespace ShellPHP\WebInterface;

class WebResponse
{
	public static $statusCodes = array
	(  
		100 => 'Continue', 101 => 'Switching Protocols',
		200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
		300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
		400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed',  406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed',
		500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 509 => 'Bandwidth Limit Exceeded'
	);
	
	public $statusCode = 200;  
	public $body = '';  
	public $headers = [];  
	
	private function __construct($status, $body)  
	{
		$this->status($status);
		$this->body = $body;
		$this->header('Date', gmdate('D, d M Y H:i:s T'));
		$this->header('Content-Type', 'text/html; charset=utf-8');
		//$this->header('Content-Length', "".strlen($body));
		$this->header('Server', 'ShellPHP_WebInterface/0.0.1 ('.trim(php_uname('s')).')');
	}
	
	public static function Make($status = 200, $body = '')
	{
		return new WebResponse($status, $body);
	}
	
	public static function File($filename)
	{
		$ext = pathinfo($filename,PATHINFO_EXTENSION);
		switch( $ext )
		{
			case 'html': case 'htm': 
				$mime = "text/html; charset=utf-8";
				break;
			case 'js': 
				$mime = "application/javascript"; 
				break;
			case 'css': case 'less': 
				$mime = "text/css"; 
				break;
			default:
				if( function_exists('finfo_open') )
				{
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime = finfo_file($finfo, $filename);
				}
				break;
		}
		if( !$mime ) 
			$mime = "text/html; charset=utf-8";
		return WebResponse::Make(200, file_get_contents($filename))
			->header("Content-Type",$mime);
	}
	
	public static function Json($data)
	{
		return WebResponse::Make(200, json_encode($data))
			->header("Content-Type","application/json");
	}
	
	public function status($status)
	{
		$s = intval($status);
		if( !isset(self::$statusCodes[$s]) )
			throw new WebInterfaceException("Invalid status code: $status");
		$this->statusCode = $s;
		return $this;
	}
	
	public function header($key, $value)
	{
		if( !is_string($key) )
			throw new WebInterfaceException("Invalid header: $key");
		if( !is_string($value) )
			throw new WebInterfaceException("Invalid header value: $value");
		$this->headers[ucfirst($key)] = $value;
		return $this;
	}
	
	public function render()
	{
		$lines = [];
		$lines[] = "HTTP/1.1 {$this->statusCode} ".self::$statusCodes[$this->statusCode];
		foreach( $this->headers as $k=>$v)
			$lines[] = "$k: $v";
		return implode(" \r\n",$lines)."\r\n\r\n".$this->body;
	}
}