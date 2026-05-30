<?php

declare(strict_types=1);

namespace LovelyWedding\Repository;

use LovelyWedding\Service\Database;

final class NewsletterRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function existsByEmail(string $email): bool
    {
        $stmt = $this->db->pdo()->prepare('SELECT 1 FROM newsletter WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        return false !== $stmt->fetchColumn();
    }

    public function insert(string $email, string $ip): void
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO newsletter (email, ip, date, actif) VALUES (:email, :ip, NOW(), 1)'
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':ip', $ip);
        $stmt->execute();
    }

    /**
     * @return array<int, string>
     */
    public function findAllActive(): array
    {
        $stmt = $this->db->pdo()->query('SELECT email FROM newsletter WHERE actif = 1 ORDER BY id ASC');
        if (false === $stmt) {
            return [];
        }
        /** @var array<int, string> $emails */
        $emails = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $emails;
    }
}
