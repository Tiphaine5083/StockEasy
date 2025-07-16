<?php
    namespace App\Controllers;

use App\Controllers\PublicController as ControllersPublicController;
use App\Core\AbstractController;
use App\Models\UserModel;

    class AuthController extends AbstractController
    {
        public function showLogin() {
            if (isset($_SESSION['user'])) {
                $pc = new ControllersPublicController();
                $pc->showStockHome();
                return;
            } else {
                require_once __DIR__ . '/../Views/login.phtml';
            }
        }

        public function login() {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                if (!empty(trim($_POST['email'])) && !empty(trim($_POST['password']))) {
                    $email = trim($_POST['email']);
                    $password = trim($_POST['password']);

                    $userModel = new UserModel();
                    $user = $userModel->findByEmail($email);

                    if ($user === null || !$user['active'] || !password_verify($password, $user['password'])) {
                        header('Location: index.php?route=login&error=Identifiants invalides');
                        exit();
                    } else {
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'first_name' => $user['first_name'],
                            'email' => $user['email'],
                            'role' => $user['id_role'],
                            ];
                        
                        header('Location: index.php?route=stock-home');
                        exit();
                    }
                } else {
                    header('Location: index.php?route=login&error=Paramètres manquants');
                    exit();
                }
            } else {
                header('Location: index.php?route=login&error=Paramètres manquants');
                exit();
            }
        }
        
        public function logout() {
            $_SESSION = [];
            session_destroy();
            header('Location: index.php?route=login&success=Déconnecté avec succès');
            exit();
        }
    }