<?php
    namespace App\Controllers;

use App\Controllers\PublicController;
use App\Core\AbstractController;
use App\Models\UserModel;

    class AuthController extends AbstractController
    {
        public function showLogin() {
            if (isset($_SESSION['user'])) {
                $pc = new PublicController();
                $pc->showStockHome();
                return;
            } else {
                require_once __DIR__ . '/../Views/login.phtml';
            }
        }

        public function login()
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
                        'id'         => $user['id'],
                        'first_name'=> $user['first_name'],
                        'email'     => $user['email'],
                        'role'      => $user['role_name'],
                    ];

                    // $_SESSION['success'] = 'Connexion réussie.';
                    header('Location: index.php?route=home');
                    exit();
                }

                $_SESSION['error'] = 'Paramètres manquants.';
                header('Location: index.php?route=login');
                exit();
            }

            $_SESSION['error'] = 'Paramètres manquants.';
            header('Location: index.php?route=login');
            exit();
        }
        
        public function logout() {
            $_SESSION = [];
            session_destroy();
            header('Location: index.php?route=login&success=Déconnecté avec succès');
            exit();
        }
    }