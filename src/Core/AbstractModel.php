<?php

namespace App\Core;

use App\Core\Database;
use PDO;

/**
 * AbstractModel
 * 
 * Base class for all database models.
 * Provides generic CRUD operations for any table.
 */
abstract class AbstractModel
{
    /** @var PDO|null The PDO database connection */
    protected ?PDO $pdo = null;

    /** @var string The table name used by this model */
    protected string $table = '';

    /**
     * AbstractModel constructor.
     *
     * @param string $table The database table name.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Initialize the PDO connection.
     *
     * @return void
     */
    private function setPdo(): void
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Get the PDO connection (lazy-loaded).
     *
     * @return PDO The PDO connection.
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->setPdo();
        }

        return $this->pdo;
    }

    /**
     * Retrieve all rows from the table.
     *
     * @return array The list of all rows.
     */
    public function findAll(): array
    {
        try {
            $query = $this->getPdo()->prepare('SELECT * FROM ' . $this->table);
            $query->execute();
            return $query->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieve a single row by ID.
     *
     * @param int $id The row ID.
     * @return array|false The row data or false if not found.
     */
    public function find(int $id): array|false
    {
        try {
            $query = $this->getPdo()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
            $query->execute([':id' => $id]);
            return $query->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Remove a row by ID.
     *
     * @param int $id The row ID to delete.
     * @return void
     * @throws \PDOException If the delete fails.
     */
    public function remove(int $id): void
    {
        try {
            $query = $this->getPdo()->prepare('DELETE FROM ' . $this->table . ' WHERE id = :id');
            $query->execute([':id' => $id]);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * Find rows matching specific criteria.
     *
     * @param array $criteria Key-value pairs for WHERE conditions.
     * @return array The matching rows.
     */
    public function findBy(array $criteria): array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ';
        foreach ($criteria as $key => $value) {
            $sql .= "$key = :$key";
            if ($key !== array_key_last($criteria)) {
                $sql .= ' AND ';
            }
        }

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute($criteria);
            return $query->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get the ID of the last inserted row.
     *
     * @return int|null Last inserted ID, or null on failure.
     */
    public function getLastInsertId(): ?int
    {
        try {
            return (int) $this->getPdo()->lastInsertId();
        } catch (\PDOException $e) {
            return null;
        }
    }


    /**
     * Insert a new row into the table.
     *
     * @param array $data Key-value pairs for column names and values.
     * @return bool True on success, false on failure.
     */
    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        try {
            $query = $this->getPdo()->prepare($sql);
            return $query->execute($data);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Update an existing row by ID.
     *
     * @param int $id The row ID to update.
     * @param array $data Key-value pairs for columns to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $set = [];
            foreach ($data as $key => $value) {
                $set[] = "$key = :$key";
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = :id";
            $data['id'] = $id;

            $query = $this->getPdo()->prepare($sql);
            return $query->execute($data);
        } catch (\PDOException $e) {
            return false;
        }
    }
}