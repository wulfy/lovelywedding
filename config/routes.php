<?php

declare(strict_types=1);

use LovelyWedding\Controller\GuestBookController;
use LovelyWedding\Controller\HomeController;
use LovelyWedding\Controller\NewsletterController;

return [
    '/'                          => [HomeController::class, 'index'],
    '/index'                     => [HomeController::class, 'index'],
    '/livre_dor'                 => [GuestBookController::class, 'form'],
    '/livre_dor.php'             => [GuestBookController::class, 'form'],
    '/livre_dor_read'            => [GuestBookController::class, 'read'],
    '/livre_dor_read.php'        => [GuestBookController::class, 'read'],
    '/livre_dor_write'           => [GuestBookController::class, 'write'],
    '/livre_dor_write.php'       => [GuestBookController::class, 'write'],
    '/register_newsletter'       => [NewsletterController::class, 'subscribe'],
    '/register_newsletter.php'   => [NewsletterController::class, 'subscribe'],
];
