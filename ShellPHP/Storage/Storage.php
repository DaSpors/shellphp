<?php namespace ShellPHP\Storage;

class Storage
{
	private static $__instance;
	private $__typeMap;
	private $__classMap;
	
	private $filename;
	private $db;
	private $db_refs = 0;
	
	public static $stats = array();
	public static function StatsOut()
	{
		arsort(self::$stats);
		\ShellPHP\CmdLine\CLI::startTable(array('ms','Name'));
		foreach( self::$stats as $name=>$value )
		{
			$value = round($value*1000,0);
			if( $value > 1 )
			{
				file_put_contents(sys_get_temp_dir().'/storage.stats.txt',"$name\t\t$value\n",FILE_APPEND);
				if( $value > 5 )
					\ShellPHP\CmdLine\CLI::addTableRow(array($value,$name));
			}
		}
		\ShellPHP\CmdLine\CLI::flushTable();
		die();
	}
	public static function StatCount($title, $inc=1)
	{
		self::$stats[$title] = isset(self::$stats[$title])?self::$stats[$title]+$inc:$inc;
		return microtime(true);
	}
	public $LastStatement = array();
	
	private function __construct($filename)
	{
		$this->filename = $filename;
		$this->db = new \SQLite3($this->filename);
		$this->db->busyTimeout(250);
	}
	
	private $lastStatementStart;
	private function setLastStatement($sql,$arguments=array())
	{
		if( $sql instanceof \SQLite3Stmt )
			$sql = array_search($sql,$this->statementCache,true);
		$this->LastStatement = array("sql"=>$sql,"args"=>$arguments);
		$this->lastStatementStart = microtime(true);
	}
	private function statLastStatement()
	{
		self::StatCount($this->LastStatement['sql'],microtime(true)-$this->lastStatementStart);
	}
	
	public static function Make($filename=false)
	{
		if( self::$__instance )
			return self::$__instance;
	
		if( !$filename ) $filename = ':memory:';
		self::$__instance = $res = new Storage($filename);
		
		try
		{
			$res->__typeMap = $res->__classMap = array();
			if( !$res->tableExists('shellphp_types') )
				$res->exec("CREATE TABLE [shellphp_types]([classname] text PRIMARY KEY, [tablename] text)");
			else
			{
				$res->needs_vakuum = false;
				foreach( $res->query("SELECT classname,tablename FROM [shellphp_types]") as $row )
				{
					extract($row);
					$res->__classMap[$classname] = $tablename;
					if( !class_exists($classname) )
						continue;
					if( !$res->tableExists($tablename) )
						continue;
					
					$code = $classname::getCodeScheme();
					if( $code->table == $tablename )
					{
						$stored = StoredObjectSchema::FromTable($tablename);
						if( count(array_diff($code->renderSql(),$stored->renderSql())) == 0 )
							continue;
						
						//\ShellPHP\CmdLine\CLI::writeln("CODE",$code->renderSql());
						//\ShellPHP\CmdLine\CLI::writeln("DB  ",$stored->renderSql(),$stored);
						foreach( array_merge($stored->uniques, $stored->indexes) as $name=>$cols )
							$res->exec("DROP INDEX IF EXISTS [$name]");
						$res->exec("ALTER TABLE [$tablename] RENAME TO '{$tablename}_shellphp_temp'");
						$tablename .= '_shellphp_temp';
					}
					else
						$res->__classMap[$classname] = $code->table;
					
					\ShellPHP\CmdLine\CLI::writeln("Updating database for '$classname'");
					$total = $res->querySingle("SELECT count(*) FROM [$tablename]");
					$res->exec("BEGIN TRANSACTION");
					$limit = 1000; $offset = 0; $done = 0;
					do
					{
						$cnt = 0;
						foreach( $res->query("SELECT * FROM [$tablename] LIMIT $offset,$limit") as $row )
						{
							$classname::Make($row)->Save();
							$cnt++;
						}
						$offset += $limit;
						$done += $cnt;
						\ShellPHP\CmdLine\CLI::progress($done,$total);
					}while( $cnt>0 );
					$res->exec("DROP TABLE [$tablename]");
					$res->exec("COMMIT");
					
					$res->needs_vakuum = true;
				}
				
				$res->__typeMap = array_flip($res->__classMap);
				
				if( $res->needs_vakuum )
					$res->exec("VACUUM");
				unset($res->needs_vakuum);
			}
		}catch(StorageException $ex){ }
		return $res;
	}
	
