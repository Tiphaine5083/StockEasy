<?php

namespace App\Core;

/**
 * Access
 *
 * Provides static methods to manage user session access and role-based permissions.
 *
 * This class handles:
 * - Authentication checks (is the user logged in?)
 * - Permission checks (does the user's role allow this action?)
 *
 * Permissions are defined externally in `permissions.php` and matched against
 * the current user's role stored in the session.
 */
class Access
{
    
    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Check if the current user has the specified role.
     *
     * @param string $role The expected role (e.g. 'super_admin', 'admin', etc.).
     * @return bool True if the user has the role, false otherwise.
     */
    public static function hasRole(string $role): bool
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
    }

    /**
     * Check if the current user has one of the allowed roles.
     *
     * @param array $roles An array of allowed roles.
     * @return bool True if the user's role is in the list, false otherwise.
     */
    public static function hasOneRole(array $roles): bool
    {
        return isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], $roles, true);
    }

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
}
