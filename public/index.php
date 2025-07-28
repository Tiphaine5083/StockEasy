<?php
session_start();

require_once __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Router;

$router = new Router();

// === Partials Routes
$router->addRoute('error404', \App\Controllers\PartialsController::class . '::notFound');
$router->addRoute('error403', \App\Controllers\PartialsController::class . '::forbidden');
$router->addRoute('construction', \App\Controllers\PartialsController::class . '::underConstruction');

// === Log Routes
$router->addRoute('log-home', \App\Controllers\PublicController::class . '::showLogHome');
$router->addRoute('log-sys', \App\Controllers\PublicController::class . '::showSystemLogs');
$router->addRoute('log-modif', \App\Controllers\PublicController::class . '::showModificationLogs');

// === Generic Route
$router->addRoute('home', \App\Controllers\PublicController::class . '::showHome');

// === Login Routes
$router->addRoute('login', \App\Controllers\AuthController::class . '::showLogin');
$router->addRoute('login-post', \App\Controllers\AuthController::class . '::login');
$router->addRoute('logout', \App\Controllers\AuthController::class . '::logout');

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

// En route mauvaise troupe !
$router->startRouting();