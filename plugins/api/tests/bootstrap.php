<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/config.inc.php';

// Instantiate the API container
with(new Flynax\Api\Api())->setBasePath(realpath(__DIR__ . '/../'));

rl('Db')->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
rl('Valid');
