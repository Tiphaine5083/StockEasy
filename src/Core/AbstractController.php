<?php

namespace App\Core;

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
     * Check if a user is authenticated.
     *
     * @return bool True if the user is authenticated.
     */
    protected function isAuthenticated(): bool
    {
        // TODO: Implement real authentication logic.
        return false;
    }

    /**
     * Check if the current user is an admin.
     *
     * @return bool True if the user is authenticated and an admin.
     */
    protected function isAdmin(): bool
    {
        // TODO: Implement real admin check.
        return false;
    }
}