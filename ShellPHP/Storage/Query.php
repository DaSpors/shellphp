<?php namespace ShellPHP\Storage;

class Query
{
	private $className;
	private $tableName;
	private $scheme;
	private $escaper;
	
	private $columns = array('*');
	private $from = array();
	private $where = false;
	private $group = array();
	private $having = false;
	private $order = array();
	private $limit = '';
	
	private $currentBranch = false;
	
	public function __construct($classname, $tablename=false)
	{
		$this->className = $classname;
		$this->tableName = $tablename?$tablename:StoredObject::GetTableName($classname);
		$this->scheme = StoredObjectSchema::FromTable($this->tableName);
		$this->escaper = new \SQLite3(':memory:');
	
		$this->from = array($this->tableName);
	}
	
	public function __call($method, $args)
	{
		switch( $method )
		{
			case 'eq':
				return call_user_func_array(array($this,'equal'),$args);
			case 'gt':
				return call_user_func_array(array($this,'greater'),$args);
			case 'gte': case 'goe':
				return call_user_func_array(array($this,'greaterOrEqual'),$args);
			case 'lt':
				return call_user_func_array(array($this,'lower'),$args);
			case 'lte': case 'loe':
				return call_user_func_array(array($this,'lowerOrEqual'),$args);
			case 'neq': case 'noteq': case 'notequal':
				return call_user_func_array(array($this,'unequal'),$args);
		}
		return $this;
	}
	
	public function equal($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('=',array($column1,$column2,$escapeValue1,$escapeValue2));
	}

	public function greater($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('>',array($column1,$column2,$escapeValue1,$escapeValue2));
	}
	
	public function greaterOrEqual($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('>=',array($column1,$column2,$escapeValue1,$escapeValue2));
	}

	public function lower($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('<',array($column1,$column2,$escapeValue1,$escapeValue2));
	}
	
	public function lowerOrEqual($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('<=',array($column1,$column2,$escapeValue1,$escapeValue2));
	}

	public function unequal($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition('!=',array($column1,$column2,$escapeValue1,$escapeValue2));
	}
	
	public function like($column1,$column2,$escapeValue1=false,$escapeValue2=true)
	{
		return $this->__addCondition(' LIKE ',array($column1,$column2,$escapeValue1,$escapeValue2));
	}
	
	public function isNull($column1)
	{
		return $this->__addCondition(' IS ',array($column1,'NULL',false,false));
	}
	
	public function notNull($column1)
	{
		return $this->__addCondition(' IS NOT ',array($column1,'NULL',false,false));
	}
	
	public function shuffle()
	{
		$mem = $this->where;
		$this->where = new QueryConditionBranch('AND');
		$this->where->conditions[] = "_ROWID_ >= (abs(random()) % (SELECT max(_ROWID_) FROM [{$this->tableName}]))";
		if( $mem )
		{
			$this->where->conditions[] = $mem;
			$mem->parent = $this->where;
		}
		else
			$this->currentBranch = $this->where;
		return $this;
	}

	public function in($column,$values=array(),$all_if_empty=false)
	{
		if( count($values) == 0 )
			return $all_if_empty?$this:$this->eq('0','1',true,true);
		$column2 = array();
		foreach( $values as $v )
			$column2[] = is_numeric($v)?intval($v):"'".$this->escaper->escapeString($v)."'";
		$column2 = "(".implode(",",$column2).")";
		return $this->__addCondition(' IN',array($column,$column2,false,false));
	}

	private function __escapeOp($operation, $op, $escape)
	{
		if( $this->scheme && $this->scheme->hasColumn($op) )
			return "[$op]";
		if( !$escape )
			return "$op";

		if( $operation == ' LIKE ' && !preg_match('/[%_]/',$op) )
			$op = "%$op%";
		return "'".$this->escaper->escapeString($op)."'";
	}
	
	private function __addCondition($operation,$args)
	{
		list($op1,$op2,$escapeOp1,$escapeOp2) = array_pad(array_values($args),4,null);
		if( $escapeOp1 === null ) $escapeOp1 = false;
		if( $escapeOp2 === null ) $escapeOp2 = true;
		
		$op1 = $this->__escapeOp($operation,$op1,$escapeOp1);
		$op2 = $this->__escapeOp($operation,$op2,$escapeOp2);
		
		if( !$this->currentBranch )
			$this->all();
		$this->currentBranch->conditions[] = "$op1$operation$op2";
		return $this;
	}
	
	private function __ensureBranch($clause)
	{
		if( $this->currentBranch )
		{
			$this->currentBranch->conditions[] = new QueryConditionBranch($clause,$this->currentBranch);
			$this->currentBranch = $this->currentBranch->conditions[count($this->currentBranch->conditions)-1];
		}
		else
		{
			$this->where = new QueryConditionBranch($clause);
			$this->currentBranch = $this->where;
		}
		return $this;
	}
	
	public function all()
	{
		return $this->__ensureBranch('AND');
	}
	
	public function any()
	{
		return $this->__ensureBranch('OR');
	}
	
	public function not($clause='AND')
	{
		$this->__ensureBranch($clause);
		$this->currentBranch->prefix = ' NOT';
		return $this;
	}
	
	public function end()
	{
		if( !$this->currentBranch || !$this->currentBranch->parent )
			return $this;
		$this->currentBranch = $this->currentBranch->parent;
		return $this;
	}
	
	public function column($column, $replace=false)
	{
		if( $replace )
			$this->columns = array($column);
		else
			$this->columns[] = $column;
		return $this;
	}
	
	public function groupBy($column, $replace=false)
	{
		if( $replace )
			$this->group = array($column);
		else
			$this->group[] = $column;
		return $this;
	}
	
	public function orderBy($column, $replace=false)
	{
		if( $replace )
			$this->order = array($column);
		else
			$this->order[] = $column;
		return $this;
	}
	
	public function limit($limit,$offset=0)
	{
		$this->limit = "$limit OFFSET $offset";
		return $this;
	}
	
	public function renderSql()
	{
		$sql = "SELECT ".implode(", ",$this->columns)." FROM ".implode(", ",$this->from);
		if( $this->where )
			$sql .= " WHERE".$this->where->renderSql();
		if( count($this->group)>0 )
			$sql .= " GROUP BY ".implode(", ",$this->group);
		if( $this->having )
			$sql .= " HAVING ".$this->having->renderSql();
		if( count($this->order)>0 )
			$sql .= " ORDER BY ".implode(", ",$this->order);
		if( $this->limit )
			$sql .= " LIMIT ".$this->limit;
		
		return $sql;
	}
	
	public function results()
	{
		$res = array();
		Storage::Make()->query($this->renderSql(),false,function($row,$db,$model)use(&$res){ $res[] = $model?$model:$row; });
		return $res;
	}
	
	public function current()
	{
		$res = null;
		Storage::Make()->query($this->renderSql(),false,function($row,$db,$model)use(&$res){ $res = $model?$model:$row; return false; });
		return $res;
	}
	
	public function scalar($column)
	{
		$mem = $this->columns;
		$this->columns = array($column);
		$res = Storage::Make()->querySingle($this->renderSql());
		$this->columns = $mem;
		return $res;
	}
	
	public function count()
	{
		return $this->scalar("count(*)");
	}
	
	public function each($callback)
	{
		Storage::Make()->query($this->renderSql(),false,function($row,$db,$model)use(&$callback){ $callback($model?$model:$row); });
	}
}