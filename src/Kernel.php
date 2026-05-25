<?php

declare(strict_types=1);

namespace LovelyWedding;

use Dotenv\Dotenv;
use LovelyWedding\Controller\GuestBookController;
use LovelyWedding\Controller\HomeController;
use LovelyWedding\Controller\NewsletterController;
use LovelyWedding\Http\Request;
use LovelyWedding\Http\Response;
use LovelyWedding\Http\Router;
use LovelyWedding\Repository\GuestBookRepository;
use LovelyWedding\Repository\NewsletterRepository;
use LovelyWedding\Service\CsrfTokenManager;
use LovelyWedding\Service\Database;
use LovelyWedding\Service\Mailer;
use LovelyWedding\Service\RemoteImageInspector;
use LovelyWedding\Template\SmartyRenderer;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class Kernel
{
    private readonly string $projectDir;
    private LoggerInterface $logger;
    private Database $database;
    private Mailer $mailer;
    private SmartyRenderer $renderer;
    private CsrfTokenManager $csrf;
    private RemoteImageInspector $imageInspector;

    public function __construct(string $projectDir)
    {
        $this->projectDir = rtrim($projectDir, '/');
        $this->boot();
    }

    public function handle(Request $request): Response
    {
        /** @var array<string, array{0: class-string, 1: string}> $routes */
        $routes = require $this->projectDir.'/config/routes.php';
        $router = new Router($routes);
        $match = $router->match($request->path());
        if (null === $match) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        [$class, $method] = $match;
        $controller = $this->instantiateController($class);

        /** @var Response $response */
        $response = $controller->{$method}($request);

        return $response;
    }

    private function boot(): void
    {
        $envFile = $this->projectDir.'/.env';
        if (is_file($envFile)) {
            Dotenv::createImmutable($this->projectDir)->safeLoad();
        }

        $env = (string) ($_ENV['APP_ENV'] ?? 'prod');
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

        error_reporting(E_ALL);
        ini_set('display_errors', ('prod' !== $env && $debug) ? '1' : '0');
        ini_set('log_errors', '1');

        $this->logger = new Logger('app');
        $this->logger->pushHandler(new RotatingFileHandler($this->projectDir.'/var/log/app.log', 14, Logger::DEBUG));

        $this->database = new Database(
            host: (string) ($_ENV['DB_HOST'] ?? 'localhost'),
            port: (int) ($_ENV['DB_PORT'] ?? 3306),
            name: (string) ($_ENV['DB_NAME'] ?? 'lovelywedding'),
            user: (string) ($_ENV['DB_USER'] ?? 'root'),
            pass: (string) ($_ENV['DB_PASS'] ?? ''),
        );

        $this->mailer = new Mailer((string) ($_ENV['MAILER_DSN'] ?? 'null://null'));
        $this->renderer = new SmartyRenderer(
            $this->projectDir.'/templates',
            $this->projectDir.'/var/cache/smarty',
        );
        $this->csrf = new CsrfTokenManager();
        $this->imageInspector = new RemoteImageInspector();
    }

    /**
     * @param class-string $class
     */
    private function instantiateController(string $class): object
    {
        return match ($class) {
            HomeController::class => new HomeController(),
            GuestBookController::class => new GuestBookController(
                $this->renderer,
                $this->csrf,
                new GuestBookRepository($this->database),
                $this->imageInspector,
                $this->mailer,
                $this->logger,
                (string) ($_ENV['GUESTBOOK_NOTIFY_TO'] ?? 'admin@localhost'),
                (string) ($_ENV['NEWSLETTER_FROM'] ?? 'noreply@localhost'),
            ),
            NewsletterController::class => new NewsletterController(
                $this->csrf,
                new NewsletterRepository($this->database),
                $this->mailer,
                $this->logger,
                (string) ($_ENV['NEWSLETTER_FROM'] ?? 'noreply@localhost'),
            ),
            default => throw new \RuntimeException(sprintf('Unknown controller %s', $class)),
        };
    }
}
