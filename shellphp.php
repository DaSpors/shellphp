<?php

define('ISWIN',(stripos(php_uname('s'),'windows') !== false));

spl_autoload_register(function ($class)
{
    $prefix = 'ShellPHP\\';
    $base_dir = __DIR__ . '/ShellPHP/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
        return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file))
        require $file;
});
