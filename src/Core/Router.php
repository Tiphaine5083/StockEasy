<?php

namespace App\Core;
use App\Core\Access;

/**
 * Router
 *
 * Custom routing system that resolves incoming requests
 * and dispatches them to the correct controller method.
 */
class Router
{
    /** 
     * @var array<string, string> Map of route names to controller handlers
     * Example: ['home' => 'App\Controllers\PublicController::showHome']
     */
    private array $routes =[];

    /**
     * Register a new route.
     *
     * @param string $name    Route identifier (e.g., "home", "login")
     * @param string $handler Fully qualified controller and method (e.g., "App\Controllers\PublicController::showHome")
     * @return void
     */    
    public function addRoute($name, $handler)
    {
        $this->routes[$name] = $handler;
    }

    /**
     * Start the routing process.
     *
     * - Resolves the requested route from GET or POST parameters.
     * - Redirects to login if the user is not authenticated and the route is protected.
     * - Instantiates the controller and executes the corresponding method if found.
     * - Falls back to error404 if the route or handler does not exist.
     *
     * @return void
     */    
    public function startRouting()
    {
        if (array_key_exists('route', $_GET )) {
            $route = $_GET['route'];
        } elseif (array_key_exists('route', $_POST)) {
            $route = $_POST['route'];
        } else {
            $route = null;
        }

        if($route && array_key_exists($route, $this->routes)) {
            $routeHandler = $this->routes[$route];

            $publicRoutes = ['login', 'login-post'];
            if (!in_array($route, $publicRoutes, true) && !Access::isLoggedIn()) {
                if (!isset($_SESSION['user'])) {
                    $_SESSION['error'] = 'Veuillez vous connecter pour accéder à l\'application.';
                }
                header('Location: index.php?route=login');
                exit;
            }

            $explodedHandler = explode( '::', $routeHandler ); 

            if (count($explodedHandler) === 2) {
                [$controller, $method] = $explodedHandler;

                if (class_exists($controller)) {
                    $controllerInstance = new $controller();

                    if (method_exists($controllerInstance, $method)) {
                        $controllerInstance->$method();
                        return;
                    }
                }
            }
            $this->redirectToError404();
        } elseif ($route === null) {
            header('Location: index.php?route=login');
            exit();
        } else {
            $this->redirectToError404();
        }
    }
    
    /**
     * Redirect to the 404 error page.
     *
     * - Sends HTTP 404 status code.
     * - Displays the custom error404 view through PartialsController.
     * - Terminates script execution.
     *
     * @return void
     */
    private function redirectToError404(): void
    {
        http_response_code(404);

        $controller = new \App\Controllers\PartialsController();
        $controller->notFound();

        exit();
    }

}