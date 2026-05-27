<?php

declare(strict_types=1);

namespace LovelyWedding\Repository;

use LovelyWedding\Service\Database;

final class GuestBookRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT id, nom, email, ville, date, message, image, ip
             FROM livre_dor WHERE actif = 1 ORDER BY date DESC, id DESC'
        );
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        foreach ($rows as $i => $row) {
            $rows[$i] = $this->normalizeLegacyRow($row);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIpAndId(string $ip, int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT id, nom, email, ville, date, message, image, ip
             FROM livre_dor WHERE id = :id AND ip = :ip LIMIT 1'
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':ip', $ip, \PDO::PARAM_STR);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        return false === $row ? null : $this->normalizeLegacyRow($row);
    }

    /**
     * Legacy rows were inserted by the original PHP app, which ran htmlentities()
     * on every text field and used literal <br /> tags for line breaks. The modern
     * Smarty templates re-escape on output (escape_html=true) and apply |nl2br on
     * \n, so without normalization users see "&rsquo;" and "<br />" as raw text.
     *
     * Decoding entities and converting <br /> back to \n is idempotent for new
     * rows (plain UTF-8 text has no entities and no embedded <br /> tags).
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeLegacyRow(array $row): array
    {
        foreach (['nom', 'email', 'ville', 'message'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->normalizeLegacyText($row[$field]);
            }
        }

        return $row;
    }

    private function normalizeLegacyText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (string) preg_replace('#<br\s*/?>#i', "\n", $decoded);
    }

    public function existsByIp(string $ip): bool
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT 1 FROM livre_dor WHERE ip = :ip AND actif = 1 LIMIT 1'
        );
        $stmt->bindValue(':ip', $ip, \PDO::PARAM_STR);
        $stmt->execute();

        return false !== $stmt->fetchColumn();
    }

    /**
     * @param array{nom: string, email: string, ville: string, message: string, image: string|null, ip: string} $data
     */
    public function insert(array $data): int
    {
        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO livre_dor (nom, email, ville, date, message, image, ip, actif)
             VALUES (:nom, :email, :ville, NOW(), :message, :image, :ip, 1)'
        );
        $stmt->bindValue(':nom', $data['nom']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':ville', $data['ville']);
        $stmt->bindValue(':message', $data['message']);
        $stmt->bindValue(':image', $data['image']);
        $stmt->bindValue(':ip', $data['ip']);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param array{nom: string, email: string, ville: string, message: string, image: string|null, ip: string} $data
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->db->pdo()->prepare(
            'UPDATE livre_dor
             SET nom = :nom, email = :email, ville = :ville, date = NOW(), message = :message, image = :image
             WHERE id = :id AND ip = :ip'
        );
        $stmt->bindValue(':nom', $data['nom']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':ville', $data['ville']);
        $stmt->bindValue(':message', $data['message']);
        $stmt->bindValue(':image', $data['image']);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':ip', $data['ip']);
        $stmt->execute();
    }
}
