<?php

namespace App\Models;

use App\Core\AbstractModel;

/**
 * LogModel
 *
 * Handles all database interactions related to unified system and modification logs.
 * This model stores both system-level events (e.g. login, API errors) and CRUD-related data changes.
 *
 * @method \PDO getPdo() Returns the PDO instance from the AbstractModel
 */
class LogModel extends AbstractModel
{
    /**
     * LogModel constructor.
     * Sets the default table to 'log'.
     */
    public function __construct()
    {
        parent::__construct('log');
    }

    /**
     * Insert a new system log entry
     *
     * @param string $context e.g. 'login', 'api', 'error'
     * @param string $message Log message content
     * @param int|null $userId Optional user ID
     * @param string|null $firstname Optional user firstname
     * @param string|null $lastname Optional user lastname
     * @return void
     */
    public function logSystem(string $context, string $message, ?int $userId = null, ?string $firstname = null, ?string $lastname = null): void
    {
        $sql = "INSERT INTO log (
                    log_type, context, id_user, user_firstname, user_lastname,
                    log_date, ip_address, message
                ) VALUES (
                    'system', :context, :id_user, :firstname, :lastname,
                    NOW(), :ip, :message
                )";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute([
                'context'   => $context,
                'id_user'   => $userId,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'message'   => $message
            ]);
        } catch (\PDOException $e) {
            error_log('Erreur logSystem : ' . $e->getMessage());
        }
    }

    /**
     * Insert a new modification log entry
     *
     * @param string $table Target table name
     * @param int $recordId ID of the affected record
     * @param string $action 'create', 'update', 'delete' or 'archive'
     * @param int|null $userId Optional user ID
     * @param string|null $firstname Optional user firstname
     * @param string|null $lastname Optional user lastname
     * @param array $before Optional previous state (associative array)
     * @param array $after Optional new state (associative array)
     * @return void
     */
    public function logModification(string $table, int $recordId, string $action, ?int $userId = null, ?string $firstname = null, ?string $lastname = null, array $before = [], array $after = []): void 
    {
        $sql = "INSERT INTO log (
                    log_type, context, table_target, record_id,
                    id_user, user_firstname, user_lastname,
                    log_date, ip_address, message,
                    content_before, content_after
                ) VALUES (
                    'modification', :context, :table_target, :record_id,
                    :id_user, :firstname, :lastname,
                    NOW(), :ip, :message,
                    :before, :after
                )";

        $query = $this->getPdo()->prepare($sql);
        $query->execute([
            'context'      => $action,
            'table_target' => $table,
            'record_id'    => $recordId,
            'id_user'      => $userId,
            'firstname'    => $firstname,
            'lastname'     => $lastname,
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
            'message'      => ucfirst($action) . " on $table ID $recordId",
            'before'       => !empty($before) ? json_encode($before) : null,
            'after'        => !empty($after) ? json_encode($after) : null
        ]);
    }

    /**
     * Get system logs filtered by context, user full name, and/or date range.
     *
     * @param array $filters Optional filters:
     *  - 'context' => string (e.g. 'login')
     *  - 'user' => string (full name: "Firstname Lastname")
     *  - 'date_start' => string (YYYY-MM-DD)
     *  - 'date_end' => string (YYYY-MM-DD)
     * @return array List of filtered system logs (most recent first).
     */
    public function getAllSystemLogs(array $filters = []): array
    {
        $sql = "SELECT * FROM log WHERE log_type = 'system'";
        $params = [];

        if (!empty($filters['context'])) {
            $sql .= " AND context = :context";
            $params[':context'] = $filters['context'];
        }

        if (!empty($filters['user'])) {
            $sql .= " AND CONCAT(user_firstname, ' ', user_lastname) = :user";
            $params[':user'] = $filters['user'];
        }

        if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
            $sql .= " AND log_date BETWEEN :start AND :end";
            $params[':start'] = $filters['date_start'] . ' 00:00:00';
            $params[':end']   = $filters['date_end'] . ' 23:59:59';
        } elseif (!empty($filters['date_start'])) {
            $sql .= " AND log_date >= :start";
            $params[':start'] = $filters['date_start'] . ' 00:00:00';
        } elseif (!empty($filters['date_end'])) {
            $sql .= " AND log_date <= :end";
            $params[':end'] = $filters['date_end'] . ' 23:59:59';
        }

        $sql .= " ORDER BY log_date DESC";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get distinct users who have system logs.
     *
     * @return array List of users with logs (first_name + last_name).
     */
    public function getUsersByLogType(string $logType): array
    {
        $sql = "SELECT DISTINCT user_firstname, user_lastname 
                FROM log 
                WHERE log_type = 'system'
                ORDER BY user_lastname, user_firstname";
        try {
            $query = $this->getPdo()->query($sql);
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Retrieve the distinct context values used in the logs for a given log type.
     *
     * This is used to dynamically generate the list of available context filters
     * (e.g., 'login', 'logout', 'create', etc.) based on the actual data stored
     * in the `log` table for the given `log_type`.
     *
     * @param string $logType The type of logs to filter on ('system' or 'modification').
     * @return array An array of unique context strings ordered alphabetically.
     */
    public function getContextsByType(string $logType): array
    {
        $sql = "SELECT DISTINCT context FROM log WHERE log_type = :log_type ORDER BY context ASC";
        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute([':log_type' => $logType]);
            return array_column($query->fetchAll(\PDO::FETCH_ASSOC), 'context');
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get all modification logs ordered by date (desc)
     *
     * @return array
     */
    public function getAllModificationLogs(array $filters = []): array
    {
        $sql = "SELECT * FROM log WHERE log_type = 'modification'";
        $params = [];

        if (!empty($filters['context'])) {
            $sql .= " AND context = :context";
            $params[':context'] = $filters['context'];
        }

        if (!empty($filters['user'])) {
            $sql .= " AND CONCAT(user_firstname, ' ', user_lastname) = :user";
            $params[':user'] = $filters['user'];
        }

        if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
            $sql .= " AND log_date BETWEEN :start AND :end";
            $params[':start'] = $filters['date_start'] . ' 00:00:00';
            $params[':end']   = $filters['date_end'] . ' 23:59:59';
        } elseif (!empty($filters['date_start'])) {
            $sql .= " AND log_date >= :start";
            $params[':start'] = $filters['date_start'] . ' 00:00:00';
        } elseif (!empty($filters['date_end'])) {
            $sql .= " AND log_date <= :end";
            $params[':end'] = $filters['date_end'] . ' 23:59:59';
        }

        $sql .= " ORDER BY log_date DESC";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }
}