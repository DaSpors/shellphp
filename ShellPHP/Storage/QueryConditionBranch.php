<?php namespace ShellPHP\Storage;

class QueryConditionBranch
{
	public $prefix = '';
	public $clause;
	public $parent;
	public $conditions = array();
	
	public function __construct($clause='AND', $parent=false)
	{
		$this->clause = $clause;
		$this->parent = $parent;
	}
	
	public function renderSql()
	{
		if( count($this->conditions) == 0 )
			return "";
		$res = array();
		foreach( $this->conditions as $c )
			if( $c instanceof QueryConditionBranch )
			{
				$t = $c->renderSql();
				if( $t )
					$res[] = $t;
			}
			else
				$res[] = "$c";
		return "{$this->prefix}(".implode(" {$this->clause} ",$res).")";
	}
}