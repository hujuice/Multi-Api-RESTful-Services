<?php
define('API_PATH', realpath(__DIR__ . '/..'));

require_once(realpath(__DIR__ . '/../..') . '/library/Restful/Server.php');
$restJson = new Restful_Server(API_PATH . '/config.ini');

// GO GO GO!!!
$restJson->run();