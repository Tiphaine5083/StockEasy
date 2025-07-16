<?php

namespace App\Models;

use App\Core\AbstractModel;

/**
 * StockModel
 *
 * Handles all database queries related to tire stock management.
 *
 * @method \PDO getPdo()
 */
class StockModel extends AbstractModel
{
    /**
     * StockModel constructor.
     * Sets the default table to 'detail_tire'.
     */
    public function __construct() {
        parent::__construct('detail_tire');
    }

    /**
     * Retrieve tires filtered by stock status.
     *
     * @param string $filter Filter type: 'in', 'out', or 'all'.
     * @param int $limit Number of rows to return.
     * @param int $offset Starting offset for pagination.
     * @return array The list of tires matching the filter.
     */
    public function findByStockFilter(string $filter, int $limit, int $offset): array
    {

        $sql = 'SELECT * FROM detail_tire';
        $where = '';

        if ($filter === 'in') {
            $where = ' WHERE quantity_available > 0 ';
        } elseif ($filter === 'out') {
            $where = ' WHERE quantity_available = 0 ';
        }

        $sql .= $where . ' ORDER BY brand, diameter, height, width ';
        $sql .= 'LIMIT ' . $limit . ' OFFSET ' . $offset;

        try
        {
            $query = $this->getPdo()->prepare($sql);
            $query->execute();
            return $query->fetchAll();
        } 
        catch (\PDOException $e) 
        {
            return []; 
        }
    }

        /**
     * Count tires filtered by stock status.
     *
     * @param string $filter Filter type: 'in', 'out', or 'all'.
     * @return int The number of tires matching the filter.
     */
    public function countByStockFilter(string $filter): int
    {

        $sql = 'SELECT COUNT(*) FROM detail_tire';
        $where = '';

        if ($filter === 'in') {
            $where = ' WHERE quantity_available > 0 ';
        } elseif ($filter === 'out') {
            $where = ' WHERE quantity_available = 0 ';
        }

        $sql .= $where;

        try
        {
            $query = $this->getPdo()->prepare($sql);
            $query->execute();
            return $query->fetchColumn();
        } 
        catch (\PDOException $e) 
        {
            return 0; 
        }
    }

    /**
     * Get all distinct tire brands.
     *
     * @return array List of unique brands.
     */    
    public function getBrands(): array
    {
        $sql = 'SELECT DISTINCT brand FROM detail_tire ORDER BY brand';

        try 
        {
            $query = $this->getPdo()->prepare($sql);
            $query->execute();
            return $query->fetchAll(\PDO::FETCH_COLUMN);
        } 
        catch (\PDOException $e) 
        {
            return []; 
        }
    }

