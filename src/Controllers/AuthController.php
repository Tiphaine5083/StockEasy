<?php

namespace App\Controllers;

use App\Controllers\PublicController;
use App\Core\AbstractController;
use App\Models\UserModel;

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
            $publicController = new PublicController();
            $publicController->showHome();
            return;
        } else {
            $title = 'Connexion';
            require_once __DIR__ . '/../Views/login.phtml';
        }
    }

    /**
     * Display the password reset page for users with a temporary password.
     *
     * Only accessible if the user's password has never been updated.
     * Otherwise, redirects to the login screen.
     *
     * @return void
     */
    public function showPasswordReset(): void
    {
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
                $user = $userModel->findByEmail($email);

                if ($user === null || !$user['active'] || !password_verify($password, $user['password'])) {
                    $_SESSION['error'] = 'Identifiants invalides.';
                    $this->redirectToRoute('login');
                    return;
                }

                if (empty($user['last_password_update'])) {
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
                    'email'       => $user['email'],
                    'role'        => $user['role_name'],
                ];

                $this->redirectToRoute('home');
                return;
            }

            $_SESSION['error'] = 'Veuillez remplir tous les champs.';
            $this->redirectToRoute('login');
            return;
        }

        $_SESSION['error'] = 'Paramètres manquants.';
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
        $_SESSION = [];
        session_destroy();
        session_start(); 
        $_SESSION['success'] = 'Déconnexion réussie.';
        header('Location: index.php?route=login');
        exit();
    }
}
