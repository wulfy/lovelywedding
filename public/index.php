<?php

declare(strict_types=1);

use LovelyWedding\Http\Request;
use LovelyWedding\Kernel;

require dirname(__DIR__) . 'vendor/autoload.php';

$kernel = new Kernel(dirname(__DIR__));
$kernel->handle(Request::fromGlobals())->send();