    /**
     * Retrieve all possible stock movement reasons.
     *
     * @return array List of enum values for movement reasons.
     */    
    public function getMovementReasons(): array
    {
        $sql = "SHOW COLUMNS FROM stock_movement LIKE 'movement_reason'";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute();
            $columnData = $query->fetch(\PDO::FETCH_ASSOC);

            if ($columnData && isset($columnData['Type'])) {
                $enum = $columnData['Type'];
                preg_match("/^enum\((.*)\)$/", $enum, $matches);
                $values = [];
                if (isset($matches[1])) {
                    foreach (explode(",", $matches[1]) as $value) {
                        $values[] = trim($value, " '");
                    }
                }
                return $values;
            }

            return [];
        }
        catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Search tires based on multiple criteria.
     *
     * Handles exact matches and LIKE for brands,
     * plus multiple values with IN() for array filters.
     *
     * @param array $criteria Key-value pairs for search filters.
     * @return array Matching tires.
     */    
    public function searchStock(array $criteria): array
    {
        $sql = 'SELECT * FROM detail_tire';
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $placeholders = [];
                foreach ($value as $index => $item) {
                    $paramName = ':' . $key . '_' . $index;
                    $placeholders[] = $paramName;
                    $params[$paramName] = $item;
                }
                $conditions[] = "$key IN (" . implode(', ', $placeholders) . ")";
            } else {
                if ($key === 'brand') {
                    $conditions[] = "$key LIKE :$key";
                    $params[":$key"] = '%' . $value . '%';
                } else {
                    $conditions[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY brand, width, height, diameter';

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute($params);
            return $query->fetchAll();
        }
        catch (\PDOException $e) 
        {
            return []; 
        }
    }

    /**
     * Check if a tire has any related invoice lines.
     *
     * @param int $id The detail_tire ID.
     * @return bool True if at least one invoice line exists.
     */    
    public function hasInvoiceLine(int $id): bool
    {
        $sql = 'SELECT COUNT(*) 
                FROM detail_tire 
                JOIN catalog ON detail_tire.id = catalog.id_detail_tire 
                JOIN invoice_line ON catalog.id = invoice_line.id_catalog 
                WHERE detail_tire.id = :id';

        try
        {
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id' => $id]);
            $count = $query->fetchColumn();
            return $count > 0;
        }
        catch (\PDOException $e) 
        {
            return false; 
        }
    }

    /**
     * Get the catalog ID linked to a specific tire.
     *
     * @param int $idDetailTire The ID of the tire in detail_tire.
     * @return int|null The catalog ID if found, null otherwise.
     */    
    public function getCatalogIdByTireId(int $idDetailTire): ?int
    {
        $sql = "SELECT id FROM catalog WHERE id_detail_tire = :id_detail_tire LIMIT 1";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id_detail_tire' => $idDetailTire]);
            $id = $query->fetchColumn();
            return $id ? (int)$id : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Synchronize the catalog entry with the tire data.
     *
     * If no catalog exists for this tire, it inserts one.
     * If it exists, updates it with fresh data from detail_tire.
     * This method is ACID and uses a transaction.
     *
     * @param int $idDetailTire The tire ID to sync.
     * @param array $catalogData Data to merge with detail_tire data.
     * @param bool $fullSync Force full sync even if catalog exists.
     * @return int|null The catalog ID or null on error.
     */
    public function syncCatalogFromTire(int $idDetailTire, array $catalogData, bool $fullSync = false): ?int
    {
        try {
            $sql = "SELECT COUNT(*) FROM catalog WHERE id_detail_tire = :id_detail_tire";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id_detail_tire' => $idDetailTire]);
            $exists = $query->fetchColumn() > 0;

            if (!$exists) {
                $fullSync = true;
            }

            if ($fullSync) {
                $sql = "SELECT quality, diameter, brand, width, height, season 
                        FROM detail_tire WHERE id = :id";
                $query = $this->getPdo()->prepare($sql);
                $query->execute(['id' => $idDetailTire]);
                $tireData = $query->fetch();

                $catalogData['quality'] = $tireData['quality'];
                $catalogData['diameter'] = $tireData['diameter'];
                $catalogData['brand'] = $tireData['brand'];
                $catalogData['width'] = $tireData['width'];
                $catalogData['height'] = $tireData['height'];
                $catalogData['season'] = $tireData['season'];

                $type = ($catalogData['quality'] === 'N') ? "pneu neuf" : "pneu d'occasion";
                $catalogData['name'] = $type . ' ' . $catalogData['diameter'] . "''";
                $catalogData['description'] = $catalogData['brand'] . ' ' 
                    . $catalogData['width'] . '/' 
                    . $catalogData['height'] . '/' 
                    . $catalogData['diameter'] . ' ' 
                    . $catalogData['season'];

                $catalogData['archived'] = 0;
                $catalogData['id_detail_tire'] = $idDetailTire;
            }

            if ($exists) {
                $set = [];
                foreach ($catalogData as $column => $value) {
                    $set[] = "$column = :$column";
                }
                $sql = "UPDATE catalog SET " . implode(', ', $set) . " WHERE id_detail_tire = :id_detail_tire";
                $query = $this->getPdo()->prepare($sql);
                $query->execute($catalogData);

                $id = $this->getCatalogIdByTireId($idDetailTire);
                return $id;

            } else {
                $id = $this->insertCatalog($idDetailTire, $catalogData['brand'], $catalogData['width'], $catalogData['height'], $catalogData['diameter'], $catalogData['season'], $catalogData['unit_price_ht'] ?? $catalogData['unit_price'] ?? 0);
                return $id;
            }

        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Insert a complete stock movement row.
     *
     * @param int $tireId ID of the tire.
     * @param int|null $catalogId Linked catalog ID.
     * @param int $quantity Movement quantity.
     * @param string $movementType 'entrée' or 'sortie'.
     * @param string $reason Main movement reason.
     * @param string|null $reasonDetail Optional detail.
     * @param int|null $idInvoice Optional invoice ID if linked.
     * @return bool True on success.
     */
    public function insertStockMovementFull(int $tireId, ?int $catalogId, int $quantity, string $movementType, string $reason, ?string $reasonDetail = null, ?int $idInvoice = null): bool 
    {
        $this->table = 'stock_movement';
        $data = [
            'movement_type' => $movementType,
            'movement_reason' => $reason,
            'quantity' => $quantity,
            'id_detail_tire' => $tireId,
            'id_catalog' => $catalogId,
            'reason_detail' => $reasonDetail,
            'id_invoice' => $idInvoice
        ];
        return $this->insert($data);
    }

    /**
     * Wrapper for inserting a stock movement.
     *
     * Calls insertStockMovementFull with standard parameters.
     *
     * @param int $idDetailTire ID of the tire.
     * @param string $movementType 'entrée' or 'sortie'.
     * @param int $quantity Quantity moved.
     * @param string $reason Reason for the movement.
     * @param string|null $reasonDetail Optional detail.
     * @param int|null $idInvoice Optional invoice ID.
     * @param int|null $idCatalog Optional catalog ID.
     * @return bool True on success.
     */
    public function syncStockMovement(int $idDetailTire, string $movementType, int $quantity, string $reason, ?string $reasonDetail = null, ?int $idInvoice = null, ?int $idCatalog = null): bool
    {
        return $this->insertStockMovementFull($idDetailTire, $idCatalog, $quantity, $movementType, $reason, $reasonDetail, $idInvoice);
    }

    /**
     * Archive a tire.
     *
     * Updates catalog to archived and sets quantity to zero.
     * If quantity > 0, logs a 'sortie' stock movement.
     * Wrapped in a transaction.
     *
     * @param int $tireId Tire ID.
     * @param string $reason Main reason for archive.
     * @param string|null $reasonDetail Optional detail.
     * @return bool True on success.
     */
    public function archiveTire(int $tireId, string $reason, ?string $reasonDetail = null): bool
    {
        try {
            $this->getPdo()->beginTransaction();
            $sql = "SELECT quantity_available FROM detail_tire WHERE id = :id";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id' => $tireId]);
            $tire = $query->fetch();

            if (!$tire) {
                throw new \Exception('Pneu introuvable pour archivage.');
            }

            $oldQuantity = (int) $tire['quantity_available'];   // cast explicite => force la valeur à êter un entier car PHP le récupère souvent comme une string

            $idCatalog = $this->getCatalogIdByTireId($tireId);

            $sql = "UPDATE catalog SET archived = 1 WHERE id_detail_tire = :id_detail_tire";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id_detail_tire' => $tireId]);

            $sql = "UPDATE detail_tire SET quantity_available = 0 WHERE id = :id";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id' => $tireId]);

            if ($oldQuantity > 0) {
                $this->insertStockMovementFull($tireId, $idCatalog, $oldQuantity, 'sortie', $reason, $reasonDetail);
            }

            $this->getPdo()->commit();
            return true;

        } catch (\PDOException $e) {
            $this->getPdo()->rollBack();
            return false;
        } 
    }

    /**
     * Fully delete a tire and all linked data.
     *
     * Removes stock_movement, catalog, and detail_tire rows.
     * Wrapped in a transaction to ensure atomicity.
     *
     * @param int $tireId Tire ID.
     * @return bool True on success.
     */
    public function deleteTireCompletely(int $tireId): bool
    {
        try {
            $this->getPdo()->beginTransaction();

            $sql = "DELETE FROM stock_movement WHERE id_detail_tire = :id_detail_tire";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id_detail_tire' => $tireId]);

            $sql = "DELETE FROM catalog WHERE id_detail_tire = :id_detail_tire";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id_detail_tire' => $tireId]);

            $sql = "DELETE FROM detail_tire WHERE id = :id";
            $query = $this->getPdo()->prepare($sql);
            $query->execute(['id' => $tireId]);

            $this->getPdo()->commit();

            return true;

        } catch (\PDOException $e) {
            $this->getPdo()->rollBack();
            return false;
        }
    }

    /**
     * Check if a duplicate tire already exists.
     *
     * Matches all key specs: brand, dimensions, indices, DOT, season, quality, unit price.
     *
     * @param array $data Key specs to check.
     * @return int|null The existing tire ID if found, null otherwise.
     */
    public function existsDuplicate(array $data): ?int
    {
        $sql = "SELECT id FROM detail_tire
                WHERE brand = :brand
                AND width = :width
                AND height = :height
                AND diameter = :diameter
                AND load_index = :load_index
                AND speed_index = :speed_index
                AND dot = :dot
                AND season = :season
                AND quality = :quality
                AND unit_price_excluding_tax = :unit_price
                LIMIT 1";

        $query = $this->getPdo()->prepare($sql);
        $query->execute([
            'brand' => $data['brand'],
            'width' => $data['width'],
            'height' => $data['height'],
            'diameter' => $data['diameter'],
            'load_index' => $data['load_index'],
            'speed_index' => $data['speed_index'],
            'dot' => $data['dot'],
            'season' => $data['season'],
            'quality' => $data['quality'],
            'unit_price' => $data['unit_price']
        ]);

        $id = $query->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Insert a new row into detail_tire.
     *
     * @param array $data The tire details.
     * @return int The new tire ID, or 0 on failure.
     */
    public function insertDetailTire(array $data): int
    {
        $this->table = 'detail_tire';
        $success = $this->insert($data);

        return $success ? (int)$this->getPdo()->lastInsertId() : 0;
    }

    /**
     * Insert a new catalog row linked to a tire.
     *
     * Generates name and description automatically.
     *
     * @param int $tireId ID of the related tire.
     * @param string $brand Tire brand.
     * @param string $width Tire width.
     * @param string $height Tire height.
     * @param string $diameter Tire diameter.
     * @param string $season Tire season.
     * @param string $unitPrice Unit price excluding tax.
     * @return int The new catalog ID, or 0 on failure.
     */
    public function insertCatalog(int $tireId, string $brand, string $width, string $height, string $diameter, string $season, string $unitPrice): int
    {
        $this->table = 'catalog';
        $name = "Pneu d'occasion {$diameter}''";
        $description = "{$brand} {$width}/{$height}/{$diameter} {$season}";

        $data = [
            'name' => $name,
            'description' => $description,
            'unit_price_ht' => $unitPrice,
            'archived' => 0,
            'id_detail_tire' => $tireId
        ];

        $success = $this->insert($data);
        return $success ? (int)$this->getPdo()->lastInsertId() : 0;
    }

    /**
     * Insert a default stock entry movement.
     *
     * Always uses type 'entrée' and reason 'achat'.
     *
     * @param int $tireId ID of the tire.
     * @param int $catalogId Linked catalog ID.
     * @param int $quantity Quantity moved.
     * @return bool True on success.
     */
    public function insertStockMovement(int $tireId, int $catalogId, int $quantity): bool
    {
        return $this->insertStockMovementFull($tireId, $catalogId, $quantity, 'entrée', 'achat');
    }

    /**
     * Find all tires registered today.
     *
     * @return array List of tires added today.
     */
    public function findTodayRegistered(): array
    {
        $sql = "SELECT * FROM detail_tire WHERE DATE(stock_registered_at) = CURDATE() ORDER BY id DESC";

        try {
            $query = $this->getPdo()->prepare($sql);
            $query->execute();
            return $query->fetchAll();
        } 
        catch (\PDOException $e) 
        {
            return [];
        }
    }

    /**
     * Create a complete stock entry:
     * - Insert detail_tire
     * - Insert catalog row
     * - Insert stock_movement entry
     * Wrapped in a transaction for ACID safety.
     *
     * @param array $detailData Data for detail_tire.
     * @param array $catalogData Data for catalog.
     * @param int $quantity Initial quantity.
     * @return int The new tire ID.
     * @throws \Exception If any insert fails.
     */
    public function createFullStock(array $detailData, array $catalogData, int $quantity)
    {
        try {
            $this->getPdo()->beginTransaction();

            $tireId = $this->insertDetailTire($detailData);
            if ($tireId === 0) {
                throw new \Exception("Erreur lors de l'insertion du pneu.");
            }

            $catalogId = $this->insertCatalog($tireId, $catalogData['brand'], $catalogData['width'], $catalogData['height'], $catalogData['diameter'], $catalogData['season'], $catalogData['unit_price']);
            if ($catalogId === 0) {
                throw new \Exception("Erreur lors de l'insertion du catalog.");
            }

            $ok = $this->insertStockMovement($tireId, $catalogId, $quantity);
            if (!$ok) {
                throw new \Exception("Erreur lors de l'insertion du mouvement de stock.");
            }

            $this->getPdo()->commit();

            return $tireId;

        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Fully update a tire and sync related catalog + stock movement.
     * Wrapped in a transaction for ACID consistency.
     *
     * @param int $tireId Tire ID to update.
     * @param array $data Fields to update in detail_tire.
     * @param string $movementType 'entrée' or 'sortie'.
     * @param int $movementQty Quantity moved.
     * @param string $reason Main reason for adjustment.
     * @param string|null $reasonDetail Optional detail.
     * @return bool True if all succeeded.
     * @throws \Exception On error.
     */
    public function updateFullStock(int $tireId, array $data, string $movementType, int $movementQty, string $reason, ?string $reasonDetail = null) 
    {
        try {
            $this->getPdo()->beginTransaction();

            $this->update($tireId, $data);

            $idCatalog = $this->syncCatalogFromTire($tireId, $data, false);
            if (!$idCatalog) {
                $idCatalog = $this->getCatalogIdByTireId($tireId);
            }

            $this->syncStockMovement(
                $tireId,
                $movementType,
                $movementQty,
                $reason,
                $reasonDetail,
                null, 
                $idCatalog
            );

            $this->getPdo()->commit();
            return true;

        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw new \Exception("Échec de la transaction : " . $e->getMessage());
        }
    }

}