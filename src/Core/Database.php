<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private int $school_id;

    public function __construct(int $schoolId)
    {
        $this->school_id = $schoolId;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Load config from constants (keeping them for now as bridge)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new \Exception("Database connection failed.");
            }
        }
        return self::$instance;
    }

    /**
     * Prepares and executes a query, automatically injecting school_id filtering
     */
    public function secureQuery(string $sql, array $params = [], bool $isInsert = false)
    {
        $pdo = self::getInstance();
        
        // Simple logic to inject school_id if not present
        // In a real framework, you'd use a Query Builder.
        if (strpos(strtoupper($sql), 'WHERE') !== false) {
            $sql = str_replace('WHERE', 'WHERE school_id = :school_id AND ', $sql);
        } else {
            $sql .= ' WHERE school_id = :school_id';
        }

        $params['school_id'] = $this->school_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }

    public function getSchoolId(): int
    {
        return $this->school_id;
    }
}
