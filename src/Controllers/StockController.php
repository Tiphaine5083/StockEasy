<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Models\StockModel;
use App\Core\Access;

/**
 * StockController
 *
 * Handles all stock-related operations for data display, CRUD, and sync.
 */
class StockController extends AbstractController {

    /**
     * Output stock list rows as HTML table rows.
     *
     * Only accessible to users except those with 'guest' or 'intern' roles.
     * Typically called via Ajax to dynamically render stock data.
     * Redirects to a 403 error page if access is denied.
     *
     * @return void
     */
    public function stockListData(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Refus d’accès à stockListData() : rôle guest ou stagiaire interdit");
        }

        $filter = $_GET['stockFilter'] ?? 'all';

        if (isset($_GET['limit'])) {
            $limit = 9999;
            $offset = 0;
        } else {
            $page = $_GET['page'] ?? 1;
            $limit = 25;
            $offset = ($page - 1) * $limit;
        }

        $stockModel = new StockModel();
        $data = $stockModel->findByStockFilter($filter, $limit, $offset);

        foreach ($data as $key => $value) {
            echo '<tr>';
                echo '<td>' . $value['brand'] . '</td>';
                echo '<td>' . $value['width'] . '/' . $value['height'] . '/' . $value['diameter'] . '</td>';
                echo '<td>' . $value['load_index'] . $value['speed_index'] . '</td>';
                echo '<td>' . $value['season'] . '</td>';
                echo '<td>' . $value['quality'] . '</td>';
                echo '<td>' . $value['quantity_available'] . '</td>';
                echo '<td>' . $value['unit_price_excluding_tax'] . ' €</td>';
            echo '</tr>';
        }
    }

    /**
     * Update an existing tire stock.
     *
     * Only accessible to users with roles other than 'guest' and 'intern'.
     * Redirects to a 403 error page if access is denied.
     *
     * Handles:
     * - Basic data validation (IDs, quantities, prices)
     * - Restriction for invoiced tires (brand/dimensions lock)
     * - Automatic stock movement if the quantity changes
     * - Synchronizes stock and catalog if needed
     * - Redirects back to stock search with appropriate success or error message
     *
     * @return void
     */
    public function stockUpdate(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Refus d’accès à stockUpdate() : rôle guest ou stagiaire interdit");
        }

        $this->requireCsrfToken();

        try {
            $tireId = $_POST['product_id'] ?? null;

            if ($tireId === null || !ctype_digit($tireId) || $tireId <= 0) {
                throw new \Exception('ID invalide : paramètre manquant ou incorrect');
            }

            $stockModel = new StockModel();
            $tire = $stockModel->find((int)$tireId);

            if (!$tire) {
                throw new \Exception('Pneu introuvable');
            }

            $isInvoiced = $stockModel->hasInvoiceLine($tireId);

            if ($isInvoiced) {
                if (isset($_POST['brand']) || isset($_POST['width']) || isset($_POST['height']) || isset($_POST['diameter']) || isset($_POST['load_index']) || isset($_POST['speed_index']) || isset($_POST['season']) || isset($_POST['quality']) || isset($_POST['dot'])) {
                    throw new \Exception('Modification interdite : pneu déjà facturé');
                }
            }

            $quantity = $_POST['quantity_available'] ?? null;
            $unitPrice = $_POST['unit_price_excluding_tax'] ?? null;

            if ($quantity === null || !is_numeric($quantity) || $quantity <= 0) {
                throw new \Exception('Quantité invalide : la quantité ne peut pas être mise à zéro directement. Veuillez utiliser l’archivage ou un mouvement de stock');
            }
            if ($unitPrice === null || !is_numeric($unitPrice) || $unitPrice < 0) {
                throw new \Exception('Prix unitaire invalide');
            }

            $data = [
                'quantity_available' => $quantity,
                'unit_price_excluding_tax' => $unitPrice,
            ];

            if (!$isInvoiced) {
                $data['brand'] = $_POST['brand'] ?? $tire['brand'];
                $data['width'] = $_POST['width'] ?? $tire['width'];
                $data['height'] = $_POST['height'] ?? $tire['height'];
                $data['diameter'] = $_POST['diameter'] ?? $tire['diameter'];
                $data['load_index'] = $_POST['load_index'] ?? $tire['load_index'];
                $data['speed_index'] = $_POST['speed_index'] ?? $tire['speed_index'];
                $data['season'] = $_POST['season'] ?? $tire['season'];
                $data['quality'] = $_POST['quality'] ?? $tire['quality'];
                $data['dot'] = $_POST['dot'] ?? $tire['dot'];
            }

            if ($quantity != $tire['quantity_available']) {
                if ($quantity > $tire['quantity_available']) {
                    $movementType = 'entrée';
                    $movementQty = $quantity - $tire['quantity_available'];
                } else {
                    $movementType = 'sortie';
                    $movementQty = $tire['quantity_available'] - $quantity;
                }

                $reason = $_POST['movement_reason'] ?? null;
                $reasonDetail = $_POST['reason_detail'] ?? null;
                if (empty($reason)) {
                    throw new \Exception('Le motif est obligatoire');
                }
                if ($reason === 'autre' && empty($reasonDetail)) {
                    throw new \Exception('Merci de préciser le motif si vous choisissez Autre');
                }

                $success = $stockModel->updateFullStock(
                    $tireId,
                    $data,
                    $movementType,
                    $movementQty,
                    $reason,
                    $reasonDetail
                );

            } else {
                $success = $stockModel->update($tireId, $data);
            }

            if (!$success) {
                throw new \Exception('La mise à jour a échoué');
            }

            $params = [
                'success' => 'Pneu modifié avec succès',
                'highlight_id' => $tireId
            ];

            if (!empty($_POST['search_brand'])) {
                $params['brand'] = $_POST['search_brand'];
            }
            if (!empty($_POST['search_width'])) {
                $params['width'] = $_POST['search_width'];
            }
            if (!empty($_POST['search_height'])) {
                $params['height'] = $_POST['search_height'];
            }
            if (!empty($_POST['search_diameter'])) {
                $params['diameter'] = $_POST['search_diameter'];
            }
            if (!empty($_POST['search_load_index'])) {
                $params['load_index'] = $_POST['search_load_index'];
            }
            if (!empty($_POST['search_speed_index'])) {
                $params['speed_index'] = $_POST['search_speed_index'];
            }
            if (!empty($_POST['search_season'])) {
                foreach ($_POST['search_season'] as $season) {
                    $params['season'][] = $season;
                }
            }
            if (!empty($_POST['search_quality'])) {
                foreach ($_POST['search_quality'] as $quality) {
                    $params['quality'][] = $quality;
                }
            }

            $this->redirectToRoute('stock-search', $params);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            $this->redirectToRoute('stock-search');
        }
    }

    /**
     * Delete or archive a tire.
     *
     * Accessible only to 'super_admin', 'admin', or 'secretary' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * Handles:
     * - Direct deletion if the tire is not invoiced
     * - Forced archive if the tire is invoiced
     * - Always records the reason and optional detail
     * - Redirects back with a proper success or error message
     *
     * @return void
     */
    public function stockDelete(): void
    {
        if (!Access::hasOneRole(['super_admin', 'admin', 'secretary'])) {
            $this->denyAccess("Refus d’accès à stockDelete() : rôle non autorisé");
        }

        $this->requireCsrfToken();

        try {
            $tireId = $_POST['product_id'] ?? null;
            $action = $_POST['delete_action'] ?? null;

            if ($tireId === null || !ctype_digit($tireId) || $tireId <= 0) {
                throw new \Exception('ID invalide');
            }

            if (!in_array($action, ['delete', 'archive'])) {
                throw new \Exception('Action invalide');
            }

            $stockModel = new StockModel();
            $tire = $stockModel->find((int)$tireId);

            if (!$tire) {
                throw new \Exception('Pneu introuvable');
            }

            $isInvoiced = $stockModel->hasInvoiceLine($tireId);
            if ($isInvoiced) {
                $action = 'archive'; 
            }

            if ($action === 'delete' && $_SESSION['user']['role'] === 'secretary') {
                $this->denyAccess("Refus de suppression dans stockDelete() : rôle secretary interdit pour delete");
            }

            $reason = $_POST['movement_reason'] ?? null;
            $reasonDetail = $_POST['reason_detail'] ?? null;

            if ($action === 'archive') {
                if (empty($reason)) {
                    throw new \Exception('Motif requis pour archivage.');
                }
                if ($reason === 'autre' && empty($reasonDetail)) {
                    throw new \Exception('Précisez le motif si Autre.');
                }

                $success = $stockModel->archiveTire((int)$tireId, $reason, $reasonDetail);
            }

            if ($action === 'delete' && !$isInvoiced) {
                $success = $stockModel->deleteTireCompletely((int)$tireId);
            }

            if (!$success) {
                throw new \Exception('L\'opération a échoué.');
            }

            $_SESSION['success'] = $action === 'archive' ? 'Pneu archivé avec succès' : 'Pneu supprimé avec succès';

            $params = [];

            if (!empty($_POST['search_brand'])) { $params['brand'] = $_POST['search_brand']; }
            if (!empty($_POST['search_width'])) { $params['width'] = $_POST['search_width']; }
            if (!empty($_POST['search_height'])) { $params['height'] = $_POST['search_height']; }
            if (!empty($_POST['search_diameter'])) { $params['diameter'] = $_POST['search_diameter']; }
            if (!empty($_POST['search_load_index'])) { $params['load_index'] = $_POST['search_load_index']; }
            if (!empty($_POST['search_speed_index'])) { $params['speed_index'] = $_POST['search_speed_index']; }

            if (!empty($_POST['search_season'])) {
                foreach ($_POST['search_season'] as $season) {
                    $params['season'][] = $season;
                }
            }
            if (!empty($_POST['search_quality'])) {
                foreach ($_POST['search_quality'] as $quality) {
                    $params['quality'][] = $quality;
                }
            }

            $this->redirectToRoute('stock-search', $params);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToRoute('stock-search');
        }
    }

    /**
     * Create a new tire stock entry.
     *
     * Accessible to all authenticated users except those with 'guest' or 'intern' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * Validates all fields:
     * - Brand, dimensions, indices, DOT, season, quality, price
     * - Ensures valid numerical values
     * - Calls the full creation routine with catalog sync and initial stock movement
     * - Redirects back with success or error message
     *
     * Note: Duplicate check logic to be added in V1.
     *
     * @return void
     */
    public function stockCreate(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Refus d’accès à stockCreate() : rôle guest ou stagiaire interdit");
        }

        $this->requireCsrfToken();

        try {
            $brand = trim($_POST['brand'] ?? '');
            $brandOther = trim($_POST['brand_other'] ?? '');
            $width = trim($_POST['width'] ?? '');
            $height = trim($_POST['height'] ?? '');
            $diameter = trim($_POST['diameter'] ?? '');
            $loadIndex = trim($_POST['load_index'] ?? '');
            $speedIndex = trim($_POST['speed_index'] ?? '');
            $dot = trim($_POST['dot'] ?? '');
            $season = trim($_POST['season'] ?? '');
            $quality = trim($_POST['quality'] ?? '');
            $quantity = trim($_POST['quantity_available'] ?? '');
            $unitPrice = trim($_POST['unit_price_excluding_tax'] ?? '');
            $features = trim($_POST['features'] ?? '');

            if (!empty($brandOther)) {
                $brand = strtoupper($brandOther);
            }
            if (empty($brand)) {
                throw new \Exception('La marque est obligatoire');
            }
            if (!ctype_digit($width) || strlen($width) !== 3) {
                throw new \Exception('La largeur doit être composée de 3 chiffres');
            }
            if (!(ctype_digit($height) && strlen($height) === 2) && !preg_match('/^[A-Za-z]$/', $height)) {
                throw new \Exception('La hauteur doit être composée de 2 chiffres ou d\'une lettre');
            }
            if (!ctype_digit($diameter) || strlen($diameter) !== 2) {
                throw new \Exception('Le diamètre doit être composé de 2 chiffres');
            }
            if (!ctype_digit($loadIndex) || strlen($loadIndex) < 2 || strlen($loadIndex) > 3) {
                throw new \Exception('L\'indice de charge doit contenir 2 ou 3 chiffres');
            }
            if (!preg_match('/^[A-Za-z]$/', $speedIndex)) {
                throw new \Exception('L\'indice de vitesse doit être une lettre');
            }
            if (!ctype_digit($dot)) {
                throw new \Exception('Le DOT doit être numérique');
            }
            if (strlen($dot) === 2) {
                $dot = '20' . $dot;
            }
            if ((int)$dot < 2000 || (int)$dot > intval(date('Y')) + 1) {
                throw new \Exception('Le DOT doit être supérieur ou égal à 2000 et cohérent');
            }
            if (empty($season)) {
                throw new \Exception('La saison est obligatoire');
            }
            if (empty($quality)) {
                throw new \Exception('L\'état est obligatoire');
            }
            if (!is_numeric($quantity) || (int)$quantity <= 0) {
                throw new \Exception('La quantité doit être supérieure à 0');
            }
            $unitPrice = str_replace(',', '.', $unitPrice);
            if (!is_numeric($unitPrice) || (float)$unitPrice <= 0) {
                throw new \Exception('Le prix unitaire doit être supérieur à 0');
            }

            $unitPrice = number_format((float)$unitPrice, 2, '.', '');

            $stockModel = new StockModel();

            $duplicateId = $stockModel->existsDuplicate([
                'brand' => $brand,
                'width' => $width,
                'height' => $height,
                'diameter' => $diameter,
                'load_index' => $loadIndex,
                'speed_index' => $speedIndex,
                'dot' => $dot,
                'season' => $season,
                'quality' => $quality,
                'unit_price' => $unitPrice
            ]);

            if ($duplicateId) {
                $this->redirectToRoute('stock-create', [
                    'duplicate_id' => $duplicateId,
                    'brand' => $brand,
                    'width' => $width,
                    'height' => $height,
                    'diameter' => $diameter,
                    'load_index' => $loadIndex,
                    'speed_index' => $speedIndex,
                    'dot' => $dot,
                    'season' => $season,
                    'quality' => $quality,
                    'unit_price' => $unitPrice,
                    'quantity_available' => $quantity
                ]);
                return;
            }

            $detailData = [
                'brand' => $brand,
                'width' => $width,
                'height' => $height,
                'diameter' => $diameter,
                'load_index' => $loadIndex,
                'speed_index' => $speedIndex,
                'dot' => $dot,
                'season' => $season,
                'quality' => $quality,
                'features' => $features,
                'unit_price_excluding_tax' => $unitPrice,
                'quantity_available' => $quantity
            ];

            $catalogData = [
                'brand' => $brand,
                'width' => $width,
                'height' => $height,
                'diameter' => $diameter,
                'season' => $season,
                'unit_price' => $unitPrice
            ];

            $stockModel->createFullStock($detailData, $catalogData, $quantity);

            $_SESSION['success'] = 'Pneu ajouté avec succès';
            $this->redirectToRoute('stock-create');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            $this->redirectToRoute('stock-create');
        }
    }

    /**
     * Output today's created tires as HTML table rows.
     *
     * Accessible to all authenticated users except those with 'guest' or 'intern' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * Typically called via Ajax to refresh the create page data table.
     * Escapes all values for safety.
     *
     * @return void
     */
    public function stockCreateData(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Refus d’accès à stockCreateData() : rôle guest ou stagiaire interdit");
        }
        
        $stockModel = new StockModel();
        $tires = $stockModel->findTodayRegistered();

        foreach ($tires as $tire) {
            echo '<tr>';
            echo '<td data-label="Marque">' . htmlspecialchars($tire['brand']) . '</td>';
            echo '<td data-label="Taille commerciale">' . htmlspecialchars($tire['width']) . '/' . htmlspecialchars($tire['height']) . '/' . htmlspecialchars($tire['diameter']) . '</td>';
            echo '<td data-label="Charge/Vitesse">' . htmlspecialchars($tire['load_index']) . htmlspecialchars($tire['speed_index']) . '</td>';
            echo '<td data-label="Saison">' . htmlspecialchars($tire['season']) . '</td>';
            echo '<td data-label="DOT">' . htmlspecialchars($tire['dot']) . '</td>';
            echo '<td data-label="État">' . htmlspecialchars($tire['quality']) . '</td>';
            echo '<td data-label="Particularités">' . htmlspecialchars($tire['features']) . '</td>';
            echo '<td data-label="Quantité">' . htmlspecialchars($tire['quantity_available']) . '</td>';
            echo '<td data-label="Prix unitaire H.T.">' . htmlspecialchars($tire['unit_price_excluding_tax']) . ' €</td>';
            echo '</tr>';
        }
    }

    /**
     * Increment stock quantity for an existing tire.
     *
     * Accessible to all authenticated users except those with 'guest' or 'intern' roles.
     * Redirects to a 403 error page if access is denied.
     *
     * Adds quantity and records the stock movement as an 'entrée' with reason 'achat'.
     *
     * @return void
     */
    public function stockIncrement(): void
    {
        if (Access::hasOneRole(['guest', 'intern'])) {
            $this->denyAccess("Refus d’accès à stockIncrement() : rôle guest ou stagiaire interdit");
        }

        $this->requireCsrfToken();
    
        try {
            $tireId = $_POST['product_id'] ?? null;
            $addedQty = $_POST['added_quantity'] ?? null;

            if ($tireId === null || !ctype_digit($tireId) || $tireId <= 0) {
                throw new \Exception('ID invalide.');
            }

            if ($addedQty === null || !is_numeric($addedQty) || $addedQty <= 0) {
                throw new \Exception('Quantité invalide.');
            }

            $stockModel = new StockModel();
            $tire = $stockModel->find((int)$tireId);

            if (!$tire) {
                throw new \Exception('Pneu introuvable.');
            }

            $newQty = $tire['quantity_available'] + $addedQty;

            $success = $stockModel->updateFullStock(
                $tireId,
                ['quantity_available' => $newQty],
                'entrée',
                $addedQty,
                'achat',
                null
            );

            if (!$success) {
                throw new \Exception('L’incrément du stock a échoué.');
            }

            $_SESSION['success'] = 'Stock augmenté avec succès.';
            $this->redirectToRoute('stock-create');

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToRoute('stock-create');
        }
    }

}