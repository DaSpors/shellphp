<?php namespace ShellPHP\CmdLine;

class Format 
{
	public static function duration($duration)
	{
		$hours = floor($duration / 3600);
		$duration -= $hours * 3600;
		$minutes = floor($duration / 60);
		$seconds = $duration - $minutes * 60;
		return sprintf("%02d:%02d:%02d",$hours,$minutes,$seconds);
	}
}
