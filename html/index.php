<?php

use TestTaskMailing\API\API;

require_once(__DIR__ . "/../vendor/autoload.php");

ini_set('log_errors', 'On');
ini_set('display_errors', 'Off');
ini_set('error_log', __DIR__."/../log/log_php_errors.txt");

$API = new API();

$API->addRoute('/addSubscribersFromFile', 'AddSubscribersFromFile', API::METHOD_POST);
$API->addRoute('/addSubscribersFromJSON', 'AddSubscribersFromJSON', API::METHOD_POST);

$API->addRoute('/mailing/create', 'CreateMailing', API::METHOD_POST);
$API->addRoute('/mailing/run', 'RunMailing', API::METHOD_POST);

$API->run();