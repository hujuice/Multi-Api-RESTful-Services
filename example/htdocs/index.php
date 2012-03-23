<?php
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../library'),
    get_include_path(),
)));

require_once('restful/server.php');
$rest = new Restful\Server('../config.ini');

// GO GO GO!!!
$rest->run();

