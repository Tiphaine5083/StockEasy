<?php

namespace App\Models;

use App\Core\AbstractModel;

/**
 * Model for user-related database operations.
 *
 * Provides methods for creating, reading, updating, and validating users.
 *
 * @method \PDO getPdo() Returns the PDO instance for executing SQL queries.
 */
class UserModel extends AbstractModel
{
    /**
     * Class constructor.
     * Sets the associated table to 'user'.
     */
    public function __construct() {
        parent::__construct('user');
    }

    /**
     * Find a user by email address.
     *
     * @param string $email The user's email.
     * @return array|null The user data or null if not found or on error.
     */
    public function findByEmail(string $email): ?array 
    {
        $sql = 'SELECT user.*, role.role_name
                FROM user 
                JOIN role ON user.id_role = role.id
                WHERE user.email = :email 
                LIMIT 1';
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

    /**
     * Find users by status and optional filters (name, email, role).
     *
     * @param int $active 1 for active users, 0 for inactive.
     * @param array $filters Optional filters: name, email, role.
     * @return array List of matching users.
     */
    public function findByStatusWithFilters(int $active, array $filters = []): array
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

    /**
     * Toggle the active status of a user (active <-> inactive).
     *
     * @param int $id The user ID.
     * @return bool True if the update succeeded, false otherwise.
     */
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

    /**
     * Check if a user can be deleted (only if role is 'intern' or 'guest').
     *
     * @param int $userId The user ID.
     * @return bool True if the user can be deleted, false otherwise.
    */
    public function canBeDeleted(int $userId): bool
    {
        try {
            $sql = "
                SELECT role.role_name
                FROM user
                JOIN role ON user.id_role = role.id
                WHERE user.id = :userId
                LIMIT 1
            ";

            $query = $this->getPdo()->prepare($sql);
            $query->execute([':userId' => $userId]);
            $result = $query->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            $role = strtolower(trim($result['role_name']));
            return in_array($role, ['intern', 'guest'], true);

        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Delete all permissions associated with a given user.
     *
     * @param int $userId The user ID.
     * @return void
     */
    public function removeUserPermissions(int $userId): void
    {
        try {
            $sql = "DELETE FROM user_permission WHERE id_user = :userId";
            $query = $this->getPdo()->prepare($sql);
            $query->execute([':userId' => $userId]);
        } catch (\PDOException $e) {
            error_log('Error while removing permissions: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user and all associated permissions in a single transaction.
     *
     * @param int $userId The user ID.
     * @return bool True on success, false on failure.
     */
    public function deleteUserWithPermissions(int $userId): bool
    {
        try {
            $pdo = $this->getPdo();
            $pdo->beginTransaction();

            if (!$this->canBeDeleted($userId)) {
                throw new \Exception('User cannot be deleted.');
            }

            $this->removeUserPermissions($userId);
            $this->remove($userId);

            $pdo->commit();
            return true;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Delete user failed: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Update user data (with optional password change).
     *
     * @param array $data The data to update, including 'id' and optionally 'password'.
     * @return bool True on success, false on failure.
     */
    public function updateUserInformations(array $data): bool
    {
        try {
            if (!isset($data['id']) || !ctype_digit((string)$data['id'])) {
                throw new \InvalidArgumentException('ID utilisateur invalide.');
            }
            $id = (int)$data['id'];
            unset($data['id']);

            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']); // Ne pas toucher au mdp si vide
            }

            $result = $this->update($id, $data);

            if (!$result) {
                error_log('Mise à jour utilisateur a échoué pour ID: ' . $id);
            }
            return $result;

        } catch (\Throwable $e) {
            error_log('Erreur userUpdate: ' . $e->getMessage());
            return false;
        }
    }

}