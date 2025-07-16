<?php

namespace App\Models;

use App\Core\AbstractModel;

/**
 * UserModel
 *
 * Handles all database queries related to user management.
 *
 * @method \PDO getPdo()
 */
class UserModel extends AbstractModel
{
    /**
     * UserModel constructor.
     * Sets the default table to 'user'.
     */
    public function __construct() {
        parent::__construct('user');
    }

    public function findByEmail(string $email): ?array {
        $sql = 'SELECT * FROM user WHERE email = :email LIMIT 1';
        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute([
                'email' => $email,
            ]);
            $result = $query->fetch(\PDO::FETCH_ASSOC);
            if ($result === false) {
                return null;
            } else {
                return $result;
            }
        } catch (\PDOException $e) {
            return null;
        } 
    }

    public function findByStatusWithFilters(string $active, array $filters = []): array
    {
        $sql = "
            SELECT user.*, role.role_name
            FROM user
            JOIN role ON user.id_role = role.id
            WHERE user.active = :active
            ";

        $params = [':active' => $active];

        if (!empty($filters['name'])) {
            $sql .= " AND (user.first_name LIKE :name OR user.last_name LIKE :name)";
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['email'])) {
            $sql .= " AND user.email LIKE :email";
            $params[':email'] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['role'])) {
            $sql .= " AND role.role_name = :role";
            $params[':role'] = $filters['role'];
        }

        $sql .= " ORDER BY user.last_name ASC, user.first_name ASC";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute($params);
            return $query->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function toggleActiveStatus(int $id): bool
    {
        try {
            $sql = "SELECT active FROM user WHERE id = :id LIMIT 1";
            $query = $this->getPdo()->prepare($sql);
            $query->execute([':id' => $id]);
            $result = $query->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            $newActive = $result['active'] ? 0 : 1;

            $sql = "UPDATE user SET active = :newActive WHERE id = :id";
            $query = $this->getPdo()->prepare($sql);
            $query->execute([
                ':newActive' => $newActive,
                ':id' => $id,
            ]);

            return $query->rowCount() > 0;

        } catch (\PDOException $e) {
            return false;
        }
    }
}