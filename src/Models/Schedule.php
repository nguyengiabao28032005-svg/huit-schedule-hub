<?php
namespace App\Models;

use Database;
use PDO;

class Schedule {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(array $filters = []): array {
        $sql = "SELECT s.*, 
                       t.full_name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_phone, t.expertise AS teacher_expertise,
                       b.branch_name, b.address AS branch_address
                FROM schedules s
                JOIN teachers t ON s.teacher_id = t.id
                JOIN branches b ON s.branch_id = b.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['branch_id'])) {
            $sql .= " AND s.branch_id = ?";
            $params[] = (int)$filters['branch_id'];
        }

        if (!empty($filters['teacher_id'])) {
            $sql .= " AND s.teacher_id = ?";
            $params[] = (int)$filters['teacher_id'];
        }

        if (!empty($filters['day_of_week'])) {
            $sql .= " AND s.day_of_week = ?";
            $params[] = $filters['day_of_week'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.subject_name LIKE ? OR s.room LIKE ? OR t.full_name LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        // Sort by Day of week, then shift
        $sql .= " ORDER BY FIELD(s.day_of_week, 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'), s.shift_or_periods, s.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT s.*, 
                                           t.full_name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_phone, t.expertise AS teacher_expertise,
                                           b.branch_name, b.address AS branch_address
                                    FROM schedules s
                                    JOIN teachers t ON s.teacher_id = t.id
                                    JOIN branches b ON s.branch_id = b.id
                                    WHERE s.id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO schedules (teacher_id, branch_id, subject_name, room, day_of_week, shift_or_periods) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['teacher_id'],
            $data['branch_id'],
            $data['subject_name'],
            $data['room'],
            $data['day_of_week'],
            $data['shift_or_periods']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE schedules 
                                    SET teacher_id = ?, branch_id = ?, subject_name = ?, room = ?, day_of_week = ?, shift_or_periods = ?
                                    WHERE id = ?");
        return $stmt->execute([
            $data['teacher_id'],
            $data['branch_id'],
            $data['subject_name'],
            $data['room'],
            $data['day_of_week'],
            $data['shift_or_periods'],
            $id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getByTeacher(int $teacherId): array {
        return $this->all(['teacher_id' => $teacherId]);
    }

    public function getByBranch(int $branchId): array {
        return $this->all(['branch_id' => $branchId]);
    }

    public function count(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    }
}
