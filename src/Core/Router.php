<?php

namespace App\Core;

class Router
{

    private array $routes =[]; // [ 'home' => 'Controller\PublicController::showHome' ]

    public function addRoute($name, $handler)
    {
        $this->routes[$name] = $handler;
    }

    public function startRouting()
    {
        if (array_key_exists( 'route', $_GET )) {
            $route = $_GET['route'];
        } elseif (array_key_exists('route', $_POST)) {
            $route = $_POST['route'];
        } else {
            $route = null;
        }

        if($route && array_key_exists($route, $this->routes)) {
            $routeHandler = $this->routes[$route];
            $explodedHandler = explode( '::', $routeHandler ); // ['Controller\PublicController', 'showHome']

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
            header('Location: index.php?route=home');
            exit();
        } else {
            $this->redirectToError404();
        }
    }

    private function redirectToError404()
    {
        if (isset($this->routes['error404'])) 
        {
            header('Location: index.php?route=error404');
            exit();
        }

        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
    }

}