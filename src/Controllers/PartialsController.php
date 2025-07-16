<?php
    namespace App\Controllers;

    use App\Core\AbstractController;
    
    class PartialsController extends AbstractController
    {
        public ?string $template = "error404.phtml";

         /**
         * Display the "error404" page.
         *
         * @return void
         */
        public function notFound(): void
        {
            include __DIR__ . '/../Views/partials/error404.phtml';
        }

        /**
         * Display the "under contruction" page.
         *
         * @return void
         */
        public function underConstruction(): void
        {
            $this->template = __DIR__ . '/../Views/partials/construction.phtml';

            $this->setBreadcrumb([
                ['label' => 'Accueil', 'url' => '?route=home'],
                ['label' => 'Page en construction', 'url' => null]
            ]);

            $this->display('construction.phtml', [
                'title' => 'Page en construction'
            ]);
        }

    }
