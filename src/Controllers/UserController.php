<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\LogModel;
use App\Core\Access;

/**
 * Controller responsible for user-related actions in the back office.
 *
 * Handles listing, creation, editing, deletion, and role-based logic.
 */
class UserController extends AbstractController 
{

    /**
     * Create a new user account from the back office.
     *
     * Accessible only to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Validates and sanitizes form data, hashes the password,
     * and inserts the user into the database.
     * On success or failure, redirects with appropriate feedback.
     *
     * @return void
     */
    public function userCreate(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Refus d’accès à userCreate() : rôle super_admin requis");
        }

        $this->requireCsrfToken();

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
                $newUserId = $userModel->getLastInsertId();

                if ($newUserId === null) {
                    throw new \Exception("Utilisateur créé mais ID introuvable pour le log");
                }

                $logData = $data;
                unset($logData['password']);

                $logModel = new LogModel();
                $logModel->logModification(
                    'user',
                    $newUserId,
                    'create',
                    $_SESSION['user']['id'],
                    $_SESSION['user']['first_name'],
                    $_SESSION['user']['last_name'] ?? null,
                    [],
                    $logData
                );

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
     * Accessible only to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Accepts GET parameters to filter users by name, email, and role.
     * Displays active or inactive users based on status.
     *
     * @return void
     */
    public function userSearch(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Refus d’accès à userSearch() : rôle super_admin requis");
        }
        
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
     * Toggle the active/inactive status of a user.
     *
     * Accessible only to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Accepts a user ID via POST and toggles their `active` field.
     * Redirects back to the user list with a success or error message.
     *
     * @return void
     */
    public function toggleStatus(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Refus d’accès à toggleStatus() : rôle super_admin requis");
        }

        $this->requireCsrfToken();
        
