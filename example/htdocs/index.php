<?php
// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../../library'),
    get_include_path(),
)));

define('API_PATH', realpath(__DIR__ . '/..'));

require_once('Restful/Server.php');
$restJson = new Restful_Server(API_PATH . '/config.ini');

// GO GO GO!!!
$restJson->run();