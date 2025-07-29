<?php

namespace App\Controllers;

use App\Core\AbstractController;

class PartialsController extends AbstractController
{
    /**
     * Display the "error404" page.
     *
     * @return void
     */
    public function notFound(): void
    {
        $this->display('partials/error404.phtml', [
            'title' => 'Page non trouvée'
        ]);
    }

    /**
     * Display the "error403" page.
     *
     * @return void
     */
    public function forbidden(): void
    {
        $title = 'Erreur 403 - Accès interdit';
        require_once __DIR__ . '/../Views/partials/error403.phtml';
    }


    /**
     * Display the "under contruction" page.
     *
     * @return void
     */
    public function underConstruction(): void
    {
        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Page en construction', 'url' => null]
        ]);

        $this->display('partials/construction.phtml', [
            'title' => 'Page en construction'
        ]);
    }
}
