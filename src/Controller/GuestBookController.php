<?php

declare(strict_types=1);

namespace LovelyWedding\Controller;

use LovelyWedding\Http\Request;
use LovelyWedding\Http\Response;
use LovelyWedding\Repository\GuestBookRepository;
use LovelyWedding\Service\CsrfTokenManager;
use LovelyWedding\Service\Mailer;
use LovelyWedding\Service\RemoteImageInspector;
use LovelyWedding\Service\StringSanitizer;
use LovelyWedding\Template\SmartyRenderer;
use LovelyWedding\Validation\GuestBookValidator;
use Psr\Log\LoggerInterface;

final class GuestBookController
{
    private const string CSRF_INTENT = 'guestbook';
    private const int MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly SmartyRenderer $renderer,
        private readonly CsrfTokenManager $csrf,
        private readonly GuestBookRepository $repo,
        private readonly RemoteImageInspector $imageInspector,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $notifyTo,
        private readonly string $notifyFrom,
    ) {
    }

    public function form(Request $request): Response
    {
        $ip = $request->clientIp();
        $previous = null;
        $idParam = $request->get('id');
        if ($idParam !== null && ctype_digit($idParam)) {
            $previous = $this->repo->findByIpAndId($ip, (int) $idParam);
        }

        return Response::html($this->renderer->render('guestbook/form.tpl', [
            'last_post_values' => $previous,
            'csrf_token'       => $this->csrf->token(self::CSRF_INTENT),
        ]));
    }

    public function read(Request $request): Response
    {
        $ip = $request->clientIp();
        $pages = $this->repo->findAll();
        foreach ($pages as &$page) {
            $page['editable'] = (isset($page['ip']) && $page['ip'] === $ip);
        }
        unset($page);

        return Response::html($this->renderer->render('guestbook/read.tpl', [
            'pages' => $pages,
        ]));
    }

    public function write(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return new Response('Method Not Allowed', 405);
        }

        if (!$this->csrf->isValid(self::CSRF_INTENT, $request->post('_csrf'))) {
            $this->logger->warning('CSRF rejected on guestbook write', ['ip' => $request->clientIp()]);

            return Response::html($this->renderer->render('guestbook/error.tpl', [
                'errors'     => ['Jeton de sécurité invalide. Rechargez la page.'],
                'jsonFields' => json_encode([]),
            ]), 400);
        }

        $input = [
            'nom'     => $request->post('nom'),
            'prenom'  => $request->post('prenom'),
            'email'   => $request->post('email'),
            'ville'   => $request->post('ville'),
            'message' => $request->post('message'),
            'image'   => $request->post('image'),
        ];

        $validator = new GuestBookValidator();
        if (!$validator->validate($input)) {
            return Response::html($this->renderer->render('guestbook/error.tpl', [
                'errors'     => array_values($validator->errors()),
                'jsonFields' => json_encode(array_keys($validator->errors())),
            ]));
        }

        $image = trim((string) $input['image']);
        if ($image !== '') {
            $info = $this->imageInspector->inspect($image);
            if ($info === null) {
                return Response::html($this->renderer->render('guestbook/error.tpl', [
                    'errors'     => ["URL d'image refusée (inaccessible ou hôte interdit)."],
                    'jsonFields' => json_encode(['image']),
                ]));
            }
            if ($info['length'] > self::MAX_IMAGE_BYTES) {
                return Response::html($this->renderer->render('guestbook/error.tpl', [
                    'errors'     => ['Image trop volumineuse (limite 5 Mo).'],
                    'jsonFields' => json_encode(['image']),
                ]));
            }
            if (!str_starts_with(strtolower($info['content_type']), 'image/')) {
                return Response::html($this->renderer->render('guestbook/error.tpl', [
                    'errors'     => ["L'URL ne renvoie pas une image."],
                    'jsonFields' => json_encode(['image']),
                ]));
            }
        }

        $ip = $request->clientIp();
        $id = $request->post('id');
        $existing = null;
        if ($id !== null && ctype_digit($id)) {
            $existing = $this->repo->findByIpAndId($ip, (int) $id);
        }

        $data = [
            'nom'     => StringSanitizer::removeAccents((string) $input['nom']),
            'email'   => (string) $input['email'],
            'ville'   => (string) $input['ville'],
            'message' => (string) $input['message'],
            'image'   => $image === '' ? null : $image,
            'ip'      => $ip,
        ];

        if ($existing !== null) {
            $this->repo->update((int) $existing['id'], $data);
            $update = true;
        } else {
            $this->repo->insert($data);
            $update = false;
        }

        $this->safeNotify($data['nom'], $data['message'], $data['image']);

        return Response::html($this->renderer->render('guestbook/success.tpl', [
            'update' => $update,
        ]));
    }

    private function safeNotify(string $nom, string $message, ?string $image): void
    {
        try {
            $body = sprintf(
                "Nouveau message du livre d'or\n\nNom: %s\nImage: %s\n\nMessage:\n%s",
                $nom,
                $image ?? '(aucune)',
                $message,
            );
            $this->mailer->send($this->notifyFrom, $this->notifyTo, "[Lovelywedding] Nouveau message livre d'or", $body);
        } catch (\Throwable $e) {
            $this->logger->error('Guestbook notification failed', ['exception' => $e->getMessage()]);
        }
    }
}
