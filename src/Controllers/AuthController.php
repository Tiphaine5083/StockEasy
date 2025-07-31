<?php

namespace App\Controllers;

use App\Controllers\PublicController;
use App\Core\AbstractController;
use App\Core\Access;
use App\Models\UserModel;
use App\Models\LogModel;

/**
 * AuthController
 *
 * Manages user authentication and session handling.
 */
class AuthController extends AbstractController
{
    /**
     * Display the login page.
     *
     * If the user is already authenticated, redirect to the main home page.
     * Otherwise, load the login view with appropriate title.
     *
     * @return void
     */
    public function showLogin(): void
    {
        if (isset($_SESSION['user'])) {
            if (!Access::hasRole('guest')) {
                $publicController = new PublicController();
                $publicController->showHome();
                return;
            }

            $_SESSION['error'] = "Accès limité. Veuillez vous reconnecter avec un compte autorisé";
        }

        $title = 'Connexion';
        require_once __DIR__ . '/../Views/login.phtml';
    }

    /**
     * Display the password reset form for users with a temporary password.
     *
     * This method is only accessible to authenticated users whose password
     * has never been updated (i.e., 'last_password_update' is null).
     * If the user is not authenticated or already updated his password,
     * he is redirected to the login screen.
     *
     * A CSRF token is generated if it doesn't exist.
     *
     * @return void
     */
    public function showPasswordReset(): void
    {
        if (Access::hasRole('guest')) {
            $this->denyAccess("Tentative d'accès à la réinitialisation sans permission");
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (!isset($_SESSION['user'])) {
            $authController = new AuthController();
            $authController->showLogin();
            return;
        }

        $userModel = new UserModel();
        $user = $userModel->find((int)$_SESSION['user']['id']);

        if (!$user || $user['last_password_update'] !== null) {
            $authController = new AuthController();
            $authController->showLogin();
            return;
        }

        $title = 'Réinitialisation du mot de passe';
        require_once __DIR__ . '/../Views/password-reset.phtml';
    }

    /**
     * Process the login form submission.
     *
     * - Validates email and password.
     * - Verifies user existence and credentials.
     * - If password is provisional, redirects to password reset.
     * - Records last login timestamp.
     * - Starts user session on success.
     * - Redirects to login page with error message on failure.
     *
     * @return void
     */
    public function login(): void
    {
        if (isset($_POST['email'], $_POST['password'])) {
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);

            if (!empty($email) && !empty($password)) {
                $userModel = new UserModel();
                $logModel = new LogModel();
                $user = $userModel->findByEmail($email);

            if ($user === null) {
                $logModel->logSystem('login', "Échec de connexion : email inconnu ($email)");
                $_SESSION['error'] = 'Identifiants invalides';
                $this->redirectToRoute('login');
                return;
            }

            if (!$user['active']) {
                $logModel->logSystem(
                    'login',
                    "Échec de connexion : compte inactif ($email)",
                    $user['id'],
                    $user['first_name'],
                    $user['last_name'] ?? null,
                );
                $_SESSION['error'] = 'Identifiants invalides';
                $this->redirectToRoute('login');
                return;
            }

            if (!password_verify($password, $user['password'])) {
                $logModel->logSystem(
                    'login',
                    "Échec de connexion : mot de passe incorrect ($email)",
                    $user['id'],
                    $user['first_name'],
                    $user['last_name'] ?? null,
                );
                $_SESSION['error'] = 'Identifiants invalides';
                $this->redirectToRoute('login');
                return;
            }

                if (empty($user['last_password_update'])) {
                    $userModel->updateLastLogin((int)$user['id']);
                    $_SESSION['user'] = [
                        'id'          => $user['id'],
                        'first_name'  => $user['first_name'],
                        'email'       => $user['email'],
                        'role'        => $user['role_name'],
                    ];
                    $this->redirectToRoute('password-reset');
                    return;
                }

                $userModel->updateLastLogin((int)$user['id']);

                $_SESSION['user'] = [
                    'id'          => $user['id'],
                    'first_name'  => $user['first_name'],
                    'last_name'  => $user['last_name'],
                    'email'       => $user['email'],
                    'role'        => $user['role_name'],
                ];

                $logModel->logSystem(
                    'login',
                    'Connexion réussie',
                    $user['id'],
                    $user['first_name'],
                    $user['last_name'] ?? null,
                );

                $this->redirectToRoute('home');
                return;
            }

            $_SESSION['error'] = 'Veuillez remplir tous les champs';
            $this->redirectToRoute('login');
            return;
        }

        $_SESSION['error'] = 'Paramètres manquants';
        $this->redirectToRoute('login');
    }

    /**
     * Log out the current user.
     *
     * - Clears and destroys the session.
     * - Starts a new session to display success message.
     * - Redirects to login page.
     *
     * @return void
     */
    public function logout(): void
    {
        if (isset($_SESSION['user'])) {
            $userId    = $_SESSION['user']['id'] ?? null;
            $firstName = $_SESSION['user']['first_name'] ?? null;
            $lastName  = $_SESSION['user']['last_name'] ?? null;

            $logModel = new LogModel();
            $logModel->logSystem(
                'logout',
                'Déconnexion réussie',
                $userId,
                $firstName,
                $lastName
            );
        }

        $_SESSION = [];
        session_destroy();
        session_start(); 
        $_SESSION['success'] = 'Déconnexion réussie';
        $this->redirectToRoute('login');
    }
}
