<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\StockModel;
use App\Models\RoleModel;

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
        $this->template = __DIR__ . '/../Views/home.phtml';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => null]
        ]);

        $this->display('home.phtml', [
            'title' => 'Accueil'
        ]);
    }

    /**
     * Display the stock management home page.
     *
     * @return void
     */
    public function showUserHome(): void
    {
        $this->template = __DIR__ . '/../Views/user/user-home.phtml';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => null]
        ]);

        $this->display('user-home.phtml', [
            'title' => 'Gestion des utilisateurs'
        ]);
    }

    public function showUserCreate(): void
    {
        $this->template = __DIR__ . '/../Views/user/user-create.phtml';

        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Création d\'un nouvel utilisateur', 'url' => null]
        ]);

        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);

        $this->display('user-create.phtml', [
            'title' => 'Création nouvel utilisateur',
            'roles' => $roles,
            'formData' => $formData,
        ]);
    }

    public function showUserListByFilter(): void
    {
        $status = strtolower($_GET['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }
        $this->template = __DIR__ . '/../Views/user/user-list.phtml';
        $label = $status === 'active' ? 'Utilisateurs actifs' : 'Utilisateurs désactivés';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => $label, 'url' => null]
        ]);

        $this->display('user-list.phtml', [
            'title' => 'Utilisateurs ' . $status
        ]);
    }

    public function showUserRole(): void
    {
        $this->template = __DIR__ . '/../Views/user/user-role.phtml';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Rôles', 'url' => null]
        ]);

        $this->display('user-role.phtml', [
            'title' => 'Rôles'
        ]);
    }

    public function showUserPermission(): void
    {
        $this->template = __DIR__ . '/../Views/user/user-permission.phtml';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des utilisateurs', 'url' => '?route=user-home'],
            ['label' => 'Permissions par utilisateur', 'url' => null]
        ]);

        $this->display('user-permission.phtml', [
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
        $this->template = __DIR__ . '/../Views/stock/stock-home.phtml';

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion du stock', 'url' => null]
        ]);

        $this->display('stock-home.phtml', [
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
        $this->template = __DIR__ . '/../Views/stock/stock-list.phtml';

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

        $this->display('stock-list.phtml', [
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
        $this->template = __DIR__ . '/../Views/stock/stock-create.phtml';
        $stockModel = new StockModel();
        $todayTires = $stockModel->findTodayRegistered();
        $brands = $stockModel->getBrands();

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion du stock', 'url' => '?route=stock-home'],
            ['label' => 'Création de stock', 'url' => null]
        ]);

        $this->display('stock-create.phtml', [
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
            $this->template = __DIR__ . '/../Views/stock/stock-search.phtml';
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

            $this->display('stock-search.phtml', [
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
            $this->template = __DIR__ . '/../Views/stock/stock-edit.phtml';
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

            $this->display('stock-edit.phtml', [
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