<?php

declare(strict_types=1);

namespace LovelyWedding\Controller;

use LovelyWedding\Http\Request;
use LovelyWedding\Http\Response;
use LovelyWedding\Repository\NewsletterRepository;
use LovelyWedding\Service\CsrfTokenManager;
use LovelyWedding\Service\Mailer;
use LovelyWedding\Validation\NewsletterValidator;
use Psr\Log\LoggerInterface;

final class NewsletterController
{
    public function __construct(
        private readonly CsrfTokenManager $csrf,
        private readonly NewsletterRepository $repo,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $newsletterFrom,
    ) {
    }

    public function subscribe(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return Response::json(['status' => 'error', 'message' => 'Méthode non autorisée'], 405);
        }

        // CSRF is only enforced when the client sent a token; the legacy newsletter form
        // does not embed one. The session-bound token is still available client-side
        // for callers that include it (livre d'or form posts).
        $csrfToken = $request->post('_csrf');
        if ($csrfToken !== null && !$this->csrf->isValid('newsletter', $csrfToken)) {
            return Response::json(['status' => 'error', 'error' => true, 'message' => 'CSRF invalide'], 400);
        }

        $validator = new NewsletterValidator();
        $input = ['email' => $request->post('email')];
        if (!$validator->validate($input)) {
            return Response::json([
                'status'  => 'error',
                'error'   => true,
                'message' => array_values($validator->errors())[0] ?? 'Email invalide',
            ]);
        }

        $email = (string) $input['email'];
        if ($this->repo->existsByEmail($email)) {
            return Response::json([
                'status'  => 'error',
                'error'   => true,
                'message' => 'Vous êtes déjà inscrit',
            ]);
        }

        try {
            $this->repo->insert($email, $request->clientIp());
        } catch (\Throwable $e) {
            $this->logger->error('Newsletter insert failed', ['exception' => $e->getMessage()]);

            return Response::json([
                'status'  => 'error',
                'error'   => true,
                'message' => "Erreur d'inscription",
            ]);
        }

        $this->safeConfirm($email);

        return Response::json([
            'status'  => 'ok',
            'error'   => false,
            'message' => 'Inscription prise en compte',
        ]);
    }

    private function safeConfirm(string $email): void
    {
        try {
            $body = "Bonjour,\n\nVotre inscription à la newsletter a bien été prise en compte.\n"
                . "Vous serez prévenu à chaque nouvelle news.\n\n"
                . "À très vite !\nAudrey et Ludovic\ncontact@lovelywedding.fr";
            $this->mailer->send($this->newsletterFrom, $email, '[Lovelywedding] Inscription à la newsletter', $body);
        } catch (\Throwable $e) {
            $this->logger->error('Newsletter confirmation mail failed', ['exception' => $e->getMessage()]);
        }
    }
}
