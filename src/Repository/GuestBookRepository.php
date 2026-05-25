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

        return false === $row ? null : $row;
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
