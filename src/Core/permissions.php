<?php

return [
    // USER
    'read_user'         => ['super_admin'],
    'create_user'       => ['super_admin'],
    'update_user'       => ['super_admin'],
    'archive_user'      => ['super_admin'],
    'delete_user'       => ['super_admin'],

    // STOCK
    'read_stock'        => ['super_admin', 'admin', 'secretary', 'user', 'intern'],
    'create_stock'      => ['super_admin', 'admin', 'secretary', 'user'],
    'update_stock'      => ['super_admin', 'admin', 'secretary', 'user'],
    'archive_stock'     => ['super_admin', 'admin', 'secretary'],
    'delete_stock'      => ['super_admin', 'admin'],

    // LOGS
    'read_logs'         => ['super_admin'],

    // CUSTOMERS — in anticipation of V2
    'read_customer'     => ['super_admin', 'admin', 'secretary', 'user', 'intern'],
    'create_customer'   => ['super_admin', 'admin', 'secretary', 'user'],
    'update_customer'   => ['super_admin', 'admin', 'secretary', 'user'],
    'archive_customer'  => ['super_admin', 'admin', 'secretary'],
    'delete_customer'   => ['super_admin', 'admin'],
];
