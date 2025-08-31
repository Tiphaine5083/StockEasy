<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\StockModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\LogModel;
use App\Core\Access;

/**
 * PublicController
 *
 * Handles public-facing pages related to stock management and home views.
 */
class PublicController extends AbstractController
{
    /**
     * Display the main home page.
     *
     * @return void
     */
    public function showHome(): void
    {
        if (!Access::isLoggedIn() || Access::hasRole('guest')) {
            $this->redirectToRoute('login');
            return;
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => null]
        ]);

        $this->display('home.phtml', [
            'title' => 'Accueil'
        ]);
    }

    /**
     * Display the main log management page.
     *
     * Only accessible to users with the 'super-admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showLogHome(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès à la gestion des logs sans permission");
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => null],
        ]);

        $this->display('log/log-home.phtml', [
            'title' => 'Gestion des logs'
        ]);
    }

    /**
     * Display all system logs with optional filters.
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showSystemLogs(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la consultation des logs système");
        }

        $filters = [
            'context'    => $_GET['type'] ?? '',
            'user'       => $_GET['user'] ?? '',
            'date_start' => $_GET['date_start'] ?? '',
            'date_end'   => $_GET['date_end'] ?? '',
        ];

        $logModel = new LogModel();
        $usersWithLogs = $logModel->getUsersByLogType('system');
        $contexts = $logModel->getContextsByType('system');

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'System Logs', 'url' => null]
        ]);

        $this->display('log/log-system.phtml', [
            'title'         => 'System Logs',
            'logs'          => [],
            'usersWithLogs' => $usersWithLogs,
            'contexts'      => $contexts,
        ]);
    }

    /**
     * Display all modification logs.
    * Only accessible to users with the 'super_admin' role.
    * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showModificationLogs(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la consultation des logs de modification");
        }        

        $logModel = new LogModel();
        $usersWithLogs = $logModel->getUsersByLogType('modification');
        $contexts = $logModel->getContextsByType('modification');

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'Modification Logs', 'url' => null]
        ]);

        $this->display('log/log-modification.phtml', [
            'title'         => 'Modification Logs',
            'logs'          => [],
            'usersWithLogs' => $usersWithLogs,
            'contexts'      =>$contexts,
        ]);
    }

    /**
     * Displays the main user management dashboard.
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showUserHome(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la gestion des utilisateurs");
        }        
        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => null]
        ]);

        $this->display('user/user-home.phtml', [
            'title' => 'Gestion des utilisateurs'
        ]);
    }

    /**
     * Display the user creation form.
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Retrieves available roles for the select input and any
     * previously submitted form data stored in session.
     *
     * @return void
     */
    public function showUserCreate(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée au formulaire de création d'utilisateur");
        }
        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Création d\'un nouvel utilisateur', 'url' => null]
        ]);

        $formData = $_SESSION['form_data_user_create'] ?? [];
        unset($_SESSION['form_data_user_create']);

        $this->display('user/user-create.phtml', [
            'title' => 'Création nouvel utilisateur',
            'roles' => $roles,
            'formData' => $formData,
        ]);
    }

    /**
     * Display the filtered user list based on status (active/inactive).
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Retrieves users by status and passes filters and roles to the view.
     *
     * @return void
     */
    public function showUserListByFilter(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la liste des utilisateurs filtrée par statut");
        }

        $status = strtolower($_GET['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }

        $active = ($status === 'active') ? 1 : 0;
        $label = $status === 'active' ? 'Utilisateurs actifs' : 'Utilisateurs désactivés';

        $userModel = new UserModel();
        $roleModel = new RoleModel();

        $users = $userModel->findByStatusWithFilters($active, []);
        $roles = $roleModel->findAll();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => $label, 'url' => null]
        ]);

        $this->display('user/user-list.phtml', [
            'title' => $label,
            'status' => $status,
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'name' => '',
                'email' => '',
                'role' => ''
            ]
        ]);
    }

    /**
     * Display the user edit form with current data or session fallback.
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * Retrieves the user by ID and populates the form with either session data
     * (in case of previous error) or current user data from database.
     *
     * @return void
     */
    public function showUserEdit(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée au formulaire de modification utilisateur");
        }

        $id = $_GET['id'] ?? null;

        if (!$id || !ctype_digit($id)) {
            $_SESSION['error'] = "ID utilisateur invalide.";
            $this->redirectToRoute('user-list');
            exit;
        }

        $userModel = new UserModel();
        $user = $userModel->find((int)$id);

        if (!$user) {
            $_SESSION['error'] = "Utilisateur introuvable.";
            $this->redirectToRoute('user-list');
            exit;
        }

        $formData = $_SESSION['form_data_user_update'] ?? [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'id_role' => $user['id_role'],
            'active' => $user['active'],
        ];
        unset($_SESSION['form_data_user_update']);

        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Modification utilisateur', 'url' => null]
        ]);

        $this->display('user/user-edit.phtml', [
            'title' => 'Modification utilisateur',
            'formData' => $formData,
            'roles' => $roles,
        ]);
    }

    /**
     * Display the user roles management page.
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * This page is intended for managing roles and permissions (V2 feature).
     *
     * @return void
     */
    public function showUserRole(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la gestion des rôles");
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Rôles', 'url' => null]
        ]);

        $this->display('user/user-role.phtml', [
            'title' => 'Rôles'
        ]);
    }

    /**
     * Display the user permissions management page.
     *
     * Only accessible to users with the 'super_admin' role.
     * Redirects to a 403 error page if access is denied.
     *
     * This page is intended for detailed permission management (planned for V2).
     *
     * @return void
     */
    public function showUserPermission(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à la gestion des permissions par utilisateur");
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Permissions par utilisateur', 'url' => null]
        ]);

        $this->display('user/user-permission.phtml', [
            'title' => 'Permissions par utilisateur'
        ]);
    }

    /**
     * Display the stock management home page.
     *
     * Accessible to all users except those with the 'guest' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showStockHome(): void
    {
        if (Access::hasRole('guest')) {
            $this->denyAccess("Un invité a tenté d'accéder à la page d'accueil de gestion du stock");
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion du stock', 'url' => null]
        ]);

        $this->display('stock/stock-home.phtml', [
            'title' => 'Gestion du stock'
        ]);
    }

    /**
     * Display the stock inventory list with optional filter.
     *
     * Accessible to all users except those with the 'guest' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showStockList(): void
    {
        if (Access::hasRole('guest')) {
            $this->denyAccess("Un invité a tenté d'accéder à la liste d'inventaire du stock");
        }

        $stockModel = new StockModel();

        $filter = $_GET['stockFilter'] ?? 'all';
        $page = 1;
        $offset = 0;
        $limit = 25;

        $data = $stockModel->findByStockFilter($filter, $limit, $offset);
        $nbTotal = $stockModel->countByStockFilter($filter);
        $nbDisplayed = count($data);

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion du stock', 'url' => '?route=stock-home'],
            ['label' => 'Inventaire', 'url' => null]
        ]);

        $this->display('stock/stock-list.phtml', [
            'title' => 'Inventaire',
            'data' => $data,
            'nbDisplayed' => $nbDisplayed,
            'nbTotal' => $nbTotal,
            'filter' => $filter,
            'nextPage' => $page + 1,
        ]);
    }

    /**
     * Display the stock creation form and today's registered tires.
     *
     * Accessible to all users except those with 'guest' or 'intern' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showStockCreate(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Accès refusé à la création de stock pour un rôle non autorisé");
        }

        $stockModel = new StockModel();
        $todayTires = $stockModel->findTodayRegistered();
        $brands = $stockModel->getBrands();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion du stock', 'url' => '?route=stock-home'],
            ['label' => 'Création de stock', 'url' => null]
        ]);

        $this->display('stock/stock-create.phtml', [
            'title' => 'Création de stock',
            'brands' => $brands,
            'todayTires' => $todayTires,
        ]);
    }

    /**
     * Display the stock search page with filters and results.
     *
     * Accessible to all users except those with the 'guest' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showStockSearch(): void
    {
        if (Access::hasRole('guest')) {
            $this->denyAccess("Accès refusé à la recherche de stock pour le rôle guest");
        }

        try {
            $stockModel = new StockModel();

            $brand = trim($_GET['brand'] ?? '');
            $width = trim($_GET['width'] ?? '');
            $height = trim($_GET['height'] ?? '');
            $diameter = trim($_GET['diameter'] ?? '');
            $load_index = trim($_GET['load_index'] ?? '');
            $speed_index = trim($_GET['speed_index'] ?? '');
            $season = $_GET['season'] ?? null;
            $quality = $_GET['quality'] ?? null;
            $dot = $_GET['DOT'] ?? null;

            $brand = ($brand === '') ? null : $brand;
            $width = ($width === '') ? null : $width;
            $height = ($height === '') ? null : $height;
            $diameter = ($diameter === '') ? null : $diameter;
            $load_index = ($load_index === '') ? null : $load_index;
            $speed_index = ($speed_index === '') ? null : $speed_index;
            $dot = ($dot === '') ? null : $dot;

            $criteria = [
                'brand' => $brand,
                'width' => $width,
                'height' => $height,
                'diameter' => $diameter,
                'load_index' => $load_index,
                'speed_index' => $speed_index,
                'season' => $season,
                'quality' => $quality,
                'dot' => $dot,
            ];

            $brands = $stockModel->getBrands();
            $reasons = $stockModel->getMovementReasons();

            if (array_filter($criteria)) {
                $criterias = $stockModel->searchStock($criteria);
            } else {
                $criterias = [];
            }
            
            $this->setBreadcrumb([
                ['label' => 'Accueil', 'url' => '?route=home'],
                ['label' => 'Gestion du stock', 'url' => '?route=stock-home'],
                ['label' => 'Recherche et Modification', 'url' => null]
            ]);

            $this->display('stock/stock-search.phtml', [
                'title' => 'Recherche de stock',
                'brands' => $brands,
                'criterias' => $criterias,
                'reasons' => $reasons,
            ]);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToRoute('stock-search');
        }
    }

    /**
     * Display the stock edit form for an existing tire.
     *
     * Accessible to all users except those with 'guest' or 'intern' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showStockEdit(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Accès interdit à la modification de stock pour les rôles guest ou stagiaire");
        }

        try {
            $tireId = $_GET['id'] ?? null;

            if ($tireId === null || !ctype_digit($tireId) || $tireId <= 0) {
                throw new \Exception('ID invalide pour la modification.');
            }

            $stockModel = new StockModel();
            $tire = $stockModel->find((int)$tireId);

            if (!$tire) {
                throw new \Exception('Pneu introuvable.');
            }

            $brands = $stockModel->getBrands();
            $reasons = $stockModel->getMovementReasons();
            $isInvoiced = $stockModel->hasInvoiceLine((int)$tireId);

            $this->setBreadcrumb([
                ['label' => 'Accueil', 'url' => '?route=home'],
                ['label' => 'Gestion du stock', 'url' => '?route=stock-home'],
                ['label' => 'Recherche de stock', 'url' => '?route=stock-search'],
                ['label' => 'Modifier un pneu', 'url' => null]
            ]);

            $this->display('stock/stock-edit.phtml', [
                'title' => 'Modifier un pneu',
                'tire' => $tire,
                'brands' => $brands,
                'isInvoiced' => $isInvoiced,
                'reasons' => $reasons,
            ]);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToRoute('stock-search');
        }
    }

        /**
     * Display the stock management home page.
     *
     * Accessible to all users except those with the 'guest' role.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function showCustomerHome(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Accès à la gestion des clients interdite pour votre rôle");
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des clients', 'url' => null]
        ]);

        $this->display('customer/customer-home.phtml', [
            'title' => 'Gestion des clients'
        ]);
    }
}