	public static function Select($tablename)
	{
		return new Query('StoredObject',$tablename);
	}
	
	private $statementCache = array();
	public function prepare($sql, $arguments=array(), $schema=false)
	{
		if( $sql instanceof \SQLite3Stmt )
		{
			$stmt = $sql;
			$stmt->clear();
		}
		elseif( !isset($this->statementCache[$sql]) || !$this->statementCache[$sql] )
			$stmt = $this->statementCache[$sql] = @$this->db->prepare($sql);
		else
		{
			$stmt = $this->statementCache[$sql];
			$stmt->clear();
		}
		
		if( !$stmt )
			throw new StorageException($this->db,$sql);
		
		$this->setArguments($stmt,$arguments,$schema,false);
		return $stmt;
	}
	
	public function setArguments($statement, $arguments=array(), $schema=false, $clear=true)
	{
		if( $clear )
			$statement->clear();
		if( is_array($arguments) && count($arguments)>0 )
		{
			foreach( $arguments as $k=>$v )
			{
				if( $schema && isset($schema->columns[$k]['sql_type']) )
					$statement->bindValue($k,$v,$schema->columns[$k]['sql_type']);
				else
					$statement->bindValue($k,$v);
			}
		}
		return $this;
	}
	
	public function query($sql, $arguments=array(),$row_callback=false)
	{
		$this->setLastStatement($sql);
		
		$tab = (is_string($sql) && preg_match("/select.+from\s+([^\s]+)/i",$sql,$m))
			?preg_replace("/[^a-z0-9-_.]/i","",$m[1])
			:false;
		$map = isset($this->__typeMap[$tab])?$this->__typeMap[$tab]:false;
		
		$stmt = $this->prepare($sql,$arguments);
		$rs = @$stmt->execute();
		$this->statLastStatement();
		if( $rs === false )
			throw new StorageException($this->db,$sql);
		
		$res = array();
		while( $row = $rs->fetchArray(SQLITE3_ASSOC) )
		{
			$model = $map?$map::Make($row):false;
			if( $row_callback )
			{
				if( $row_callback($row,$this->db,$model) === false )
					break;
			}
			else
				$res[] = $model?$model:$row;
		}
		if( $row_callback )
			return $this;
		return $res;
	}
	
	public function querySingle($sql, $arguments=array(), $entire_row=false)
	{
		$this->setLastStatement($sql);
		
		$stmt = $this->prepare($sql,$arguments);
		$rs = @$stmt->execute();
		$this->statLastStatement();
		if( $rs === false )
			throw new StorageException($this->db,$sql);
		if( $entire_row )
			while( $row = $rs->fetchArray(SQLITE3_ASSOC) )
				return $row;
		else
			while( $row = $rs->fetchArray(SQLITE3_NUM) )
				return $row[0];
		return null;
	}
	
	public function exec($sql,$arguments=array(),$schema=false,$await_locks=true)
	{
		$this->setLastStatement($sql,$arguments);
		
		$stmt = $this->prepare($sql,$arguments,$schema);
		
		do
		{
			$res = @$stmt->execute();
			$code = $this->db->lastErrorCode();
		}while( $res === false && $code == 5 && $await_locks );
		
		$this->statLastStatement();
		if( !$await_locks && $code == 5 )
			return 0;
			
		if( $res === false )
			throw new StorageException($this->db,$sql);
		
		return $this->db->changes();
	}
	
	public function tableExists($table)
	{
		return $this->querySingle("SELECT count(*) FROM sqlite_master WHERE type='table' AND tbl_name='$table'") > 0;
	}
	
	public function truncate($table)
	{
		if( !$this->tableExists($table) )
			return $this;
		$this->exec("DELETE FROM [$table]");
		if( !$this->tableExists('SQLITE_SEQUENCE') )
			return $this;
		$this->exec("UPDATE SQLITE_SEQUENCE SET SEQ=0 WHERE NAME='$table'");
		return $this;
	}
	
	public function getSetting($name,$default=null)
	{
		try
		{
			$raw = Setting::Select()->eq('name',$name)->scalar('value');
			if( $raw )
				return unserialize($raw);
		}catch(StorageException $ex){ }
		return $default;
	}
	
	public function setSetting($name,$value)
	{
		try
		{
			Setting::Make()->set('name',$name)->set('value',serialize($value))->Save();
		}catch(StorageException $ex){ }
		return $value;
	}
}
