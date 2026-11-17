<?php
namespace App\Models;

use Database;
use PDO;

class Branch {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM branches ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $branchName, string $address): int {
        $stmt = $this->db->prepare("INSERT INTO branches (branch_name, address) VALUES (?, ?)");
        $stmt->execute([$branchName, $address]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $branchName, string $address): bool {
        $stmt = $this->db->prepare("UPDATE branches SET branch_name = ?, address = ? WHERE id = ?");
        return $stmt->execute([$branchName, $address, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM branches WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM branches")->fetchColumn();
    }
}
