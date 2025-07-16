<?php

namespace App\Models;

use App\Core\AbstractModel;

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
}