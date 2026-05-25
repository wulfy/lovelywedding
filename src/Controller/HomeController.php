<?php

declare(strict_types=1);

namespace LovelyWedding\Controller;

use LovelyWedding\Http\Request;
use LovelyWedding\Http\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        $indexHtml = dirname(__DIR__, 2).'/public/index.html';
        if (is_file($indexHtml)) {
            return Response::html((string) file_get_contents($indexHtml));
        }

        return Response::html('<h1>LovelyWedding</h1>');
    }
}