        $id = $_POST['id'] ?? null;
        $status = $_GET['status'] ?? 'active';
        $route = 'user-list';

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = "ID utilisateur invalide";
            $this->redirectToRoute($route, ['status' => $status]);
            exit;
        }

        $userModel = new UserModel();
        $logModel = new LogModel();

        $beforeUser = $userModel->find((int)$id);

        if (!$beforeUser) {
            $_SESSION['error'] = "Utilisateur introuvable";
            $this->redirectToRoute($route, ['status' => $status]);
            exit;
        }

        $success = $userModel->toggleActiveStatus((int)$id);

        if ($success) {
            $afterUser = $userModel->find((int)$id);

            $logModel->logModification(
                'user',
                (int) $id,
                'update',
                $_SESSION['user']['id'],
                $_SESSION['user']['first_name'],
                $_SESSION['user']['last_name'] ?? null,
                ['active' => $beforeUser['active']],
                ['active' => $afterUser['active']]
            );

            $_SESSION['success'] = "Statut utilisateur modifié avec succès";
        } else {
            $_SESSION['error'] = "Impossible de changer le statut de l’utilisateur";
        }

        $this->redirectToRoute($route, ['status' => $status]);
    }

    /**
     * Update an existing user's information.
     *
     * Accessible only to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Validates submitted data and optionally updates the password.
     * Redirects with success or error feedback.
     *
     * @return void
     */
    public function userUpdate(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Refus d’accès à userUpdate() : rôle super_admin requis");
        }

        $this->requireCsrfToken();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Méthode non autorisée';
            $this->redirectToRoute('user-list');
            exit;
        }

        $id = $_POST['id'] ?? null;
        $status = $_GET['status'] ?? ($_POST['active'] === '0' ? 'inactive' : 'active');

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
                $_SESSION['error'] = 'Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial';
                $_SESSION['form_data_user_update'] = $_POST;
                $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
                exit;
            }
        }

        $userModel = new UserModel();
        $beforeUser = $userModel->find((int)$id);
        $success = $userModel->updateUserInformations($data);

        if ($success) {
            $afterUser = $userModel->find((int)$id);

            $logModel = new LogModel();
            $logModel->logModification(
                'user',
                (int) $id,
                'update',
                $_SESSION['user']['id'],
                $_SESSION['user']['first_name'],
                $_SESSION['user']['last_name'] ?? null,
                [
                    'first_name' => $beforeUser['first_name'],
                    'last_name'  => $beforeUser['last_name'],
                    'email'      => $beforeUser['email'],
                    'id_role'    => $beforeUser['id_role'],
                    'active'     => $beforeUser['active'],
                ],
                [
                    'first_name' => $afterUser['first_name'],
                    'last_name'  => $afterUser['last_name'],
                    'email'      => $afterUser['email'],
                    'id_role'    => $afterUser['id_role'],
                    'active'     => $afterUser['active'],
                ]
            );
            $_SESSION['success'] = 'Utilisateur mis à jour avec succès';
            $this->redirectToRoute('user-list', ['status' => $status]);
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour';
            $_SESSION['form_data_user_update'] = $_POST;
            $this->redirectToRoute('user-edit', ['id' => $id, 'status' => $status]);
        }
    }

    /**
     * Delete a user and their associated permissions.
     *
     * Accessible only to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Accepts a user ID via GET.
     * Performs deletion and redirects with feedback.
     *
     * @return void
     */
    public function userDelete(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Refus d’accès à userDelete() : rôle super_admin requis");
        }

        $this->requireCsrfToken();
        
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'active';

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = "ID utilisateur invalide";
            $this->redirectToRoute('user-list', ['status' => $status]);
            exit;
        }

        $userModel = new UserModel();
        $beforeUser = $userModel->find((int)$id);

        if (!$beforeUser) {
            $_SESSION['error'] = "Utilisateur introuvable";
            $this->redirectToRoute('user-list', ['status' => $status]);
            exit;
        }
        
        if ($userModel->deleteUserWithPermissions((int)$id)) {
            $logModel = new LogModel();
            $logModel->logModification(
                'user',
                (int) $id,
                'delete',
                $_SESSION['user']['id'],
                $_SESSION['user']['first_name'],
                $_SESSION['user']['last_name'] ?? null,
                [
                    'first_name' => $beforeUser['first_name'],
                    'last_name'  => $beforeUser['last_name'],
                    'email'      => $beforeUser['email'],
                    'id_role'    => $beforeUser['id_role'],
                    'active'     => $beforeUser['active'],
                ],
                []
            );

            $_SESSION['success'] = "Utilisateur supprimé avec succès";
        } else {
            $_SESSION['error'] = "Suppression impossible : cet utilisateur ne peut pas être supprimé, ou une erreur est survenue";
        }

        $this->redirectToRoute('user-list', ['status' => $status]);
    }


    /**
     * Handle the password reset form submission.
     *
     * This method verifies the current password, validates the new password
     * according to security policy, checks the CSRF token, and updates the password
     * in the database if all checks pass. On success, the user session is destroyed
     * and a new one is started to display a success message on login.
     *
     * On failure, the user is redirected back to the reset form with an error message.
     *
     * @return void
     */
    public function passwordReset(): void
    {
        if (Access::hasRole('guest')) {
            $this->denyAccess("Refus d’accès à passwordReset() : rôle guest interdit");
        }
                
        $this->requireCsrfToken();

        if (!isset($_SESSION['user'])) {
            $this->redirectToRoute('login');
            return;
        }

        try {
            $userModel = new UserModel();
            $user = $userModel->find((int)$_SESSION['user']['id']);

            if (!$user) {
                throw new \Exception('Utilisateur introuvable');
            }

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new \Exception('Tous les champs sont obligatoires');
            }

            if (!password_verify($currentPassword, $user['password'])) {
                throw new \Exception('Mot de passe actuel incorrect');
            }

            if (password_verify($newPassword, $user['password'])) {
                throw new \Exception('Le nouveau mot de passe ne peut pas être identique à l\'ancien');
            }

            if ($newPassword !== $confirmPassword) {
                throw new \Exception('Les mots de passe ne correspondent pas');
            }

            if (
                strlen($newPassword) < 12 ||
                !preg_match('/[A-Z]/', $newPassword) ||
                !preg_match('/[a-z]/', $newPassword) ||
                !preg_match('/\d/', $newPassword) ||
                !preg_match('/[\W_]/', $newPassword)
            ) {
                throw new \Exception("Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial");
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            if (!$hash) {
                throw new \Exception('Échec lors du hashage du mot de passe');
            }

            $success = $userModel->updatePasswordWithLoginDate($user['id'], $hash);

            if (!$success) {
                throw new \Exception('Erreur transactionnelle : le mot de passe n\'a pas été mis à jour');
            }

            $logModel = new LogModel();
            $logModel->logModification(
                'user',
                (int) $user['id'],
                'update',
                $_SESSION['user']['id'],
                $_SESSION['user']['first_name'],
                $_SESSION['user']['last_name'] ?? null,
                ['password' => 'changed'],
                ['password' => 'changed']
            );

            unset($_SESSION['csrf_token']);
            session_destroy();
            session_start();
            $_SESSION['success'] = 'Mot de passe modifié avec succès. Veuillez vous reconnecter';
            $this->redirectToRoute('login');

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToRoute('password-reset');
        }
    }

}