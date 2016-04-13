<?php namespace ShellPHP\Storage;

class StoredObjectSchema
{
	private static $_schemaCache = array();
	
	public $table = '';
	public $columns = array();
	public $uniques = array();
	public $indexes = array();
	
	public function __construct($table){ $this->table = $table; }
	
	public static function FromTable($table)
	{
		if( isset(self::$_schemaCache[$table]) )
			return self::$_schemaCache[$table];
		
		$storage = Storage::Make();
		
		if( !$storage->tableExists($table) )
			return false;
		
		$scheme = new StoredObjectSchema($table);
		
		$rs = $storage->query("PRAGMA INDEX_LIST('$table')",false,function($row)use(&$scheme,$storage)
		{
			$n = $row['name'];
			if( stripos($n,'sqlite_') === 0 )
				return;
			$u = $row['unique']>0;
			$info = $storage->query("PRAGMA INDEX_INFO('$n')",false,function($i)use(&$scheme,$n,$u)
			{
				$scheme->addIndexColumn($n,$i['name'],$u);
			});
		});
		
		$createcode = $storage->querySingle("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'");
		$pk_is_ai = stripos($createcode,"autoincrement") !== false;
		$rs = $storage->query("PRAGMA table_info([$table])",false,function($row)use(&$scheme,$pk_is_ai)
		{
			$pk = $row['pk']>0;
			$ai = $pk && $pk_is_ai;
			$scheme->addColumn($row['name'],$row['type'],$pk,$ai);
		});
		
		self::$_schemaCache[$table] = $scheme;
		return $scheme;
	}
	
	function addColumn($name,$type,$pk=false,$ai=false,$unique=false,$index=false)
	{
		if( $unique === $name ) $unique = "uni_{$this->table}_{$unique}";
		if( $index === $name ) $index = "ndx_{$this->table}_{$index}";
		$type = strtoupper($type);
		
		$this->columns[$name] = compact('name','type','pk','ai');
		if( $unique )
			$this->uniques[$unique][] = $name;
		elseif( $index )
			$this->indexes[$index][] = $name;
			
		switch( $this->columns[$name]['type'] )
		{
			case 'INTEGER': $this->columns[$name]['sql_type'] = SQLITE3_INTEGER; break;
			case 'FLOAT':   $this->columns[$name]['sql_type'] = SQLITE3_FLOAT; break;
			case 'TEXT':
			case 'DATETIME':$this->columns[$name]['sql_type'] = SQLITE3_TEXT; break;
			case 'BLOB':    $this->columns[$name]['sql_type'] = SQLITE3_BLOB; break;
			default: $this->columns[$name]['sql_type'] = SQLITE3_NULL; break;
		}
	}
	
	function addIndexColumn($name,$column_name,$unique=false)
	{
		if( $unique )
			$this->uniques[$name][] = $column_name;
		else
			$this->indexes[$name][] = $column_name;
	}
	
	function hasColumn($name)
	{
		return isset($this->columns[$name]);
	}
	
	function renderSql()
	{
		$cols = array();
		foreach( $this->columns as $col )
			$cols[] = "[{$col['name']}] {$col['type']}".($col['pk']?' PRIMARY KEY':'').($col['ai']?' AUTOINCREMENT':'');
		$sql = array(str_replace('{cols}',implode(", ",$cols),"CREATE TABLE IF NOT EXISTS [{$this->table}]({cols});"));
		
		foreach( $this->indexes as $name=>$cols )
			$sql[] = "CREATE INDEX IF NOT EXISTS [$name] ON [{$this->table}](".implode(",",$cols).");";
		foreach( $this->uniques as $name=>$cols )
			$sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS [$name] ON [{$this->table}](".implode(",",$cols).");";
			
		return $sql;
	}
	
	public static $__once = array();
	private $buffer = array();
	
