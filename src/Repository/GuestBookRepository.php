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
             FROM livre_dor WHERE actif = 1 ORDER BY date ASC, id ASC'
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
        // Some rows were re-encoded by a botched UTF-8 migration, so they
        // contain &amp;lt;br /&amp;gt; that requires two decode passes.
        // Loop until stable, capped at 5 iterations to avoid runaway input.
        $decoded = $text;
        for ($i = 0; $i < 5; ++$i) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        // Normalize every line-ending variant to \n first so a stray \r never
        // splits a break apart.
        $withNewlines = (string) preg_replace('/\r\n?/', "\n", $decoded);

        // Legacy rows stored a single break as "<br />\r\n", so each <br /> may
        // be trailed by formatting whitespace and one newline that belong to the
        // same break. Fold each <br /> (plus that trailing whitespace/newline)
        // into a single \n. Two consecutive <br /> therefore become \n\n, which
        // preserves the distinction: one <br /> is a line return, two are a
        // paragraph break (a blank line).
        $withNewlines = (string) preg_replace('#<br\s*/?>[ \t]*\n?#i', "\n", $withNewlines);

        // Cap any run of blank lines at a single one so legacy spam of many
        // <br /> can't open a huge gap.
        return (string) preg_replace('/\n{3,}/', "\n\n", $withNewlines);
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
