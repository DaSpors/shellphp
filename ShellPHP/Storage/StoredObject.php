<?php namespace ShellPHP\Storage;

class StoredObject implements \ArrayAccess
{
	private static $__schema = array();
	
	public function __construct(){}
	
	public function offsetSet($offset, $value)
	{
        if( is_null($offset) ) 
			return;
        $this->$offset = $value;
    }
	
	public function offsetExists($offset)
	{
        return isset($this->$offset);
    }

    public function offsetUnset($offset)
	{
        unset($this->$offset);
    }

    public function offsetGet($offset)
	{
        return isset($this->$offset)?$this->$offset:null;
    }
	
	public static function GetTableName()
	{
		$classname = func_num_args()?func_get_arg(0):false;
		if( $classname )
		{
			if( $classname instanceof StoredObject )
				$classname = get_class($classname);
			$tab = $classname::GetTableName();
		}
		else
			$tab = strtolower(get_called_class())."s";
		if( strpos($tab,'\\') !== false )
			throw new StorageException('Special chars in table name: Please override '.__METHOD__);
		return $tab;
	}
	
	public static function Make($data=array())
	{
		$cls = get_called_class();
		$res = new $cls();
		foreach( $data as $k=>$v )
			$res->$k = $v;
		return $res;
	}
	
	public static function Select()
	{
		$cls = get_called_class();
		$cls::getCodeScheme()->ensureTable($cls);
		return new Query($cls);
	}
	
	public static function Truncate()
	{
		$cls = get_called_class();
		Storage::Make()->truncate($cls::GetTableName());
	}
	
	public static function Find($data=array())
	{
		$cls = get_called_class();
		$res = $cls::Select();
		foreach( $data as $k=>$v )
			$res->eq($k,$v);
		return $res;
	}
	
	public function Save($replace=false)
	{
		$cls = get_class($this);
		$scheme = $cls::getCodeScheme();
		return $scheme->saveModel($this,$replace);
	}
	
	public function UpdateBy()
	{
		$cls = get_class($this);
		$scheme = $cls::getCodeScheme();
		return $scheme->updateModel($this,func_get_args());
	}
	
	public function Delete()
	{
		$cls = get_class($this);
		$scheme = $cls::getCodeScheme();
		$scheme->deleteModel($this);
		return $this;
	}
	
	public function set($column,$value)
	{
		$this->$column = $value;
		return $this;
	}
	
	public function get()
	{
		return $this->getCodeScheme()->getValues($this,func_get_args());
	}
	
	public static function getCodeScheme()
	{
		$cls = get_called_class();
		$tab = $cls::GetTableName();
		if( isset(self::$__schema[$tab]) )
			return self::$__schema[$tab];
		
		$ref = new \ReflectionClass($cls); 
		$defaults = $ref->getDefaultProperties();
		
		$scheme = new StoredObjectSchema( $tab );
		foreach( $ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $property )
		{
			$name = $property->name;
			if( !isset($defaults[$name]) )
				throw new StorageException("Missing property definition: {$name}");
			
			$words = $next = preg_split("/[^a-z_]+/i",strtolower($defaults[$name]));
			array_shift($next); $next[] = $name;
			$def = array_combine($words,$next);
			
			if( isset($def['int']) || isset($def['integer']) || isset($def['byte']) || isset($def['bool']) || isset($def['boolean']) )
				$type = 'INTEGER';
			elseif( isset($def['string']) || isset($def['text']) || isset($def['char']) || isset($def['varchar']) )
				$type = 'TEXT';
			elseif( isset($def['date']) || isset($def['datetime']) )
				$type = 'DATETIME';
			elseif( isset($def['real']) || isset($def['float']) || isset($def['double']) || isset($def['currency']) )
				$type = 'FLOAT';
			else
				throw new StorageException("No valid type in property definition: {$defaults[$name]}");
			
			$pk = isset($def['primary']) || isset($def['prim']) || isset($def['pk']);
			$ai = isset($def['autoinc']) || isset($def['autoincrement']);
			
			$scheme->addColumn($name,$type,$pk,$ai,isset($def['unique'])?$def['unique']:false,isset($def['index'])?$def['index']:false);
		}
		self::$__schema[$scheme->table] = $scheme;
		return $scheme;
	}
}