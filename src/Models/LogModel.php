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
    public function logModification(
        string $table,
        int $recordId,
        string $action,
        ?int $userId = null,
        ?string $firstname = null,
        ?string $lastname = null,
        array $before = [],
        array $after = []
    ): void {
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
     * Get all system logs ordered by date (desc)
     *
     * @return array
     */
    public function getAllSystemLogs(): array
    {
        $sql = "SELECT * FROM log WHERE log_type = 'system' ORDER BY log_date DESC";
        return $this->getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all modification logs ordered by date (desc)
     *
     * @return array
     */
    public function getAllModificationLogs(): array
    {
        $sql = "SELECT * FROM log WHERE log_type = 'modification' ORDER BY log_date DESC";
        return $this->getPdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}