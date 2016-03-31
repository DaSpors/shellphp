<?php namespace ShellPHP\Storage;

class StorageException extends \Exception
{
	public function __construct($message,$sql=false)
	{
		if( $message instanceof \SQLite3 )
			$message = "[".$message->lastErrorCode()."] ".$message->lastErrorMsg();
		
		if( is_string($sql) )
			$message .= "\nSQL: $sql";
		
		parent::__construct($message);
	}
}
