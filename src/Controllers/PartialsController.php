<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Access;

class PartialsController extends AbstractController
{
    /**
     * Display the "error404" page.
     *
     * @return void
     */
    public function notFound(): void
    {
        if (!Access::isLoggedIn() || Access::hasRole('guest')) {
            $this->redirectToRoute('login');
            return;
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],            
            ['label' => 'Page non trouvée', 'url' => null]
        ]);

        $this->display('partials/error404.phtml', [
            'title' => 'Page non trouvée'
        ]);
    }

    /**
     * Display the "under contruction" page.
     *
     * @return void
     */
    public function underConstruction(): void
    {
        if (!Access::isLoggedIn() || Access::hasRole('guest')) {
            $this->redirectToRoute('login');
            return;
        }

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Page en construction', 'url' => null]
        ]);

        $this->display('partials/construction.phtml', [
            'title' => 'Page en construction'
        ]);
    }
}
