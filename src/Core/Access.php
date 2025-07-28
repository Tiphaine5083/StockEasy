<?php

namespace App\Core;

class Access
{
    /**
     * Check if the current user has the required permission.
     *
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(string $permission): bool
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role'])) {
            return false;
        }

        $userRole = $_SESSION['user']['role'];

        $permissions = require __DIR__ . '/permissions.php';

        if (isset($permissions[$permission])) {
            return in_array($userRole, $permissions[$permission], true);
        }

        return false;
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}
