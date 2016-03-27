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
		$res = array();
		foreach( $this->conditions as $c )
			if( $c instanceof QueryConditionBranch )
				$res[] = $c->renderSql();
			else
				$res[] = "$c";
		return "{$this->prefix}(".implode(" {$this->clause} ",$res).")";
	}
}