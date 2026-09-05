<?php

declare(strict_types=1);

define('AVA_START', microtime(true));
define('AVA_ROOT', dirname(__DIR__));

$app = require AVA_ROOT . '/bootstrap.php';
$request = Ava\Http\Request::capture();
$response = $app->handle($request);
$response->withHeader('X-Render-Time', round((microtime(true) - AVA_START) * 1000, 1) . 'ms')->send();
