<?php
/**
 * Application entry point
 *
 * - Starts PHP session and initializes CSRF token
 * - Loads the autoloader
 * - Registers all application routes (public, stock, users, logs, etc.)
 * - Dispatches the current request to the correct controller via Router
 */
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Router;

$router = new Router();

// === Partials Routes
$router->addRoute('construction', \App\Controllers\PartialsController::class . '::underConstruction');

// === Print route
$router->addRoute('stock-print', \App\Controllers\StockController::class . '::stockPrint');

// === Log Routes
$router->addRoute('log-home', \App\Controllers\PublicController::class . '::showLogHome');
$router->addRoute('log-sys', \App\Controllers\PublicController::class . '::showSystemLogs');
$router->addRoute('log-modif', \App\Controllers\PublicController::class . '::showModificationLogs');
$router->addRoute('log-system-post', \App\Controllers\LogController::class . '::filterSystemLogs');
$router->addRoute('log-modification-post', \App\Controllers\LogController::class . '::filterModificationLogs');

// === Generic Route
$router->addRoute('home', \App\Controllers\PublicController::class . '::showHome');

// === Login Routes
$router->addRoute('login', \App\Controllers\AuthController::class . '::showLogin');
$router->addRoute('login-post', \App\Controllers\AuthController::class . '::login');
$router->addRoute('logout', \App\Controllers\AuthController::class . '::logout');
$router->addRoute('password-reset', \App\Controllers\AuthController::class . '::showPasswordReset');
$router->addRoute('password-reset-post', \App\Controllers\UserController::class . '::passwordReset');

// === Stock Routes
$router->addRoute('stock-home', \App\Controllers\PublicController::class . '::showStockHome');
$router->addRoute('stock-list', \App\Controllers\PublicController::class . '::showStockList');
$router->addRoute('ajax-stock-list', \App\Controllers\StockController::class . '::stockListData');
$router->addRoute('stock-search', \App\Controllers\PublicController::class . '::showStockSearch');
$router->addRoute('stock-edit', \App\Controllers\PublicController::class . '::showStockEdit');
$router->addRoute('stock-update', \App\Controllers\StockController::class . '::stockUpdate');
$router->addRoute('stock-delete', \App\Controllers\StockController::class . '::stockDelete');
$router->addRoute('stock-create', \App\Controllers\PublicController::class . '::showStockCreate');
$router->addRoute('stock-store', \App\Controllers\StockController::class . '::stockCreate');
$router->addRoute('stock-increment', \App\Controllers\StockController::class . '::stockIncrement');

// === User Routes
$router->addRoute('user-home', \App\Controllers\PublicController::class . '::showUserHome');
$router->addRoute('user-create', \App\Controllers\PublicController::class . '::showUserCreate');
$router->addRoute('user-list', \App\Controllers\PublicController::class . '::showUserListByFilter');
$router->addRoute('user-edit', \App\Controllers\PublicController::class . '::showUserEdit');
// $router->addRoute('user-role', \App\Controllers\PublicController::class . '::showUserRole');                 Coming soon
// $router->addRoute('user-permission', \App\Controllers\PublicController::class . '::showUserPermission');     Coming soon
$router->addRoute('user-create-submit', \App\Controllers\UserController::class . '::userCreate');
$router->addRoute('user-search', \App\Controllers\UserController::class . '::userSearch');
$router->addRoute('toggle-status', \App\Controllers\UserController::class . '::toggleStatus');
$router->addRoute('user-delete', \App\Controllers\UserController::class . '::userDelete');
$router->addRoute('user-update-submit', \App\Controllers\UserController::class . '::userUpdate');

// === Customer Routes
$router->addRoute('customer-home', \App\Controllers\PublicController::class . '::showCustomerHome');
$router->addRoute('customer-create', \App\Controllers\PartialsController::class . '::underConstruction');
$router->addRoute('customer-search', \App\Controllers\PartialsController::class . '::underConstruction');

// === coming routes
$router->addRoute('editions-home', \App\Controllers\PartialsController::class . '::underConstruction');
$router->addRoute('accounting-home', \App\Controllers\PartialsController::class . '::underConstruction');

// Let's Go :)
$router->startRouting();