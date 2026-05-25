<?php

declare(strict_types=1);

namespace LovelyWedding\Service;

use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

final class Mailer
{
    private readonly SymfonyMailer $inner;

    public function __construct(string $dsn)
    {
        $transport = Transport::fromDsn($dsn);
        $this->inner = new SymfonyMailer($transport);
    }

    public function send(string $from, string $to, string $subject, string $body, bool $html = false): void
    {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject);

        if ($html) {
            $email->html($body);
        } else {
            $email->text($body);
        }

        $this->inner->send($email);
    }
}
