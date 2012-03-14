<?php
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../resources'),
    get_include_path(),
)));

require_once(realpath(__DIR__ . '/../..') . '/library/Restful/Server.php');
$restJson = new Restful_Server('../config.ini');

// GO GO GO!!!
$restJson->run();
