<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class TracerService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Traces a student's history and returns activities for the timeline.
     */
    public function getHistory(int $studentId, string $filter = 'all'): array
    {
        $sql = "
            SELECT ta.*, DATE_FORMAT(ta.activity_date, '%M %d, %Y') as formatted_date
            FROM tracer_activities ta
        ";
        
        $params = ['student_id' => $studentId];
        
        // Database::secureQuery will inject "WHERE school_id = :school_id AND"
        $sql .= " WHERE student_id = :student_id";

        if ($filter !== 'all') {
            $sql .= " AND ta.activity_type = :filter";
            $params['filter'] = $filter;
        }

        $sql .= " ORDER BY ta.activity_date DESC, ta.created_at DESC";

        $stmt = $this->db->secureQuery($sql, $params);
        return $stmt->fetchAll();
    }

    public function getStudent(int $studentId): ?array
    {
        $sql = "SELECT * FROM students WHERE id = :id";
        $stmt = $this->db->secureQuery($sql, ['id' => $studentId]);
        return $stmt->fetch() ?: null;
    }
}
