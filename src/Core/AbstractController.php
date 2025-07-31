<?php

namespace App\Core;

use App\Models\LogModel;

/**
 * AbstractController
 *
 * Base controller providing core helpers for all concrete controllers.
 */
abstract class AbstractController
{
    /** @var string|null The view template to render */
    protected ?string $template = '';

    /** @var array The breadcrumb trail for the current page */
    protected array $breadcrumb = [];

    /**
     * Display a view with the given data.
     * Escapes all string values for security.
     *
     * @param string $view The view filename.
     * @param array $data Key-value pairs passed to the view.
     * @return void
     */
    protected function display(string $view, array $data): void
    {
        foreach ($data as $key => $value) {
            if (gettype($value) === 'string') {
                $data[$key] = htmlspecialchars($value);
            }
        }

        $data['breadcrumb'] = $this->breadcrumb;
        extract($data);

        if (!empty($view)) {
            $this->template = __DIR__ . '/../Views/' . $view;
        }

        require __DIR__ . '/../Views/layout.phtml';
    }

    /**
     * Redirect the user to a given route with optional query parameters.
     * If a 'success' or 'error' key is present, it is stored in the session.
     *
     * @param string $routeName The route name.
     * @param array $params Optional query parameters (e.g., status filters).
     *                      'success' or 'error' will be moved to $_SESSION.
     * @return void
     */
    protected function redirectToRoute(string $routeName, array $params = []): void
    {
        $query = ['route' => $routeName];

        if (isset($params['success'])) {
            $_SESSION['success'] = $params['success'];
            unset($params['success']);
        }

        if (isset($params['error'])) {
            $_SESSION['error'] = $params['error'];
            unset($params['error']);
        }

        if (!empty($params)) {
            $query = array_merge($query, $params);
        }

        $url = 'index.php?' . http_build_query($query);

        header('Location: ' . $url);
        exit();
    }

    /**
     * Set the breadcrumb trail for the current view.
     *
     * @param array $items An array of breadcrumb items.
     * @return void
     */
    protected function setBreadcrumb(array $items): void
    {
        $this->breadcrumb = $items;
    }

    /**
     * Redirect to the 403 Forbidden error page.
     *
     * This method sends a 403 HTTP status code and displays the custom
     * error403.phtml view, just like error404 does for 404 errors.
     * Use this to deny access when the user does not have sufficient permissions.
     *
     * @return void
     */
    protected function redirectToError403(): void
    {
        http_response_code(403);

        $this->display('partials/error403.phtml', [
            'title' => 'Accès refusé',
        ]);

        exit();
    }

    /**
     * Log an access denial and redirect to the 403 Forbidden page.
     *
     * @param string $reason Contextual explanation of the access denial.
     * @return void
     */
    protected function denyAccess(string $reason): void
    {
        $logModel = new LogModel();

        $logModel->logSystem(
            '403',
            $reason,
            $_SESSION['user']['id'] ?? null,
            $_SESSION['user']['first_name'] ?? null,
            $_SESSION['user']['last_name'] ?? null
        );

        $this->redirectToError403();
    }

}