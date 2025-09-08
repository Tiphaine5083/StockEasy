<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database
 *
 * Singleton class that manages the PDO database connection.
 * 
 * - Loads database configuration from the `.env` file.
 * - Creates a single PDO instance with secure default options.
 * - Provides global access to the PDO connection across the application.
 */
class Database
{
    /**
     * @var Database|null The single instance of the Database class
     */
    private static ?Database $instance = null;

    /**
     * @var PDO The PDO connection instance
     */   
    private PDO $pdo;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * Reads configuration values from `.env` and initializes
     * the PDO connection with error handling and safe defaults.
     *
     * @throws PDOException If the connection cannot be established
     */
    private function __construct()
    {
        $env = parse_ini_file(__DIR__ . '/../../.env');

        $host = $env['DB_HOST'];
        $port = $env['DB_PORT'];
        $dbname = $env['DB_NAME'];
        $user = $env['DB_USER'];
        $pass = $env['DB_PASS'];

        try {
            $this->pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            http_response_code(503);
            exit("Service temporairement indisponible");
        }
    }

    /**
     * Get the singleton instance of the Database class.
     *
     * @return Database The single instance of the Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection instance.
     *
     * @return PDO The PDO connection object
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
