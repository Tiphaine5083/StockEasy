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
     * Process the login form submission.
     *
     * - Validates email and password.
     * - Verifies user existence and credentials.
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
                    header('Location: index.php?route=login');
                    exit();
                }

                $_SESSION['user'] = [
                    'id'          => $user['id'],
                    'first_name'  => $user['first_name'],
                    'email'       => $user['email'],
                    'role'        => $user['role_name'],
                ];

                header('Location: index.php?route=home');
                exit();
            }

            $_SESSION['error'] = 'Veuillez remplir tous les champs.';
            header('Location: index.php?route=login');
            exit();
        }

        $_SESSION['error'] = 'Paramètres manquants.';
        header('Location: index.php?route=login');
        exit();
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
