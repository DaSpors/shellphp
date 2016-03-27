<?php namespace ShellPHP\WebInterface;

class WebInterface 
{
	protected $host;
	protected $port;
	protected $socket;
	protected $handler = array();
	protected $statics = array();
	protected $timers = array();
	
	private function __construct($host, $port)  
	{
		$this->host = $host;
		$this->port = intval($port);
	}
	
	public static function log()
	{
		call_user_func_array('\ShellPHP\CmdLine\CLI::writeln',func_get_args());
	}
	
	public static function Make($host='127.0.0.1', $port=6789)
	{
		return new WebInterface($host, $port);
	}
	
	public function getAddress()
	{
		$host = $this->host=='0.0.0.0'?gethostname():$this->host;
		return "http://$host:$this->port";
	}
	
	public function go()  
	{
		$this->socket = socket_create(AF_INET,SOCK_STREAM,0);
		
		if( !socket_bind($this->socket,$this->host,$this->port) )
		{
			$err = socket_strerror( socket_last_error() );
			throw new WebInterfaceException( "Unabled to start WebInterface at {$this->host}:{$this->port} - $err" );
		}
		
		\ShellPHP\CmdLine\CLI::prependDateTime();
		$last_loop = time();
		socket_set_nonblock($this->socket);
		while ( true ) 
		{
			socket_listen($this->socket);
			do
			{
				usleep(1000);
				$client = @socket_accept($this->socket);
				
				if( time() - $last_loop > 0 )
				{
					foreach( $this->timers as $i=>$t )
					{
						list($next,$interval,$callback,$args) = $t;
						if( $next > time() )
							continue;
						try
						{
							$callback($args);
						}
						catch(\Exception $ex)
						{
							$interval = false;
							self::log("Timer Exception: ".$ex->getMessage());
						}
						if( !$interval )
							unset($this->timers[$i]);
						else
							$this->timers[$i][0] = time()+$interval;
					}
					$last_loop = time();
				}
			}while( $client === false );

			$content = "";
			do
			{
				$chunk = socket_read($client, 1024);
				$content .= $chunk;
			}while( strlen($chunk)==1024 );
			if( $content == "" )
				continue;
			
			$request = new WebRequest($content);

			try
			{
				$response = $request->handle($this->handler,$this->statics);
				if( !($response instanceof WebResponse) )
					$response = WebResponse::Make(200,$response);
			}
			catch(Exception $ex)
			{
				$response = WebResponse::Make(500);
			}

			self::log($request->method,$response->statusCode,$request->path,http_build_query($request->arguments));
			
			$response = $response->render();
			socket_write($client, $response, strlen($response));
			socket_close($client);
		}
	}
	
	private function sanitizePath($path)
	{
		if( !$path || !is_string($path) || strlen($path)<1 )
			return '';
		if( $path[0] != '/' )
			$path = "/$path";
		return $path;
	}
	
	public function handler($path,$callback,$method=false)
	{
		$path = $this->sanitizePath($path);
		if( !$path )
			throw new WebInterfaceException("Invalid value for argument 'path'");
		
		$m = WebRequest::sanitizeMethod($method);
		if( $m )
			$this->handler[$m][$path] = $callback;
		else
			$this->handler['all'][$path] = $callback;
		return $this;
	}
	
	public function index($folder)
	{
		return $this->dir("/",$folder);
	}
	
	public function dir($path,$folder)
	{
		if( !file_exists($folder) )
			throw new WebInterfaceException("Invalid folder not found");
		if( !is_dir($folder) )
			throw new WebInterfaceException("Not a folder");
		$path = $this->sanitizePath($path);
		$this->statics[$path] = $folder;
		return $this;
	}
	
	public function timer($delay,$interval,$callback)
	{
		$args = func_get_args();
		array_splice($args,0,3);
		$this->timers[] = array(time()+$delay,$interval,$callback,$args);
		return $this;
	}
}