	function ensureTable($model)
	{
		if( isset($this->buffer['tab']) && isset($this->buffer['defaults']) )
			return array($this->buffer['tab'],$this->buffer['defaults']);
		
		$storage = Storage::Make();
		
		if( !isset($this->buffer['classname']) ) 
			$classname = $this->buffer['classname'] = is_string($model)?$model:get_class($model);
		else
			$classname = $this->buffer['classname'];
		
		if( !isset(StoredObjectSchema::$__once["tablename_{$classname}"]) )
		{
			$tab = StoredObjectSchema::$__once["tablename_{$classname}"] = $classname::GetTableName();
			$storage->setClassMapping($classname,$tab);
		}
		else
			$tab = StoredObjectSchema::$__once["tablename_{$classname}"];
		
		if( !isset(StoredObjectSchema::$__once["tableExists_$tab"]) )
		{
			if( !$storage->tableExists($tab) )
				foreach( $this->renderSql() as $sql )
					$storage->exec($sql);
			StoredObjectSchema::$__once["tableExists_$tab"] = true;
		}
		
		if( !isset(StoredObjectSchema::$__once["shellphp_types_$tab"]) )
		{
			$storage->exec("REPLACE INTO shellphp_types(classname,tablename)VALUES('$classname','".$tab."')");		
			StoredObjectSchema::$__once["shellphp_types_$tab"] = true;
		}
		
		if( !isset(StoredObjectSchema::$__once["get_class_vars_$classname"]) )
			$defaults = StoredObjectSchema::$__once["get_class_vars_$classname"] = get_class_vars($classname);
		else
			$defaults = StoredObjectSchema::$__once["get_class_vars_$classname"];
		
		$this->buffer['tab'] = $tab;
		$this->buffer['defaults'] = $defaults;
		return array($tab,$defaults);
	}
	
	function saveModel($model,$replace=false)
	{
		list($tab,$defaults) = $this->ensureTable($model);
		
		$cols = array(); $vars = array(); $comb = array(); $vals = array();
		$pk = array();
		foreach( $defaults as $k=>$v )
		{
			if( !isset($this->columns[$k]) || $v == $model->$k )
				continue;
			
			if( $model->$k == '__NOW__' )
				$ph = "datetime('now')";
			else
			{
				$vals[$k] = $model->$k;
				$ph = ":$k";
			}
			
			$cols[] = "[$k]";
			$vars[] = "$ph";
			$comb[] = "[$k]=$ph";
			if( $this->columns[$k]['pk'] )
				$pk[] = "[$k]=$ph";
		}

		$storage = Storage::Make();
		$cnt = $storage->exec(
			"INSERT OR ".($replace?"REPLACE":"IGNORE")." INTO [$tab](".implode(",",$cols).")VALUES(".implode(",",$vars).")",
			$vals,$this);
		if( $cnt && isset($defaults['id']) && !$model->id )
			$model->id = $storage->querySingle("SELECT last_insert_rowid()");
		
		if( !$replace && $cnt < 1 && count($pk)>0 )
			$cnt = $storage->exec("UPDATE [$tab] SET ".implode(", ",$comb)." WHERE ".implode(" AND ",$pk),$vals,$this);
		
		return $cnt > 0;
	}
	
	function updateModel($model,$match_columns=array())
	{
		if( count($match_columns) < 1 )
			return false;
		
		list($tab,$defaults) = $this->ensureTable($model);
		
		//$start = microtime(true);
		$comb = array(); $vals = array();
		$match = array();
		foreach( $defaults as $k=>$v )
		{
			if( !isset($this->columns[$k]) || $v == $model->$k )
				continue;
			
			if( $model->$k == '__NOW__' )
				$ph = "datetime('now')";
			else
			{
				$vals[$k] = $model->$k;
				$ph = ":$k";
			}
			
			if( in_array($k,$match_columns) )
			{
				if( $model->$k === null )
					$match[] = "[$k] IS NULL";
				else
					$match[] = "[$k]=$ph";
			}
			else
				$comb[] = "[$k]=$ph";
		}
		//Storage::StatCount(__METHOD__,microtime(true)-$start);
		return 0 < Storage::Make()->exec("UPDATE [$tab] SET ".implode(", ",$comb)." WHERE ".implode(" AND ",$match),$vals,$this);
	}
	
	function deleteModel($model)
	{
		list($tab,$defaults) = $this->ensureTable($model);
		
		$comb = array(); $vals = array();
		foreach( $defaults as $k=>$v )
		{
			if( !isset($this->columns[$k]) || $v == $model->$k || !isset($model->$k) )
				continue;
			$comb[] = "[$k]=:$k";
			$vals[$k] = $model->$k;
		}
		
		Storage::Make()->exec("DELETE FROM [$tab] WHERE ".implode(" AND ",$comb),$vals,$this);
	}
	
	function getValues($model,$columns=array())
	{
		list($tab,$defaults) = $this->ensureTable($model);
				
		$res = array();
		$columns = (count($columns)>0)?$columns:array_keys($defaults);
		foreach( $columns as $k)
		{
			if( !isset($this->columns[$k]) || $defaults[$k] == $model->$k )
				continue;
			$res[$k] = $model->$k;
		}
		return $res;
	}
}