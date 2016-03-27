<?php namespace ShellPHP\Storage;

class Setting extends StoredObject
{
	public static function GetTableName(){ return 'shellphp_settings'; }
	
	public $name = 'text primary';
	public $value = 'text';
}