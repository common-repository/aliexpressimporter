<?php

if (!function_exists('aeidn_plugin_autoload')) {
    function aeidn_plugin_autoload($class)
    {
        $prefix = 'Dnolbon\\';
        $baseDir = __DIR__ . '/src/';

        if (strpos($class, $prefix) === false) {
            return;
        }

        $file = $baseDir . str_replace('\\', '/', $class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
spl_autoload_register('aeidn_plugin_autoload');
