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
        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => null]
        ]);

        $this->display('home.phtml', [
            'title' => 'Accueil'
        ]);
    }

    public function showLogHome(): void
    {
        // if (
        //     empty($_SESSION['user']) ||
        //     ($_SESSION['user']['role'] ?? '') !== 'super-admin'
        // ) {
        //     $this->redirectToRoute('error/404');
        // }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => null],
        ]);

        $this->display('log/log-home.phtml', [
            'title' => 'Gestion des logs'
        ]);
    }

    /**
     * Display all system logs.
     * Access restricted to users with role 'super-admin'.
     *
     * @return void
     */
    public function showSystemLogs(): void
    {
        // if (
        //     empty($_SESSION['user']) ||
        //     ($_SESSION['user']['role'] ?? '') !== 'super-admin'
        // ) {
        //     $this->redirectToRoute('error/404');
        // }

        $logModel = new LogModel();
        $logs = $logModel->getAllSystemLogs();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],            
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'System Logs', 'url' => null]
        ]);

        $this->display('log/system.phtml', [
            'title' => 'System Logs',
            'logs'  => $logs
        ]);
    }

    /**
     * Display all modification logs.
     * Access restricted to users with role 'super-admin'.
     *
     * @return void
     */
    public function showModificationLogs(): void
    {
        // if (
        //     empty($_SESSION['user']) ||
        //     ($_SESSION['user']['role'] ?? '') !== 'super-admin'
        // ) {
        //     $this->redirectToRoute('error/404');
        // }

        $logModel = new LogModel();
        $logs = $logModel->getAllModificationLogs();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'Modification Logs', 'url' => null]
        ]);

        $this->display('log/modification.phtml', [
            'title' => 'Modification Logs',
            'logs'  => $logs
        ]);
    }

    /**
     * Displays the main user management dashboard.
     *
     * Sets the breadcrumb navigation and passes a title to the view.
     *
     * @return void
     */
    public function showUserHome(): void
    {
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
     * @return void
     */
    public function showUserCreate(): void
    {
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
     * @return void
     */    
    public function showUserListByFilter(): void
    {
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
     * @return void
     */ 
    public function showUserEdit(): void
    {
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

    // TODO: Implement detailed roles management
    /**
     * Display the user roles management page.
     *
     * @return void
     */
    public function showUserRole(): void
    {
        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Rôles', 'url' => null]
        ]);

        $this->display('user/user-role.phtml', [
            'title' => 'Rôles'
        ]);
    }

    // TODO: Implement detailed permission management
    /**
     * Display the user permissions management page.
     *
     * @return void
     */    
    public function showUserPermission(): void
    {
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
     * @return void
     */
    public function showStockHome(): void
    {
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
     * @return void
     */
    public function showStockList(): void
    {
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
     * @return void
     */
    public function showStockCreate(): void
    {
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
     * @return void
     */
    public function showStockSearch(): void
    {
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
     * @return void
     */
    public function showStockEdit(): void
    {
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
}