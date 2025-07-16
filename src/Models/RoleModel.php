<?php

namespace App\Models;

use App\Core\AbstractModel;

class RoleModel extends AbstractModel
{
    /**
     * RoleModel constructor.
     * Sets the default table to 'user'.
     */
    public function __construct() {
        parent::__construct('role');
    }
}