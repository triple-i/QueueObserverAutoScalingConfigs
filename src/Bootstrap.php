<?php

defined('ROOT') || define('ROOT', realpath(__DIR__.'/../'));
defined('LIB') || define('LIB', ROOT.'/src');
defined('APP') || define('APP', 'Qoasc');
defined('TEST') || define('TEST', false);

$loader = require_once ROOT.'/vendor/autoload.php';
$loader->set('Qoasc', LIB);

