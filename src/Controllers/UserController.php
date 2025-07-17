<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\UserModel;
use App\Models\RoleModel;

/**
 * Controller responsible for user-related actions in the back office.
 *
 * Handles listing, creation, editing, deletion, and role-based logic.
 */
class UserController extends AbstractController 
{

    /**
     * Handle the creation of a new user from the back office.
     *
     * @return void
     */
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
            $_SESSION['form_data_user_create'] = $_POST;
            $this->redirectToRoute('user-create');
        }
    }

    /**
     * Search and display users with optional filters and status.
     *
     * @return void
     */
    public function userSearch(): void
    {
        $status = $_GET['status'] ?? 'active'; // brut, pour redirection
        try {
            $validatedStatus = strtolower($status);
            if (!in_array($validatedStatus, ['active', 'inactive'])) {
                $validatedStatus = 'active';
            }

            $active = ($validatedStatus === 'active') ? 1 : 0;

            $filters = [
                'name' => $_GET['name'] ?? '',
                'email' => $_GET['email'] ?? '',
                'role' => $_GET['role'] ?? '',
            ];

            $userModel = new UserModel();
            $roleModel = new RoleModel();

            $users = $userModel->findByStatusWithFilters($active, $filters);
            $roles = $roleModel->findAll();

            $this->display('user/user-list.phtml', [
                'users' => $users,
                'roles' => $roles,
                'status' => $validatedStatus,
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            $_SESSION['error'] = "Impossible d'afficher la liste des utilisateurs.";
            $this->redirectToRoute('user-list', ['status' => $status]);
        }
    }

    /**
     * Toggle the active status of a user (via POST).
     *
     * @return void
     */
    public function toggleStatus(): void
    {
        $id = $_POST['id'] ?? null;
        $status = $_GET['status'] ?? 'active';
        $route = 'user-list';

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = "ID utilisateur invalide";
            $this->redirectToRoute($route, ['status' => $status]);
            exit;
        }

        $userModel = new UserModel();
        $success = $userModel->toggleActiveStatus((int)$id);

        if ($success) {
            $_SESSION['success'] = "Statut utilisateur modifié avec succès";
        } else {
            $_SESSION['error'] = "Impossible de changer le statut de l’utilisateur";
        }

        $this->redirectToRoute($route, ['status' => $status]);
    }

    /**
     * Delete a user and their permissions if allowed.
     *
     * @return void
     */
    public function userDelete(): void
    {
        $id = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? 'active';

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = "ID utilisateur invalide";
            $this->redirectToRoute('user-list', ['status' => $status]);
            exit;
        }

        $userModel = new UserModel();
        
        if ($userModel->deleteUserWithPermissions((int)$id)) {
            $_SESSION['success'] = "Utilisateur supprimé avec succès";
        } else {
            $_SESSION['error'] = "Suppression impossible : cet utilisateur ne peut pas être supprimé, ou une erreur est survenue";
        }

        $this->redirectToRoute('user-list', ['status' => $status]);
    }

    /**
     * Update an existing user with validated form data.
     *
     * @return void
     */
    public function userUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Méthode non autorisée';
            $this->redirectToRoute('user-list');
            exit;
        }

        $id = $_POST['id'] ?? null;
        $status = $_GET['status'] ?? 'active';

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = 'ID utilisateur invalide';
            $this->redirectToRoute('user-list');
            exit;
        }

        $data = [
            'id' => (int)$id,
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'id_role' => (int)($_POST['id_role'] ?? 0),
            'password' => $_POST['password'] ?? '',
        ];

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || $data['id_role'] === 0) {
            $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
            $_SESSION['form_data_user_update'] = $_POST;
            $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
            exit;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Adresse email invalide';
            $_SESSION['form_data_user_update'] = $_POST;
            $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
            exit;
        }
        if (!empty($data['password'])) {
            if (
                strlen($data['password']) < 12
                || !preg_match('/[A-Z]/', $data['password'])
                || !preg_match('/[a-z]/', $data['password'])
                || !preg_match('/\d/', $data['password'])
                || !preg_match('/[\W_]/', $data['password'])
            ) {
                $_SESSION['error'] = 'Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
                $_SESSION['form_data_user_update'] = $_POST;
                $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
                exit;
            }
        }

        $userModel = new UserModel();
        $success = $userModel->updateUserInformations($data);

        if ($success) {
            $_SESSION['success'] = 'Utilisateur mis à jour avec succès.';
            $this->redirectToRoute('user-list', ['status' => $status]);
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            $_SESSION['form_data_user_update'] = $_POST;
            $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
        }
    }

}