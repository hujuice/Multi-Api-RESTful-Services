<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Restful Services Discovery</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="Multi-API Restful Services - Client" />
</head>
<body>
<h1>Multi-API Restful Services - Client</h1>
<h2>Resource "Members": available examples</h2>
<?php
require_once(realpath(__DIR__ . '/../..') . '/library/Restful/Client.php');
$members = new Restful_Client('http://restful.inservibile.org/members', 'GET', 'application/json');
?>
<h3>List of planets</h3>
<?php
$planets = $members->getPlanets();
echo '<ul>', PHP_EOL;
foreach ($planets as $planet)
    echo '<li>', $planet, '</li>', PHP_EOL;
echo '</ul>', PHP_EOL;
?>
<h3>Users from Earth</h3>
<?php
$users = $members->getFromPlanet('Earth');
echo '<ul>', PHP_EOL;
foreach ($users as $user)
    echo '<li>', $user, '</li>', PHP_EOL;
echo '</ul>', PHP_EOL;
?>
</body>
</html>
