<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\LogModel;
use App\Core\Access;

/**
 * LogController
 *
 */
class LogController extends AbstractController {

    /**
     * Filter and display system logs via POST request.
     * Only accessible to super_admin.
     *
     * @return void
     */
    public function filterSystemLogs(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à l'utilisation du filtre des logs système");
        }

        $filters = [
            'context'    => $_POST['type'] ?? '',
            'user'       => $_POST['user'] ?? '',
            'date_start' => $_POST['date_start'] ?? '',
            'date_end'   => $_POST['date_end'] ?? '',
        ];

        $logModel = new LogModel();
        $logs = $logModel->getAllSystemLogs($filters);
        $usersWithLogs = $logModel->getUsersByLogType('system');
        $contexts = $logModel->getContextsByType('system');

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'System Logs', 'url' => null]
        ]);

        $this->display('log/log-system.phtml', [
            'title'          => 'System Logs (Filtrés)',
            'logs'           => $logs,
            'usersWithLogs'  => $usersWithLogs,
            'contexts' => $contexts,
        ]);
    }

    /**
     * Filter and display modification logs via POST request.
     * Only accessible to super_admin.
     *
     * @return void
     */
    public function filterModificationLogs(): void
    {
        if (!Access::hasRole('super_admin')) {
            $this->denyAccess("Tentative d'accès non autorisée à l'utilisation du filtre des logs de modification");
        }

        $filters = [
            'context'    => $_POST['type'] ?? '',
            'user'       => $_POST['user'] ?? '',
            'date_start' => $_POST['date_start'] ?? '',
            'date_end'   => $_POST['date_end'] ?? '',
        ];

        $logModel = new LogModel();
        $logs = $logModel->getAllModificationLogs($filters);
        $usersWithLogs = $logModel->getUsersByLogType('modification');
        $contexts = $logModel->getContextsByType('modification');

        $this->setBreadcrumb([
            ['label' => 'Accueil', 'url' => '?route=home'],
            ['label' => 'Gestion des logs', 'url' => '?route=log-home'],
            ['label' => 'System Logs', 'url' => null]
        ]);

        $this->display('log/log-modification.phtml', [
            'title'          => 'Modification Logs (Filtré)',
            'logs'           => $logs,
            'usersWithLogs'  => $usersWithLogs,
            'contexts' => $contexts,
        ]);
    }

}