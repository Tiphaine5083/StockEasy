<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\UserModel;

/**
 * UserController
 *
 * Handles all user-related operations for data display, CRUD, and sync.
 */
class UserController extends AbstractController {

    public function userCreate(): void
    {
        try
        {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $_SESSION['error'] = 'Méthode non autorisée';
                $this->redirectToRoute('user-create');
            }

            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
            if (empty($first_name)) {
                throw new \Exception('Le prénom est obligatoire');
            }
            $first_name = preg_replace('/\s*-\s*/', '-', $first_name);
            $first_name = str_replace(' ', '-', $first_name);
            $first_name = strtolower($first_name);
            $first_name = ucwords($first_name, '-');

            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
            if (empty($last_name)) {
                throw new \Exception('Le nom est obligatoire');
            }
            $last_name = strtoupper($last_name);

            $email = isset($_POST['email']) ? trim($_POST['email']) : null;
            if (empty($email)) {
                throw new \Exception('L\'adresse mail est obligatoire');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('L\'adresse e-mail n\'est pas valide');
            }

            $userModel = new UserModel();
            if ($userModel->findByEmail($email)) {
                throw new \Exception('Cette adresse email est déjà utilisée');
            }

            $password = isset($_POST['password']) ? trim($_POST['password']) : null;
            if (empty($password)) {
                throw new \Exception('Le mot de passe est obligatoire');
            }
            if (strlen($password) < 12 || 
            !preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/\d/', $password) || 
            !preg_match('/[\W_]/', $password)) {
                throw new \Exception('Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $id_role = isset($_POST['id_role']) ? (int) trim($_POST['id_role']) : null;
                    if (empty($id_role)) {
                throw new \Exception('L\'attribution d\'un rôle est obligatoire');
            }

            $data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $hash,
                'active' => 1,
                'id_role' => $id_role,
                'last_password_update' => null,
            ];
            
            if ($userModel->insert($data)) {
                $_SESSION['success'] = 'Nouvel utilisateur créé avec succès';
                $this->redirectToRoute('user-list', ['status' => 'active']);
            } else {
                throw new \Exception('Erreur lors de la création de l’utilisateur');
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            $this->redirectToRoute('user-create');
        }
    }


}