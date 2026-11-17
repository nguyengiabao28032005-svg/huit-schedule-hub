<?php
namespace App\Models;

use Database;
use PDO;

class Teacher {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM teachers ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM teachers WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM teachers WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $fullName, string $email, string $phone, string $expertise, string $password = 'password123'): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO teachers (full_name, email, phone, expertise, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $phone, $expertise, $hash]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $fullName, string $email, string $phone, string $expertise, ?string $password = null): bool {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE teachers SET full_name = ?, email = ?, phone = ?, expertise = ?, password = ? WHERE id = ?");
            return $stmt->execute([$fullName, $email, $phone, $expertise, $hash, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE teachers SET full_name = ?, email = ?, phone = ?, expertise = ? WHERE id = ?");
            return $stmt->execute([$fullName, $email, $phone, $expertise, $id]);
        }
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM teachers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    }
}
